<?php
require_once '../includes/session.php';
verifierRole('etudiant');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: mes_cours.php"); exit(); }
verifier_csrf();

$id_etudiant = $_SESSION['user_id'];
$id_eval     = intval($_POST['id_evaluation'] ?? 0);
$id_cours    = intval($_POST['id_cours'] ?? 0);

// Récupérer les questions
$questions_r = $conn->query("SELECT * FROM questions WHERE id_evaluation=$id_eval ORDER BY id");
$questions = [];
while ($q = $questions_r->fetch_assoc()) $questions[] = $q;

$score     = 0;
$score_max = 0;

// Insérer le résultat principal
$stmt = $conn->prepare("INSERT INTO resultats_evaluations(id_etudiant,id_evaluation,score,score_max,pourcentage,reussi) VALUES(?,?,0,0,0,0)");
$stmt->bind_param("ii", $id_etudiant, $id_eval);
$stmt->execute();
$id_resultat = $conn->insert_id;

foreach ($questions as $q) {
    $score_max += $q['points'];
    if ($q['type'] === 'qcm') {
        $rep = $_POST['q' . $q['id']] ?? null;
        if ($rep && $rep === $q['bonne_reponse']) $score += $q['points'];
    } else {
        // Question ouverte — enregistrer la réponse pour correction manuelle
        $rep_open = trim($_POST['q' . $q['id'] . '_open'] ?? '');
        $s2 = $conn->prepare("INSERT INTO reponses_ouvertes(id_resultat,id_question,reponse) VALUES(?,?,?)");
        $s2->bind_param("iis", $id_resultat, $q['id'], $rep_open);
        $s2->execute();
    }
}

// Calculer pourcentage (basé sur QCM uniquement si des questions ouvertes existent)
$pct = $score_max > 0 ? round(($score / $score_max) * 100, 2) : 0;

// Récupérer le seuil de réussite
$eval = $conn->query("SELECT * FROM evaluations WHERE id=$id_eval")->fetch_assoc();
$reussi = $pct >= $eval['note_passage'] ? 1 : 0;

// Mettre à jour le résultat
$s3 = $conn->prepare("UPDATE resultats_evaluations SET score=?,score_max=?,pourcentage=?,reussi=? WHERE id=?");
$s3->bind_param("dddii", $score, $score_max, $pct, $reussi, $id_resultat);
$s3->execute();

// Marquer la leçon comme vue
$id_lecon = $eval['id_lecon'];
$conn->query("INSERT IGNORE INTO lecons_vues(id_etudiant,id_lecon) VALUES($id_etudiant,$id_lecon)");

// Vérifier si le module est validé → générer certificat
$module = $conn->query("
    SELECT m.* FROM modules m
    JOIN cours c ON c.id_module=m.id
    WHERE c.id=$id_cours LIMIT 1
")->fetch_assoc();

if ($module) {
    $id_module   = $module['id'];
    $note_min    = $module['note_validation'];
    $nb_evals_module = $conn->query("
        SELECT COUNT(*) n FROM evaluations ev
        JOIN lecons l ON ev.id_lecon=l.id
        JOIN cours c ON l.id_cours=c.id
        WHERE c.id_module=$id_module
    ")->fetch_assoc()['n'];

    $nb_evals_passes = $conn->query("
        SELECT COUNT(*) n FROM resultats_evaluations re
        JOIN evaluations ev ON re.id_evaluation=ev.id
        JOIN lecons l ON ev.id_lecon=l.id
        JOIN cours c ON l.id_cours=c.id
        WHERE c.id_module=$id_module AND re.id_etudiant=$id_etudiant
    ")->fetch_assoc()['n'];

    if ($nb_evals_module > 0 && $nb_evals_passes >= $nb_evals_module) {
        $avg_module = $conn->query("
            SELECT AVG(re.pourcentage) avg FROM resultats_evaluations re
            JOIN evaluations ev ON re.id_evaluation=ev.id
            JOIN lecons l ON ev.id_lecon=l.id
            JOIN cours c ON l.id_cours=c.id
            WHERE c.id_module=$id_module AND re.id_etudiant=$id_etudiant
        ")->fetch_assoc()['avg'];

        if ($avg_module >= $note_min) {
            $already = $conn->query("SELECT id FROM certificats WHERE id_etudiant=$id_etudiant AND id_module=$id_module")->num_rows;
            if (!$already) {
                $code = strtoupper(substr(md5($id_etudiant . $id_module . time()), 0, 12));
                $conn->query("INSERT INTO certificats(id_etudiant,id_module,code_unique) VALUES($id_etudiant,$id_module,'$code')");
                envoyerNotification($conn, $id_etudiant, 'bravo', 'Félicitations !', 'Vous avez obtenu le certificat pour le module : ' . $module['titre'], 'etudiant/certificats.php');
            }
        }
    }
}

// Rediriger vers la leçon avec les résultats
header("Location: voir_cours.php?id=$id_cours&lecon=$id_lecon");
exit();
