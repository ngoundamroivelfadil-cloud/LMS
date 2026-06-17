<?php
require_once '../includes/session.php';
verifierRole('etudiant');
require_once '../config/database.php';
require_once '../includes/sidebar.php';

$id = $_SESSION['user_id'];

// S'inscrire
if (isset($_GET['inscrire'])) {
    $id_cours = intval($_GET['inscrire']);
    $conn->query("INSERT IGNORE INTO inscriptions(id_etudiant,id_cours) VALUES($id,$id_cours)");
    header("Location: voir_cours.php?id=$id_cours");
    exit();
}

// Recherche
$search = trim($_GET['q'] ?? '');
$where  = $search ? "AND (c.titre LIKE '%".addslashes($search)."%' OR c.description LIKE '%".addslashes($search)."%')" : '';

$cours = $conn->query("
    SELECT c.*, u.nom enseignant_nom, m.titre module_titre,
           COUNT(DISTINCT l.id) nb_lecons,
           COUNT(DISTINCT i2.id) nb_inscrits,
           MAX(CASE WHEN i.id_etudiant=$id THEN 1 ELSE 0 END) inscrit
    FROM cours c
    LEFT JOIN utilisateurs u ON c.id_enseignant=u.id
    LEFT JOIN modules m ON c.id_module=m.id
    LEFT JOIN lecons l ON l.id_cours=c.id
    LEFT JOIN inscriptions i2 ON i2.id_cours=c.id
    LEFT JOIN inscriptions i ON i.id_cours=c.id AND i.id_etudiant=$id
    WHERE 1=1 $where
    GROUP BY c.id ORDER BY c.date_creation DESC
");

$modules = $conn->query("SELECT * FROM modules ORDER BY titre");
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Catalogue des cours</title>
<link rel="icon" type="image/svg+xml" href="../img/logo.svg">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"></head>
<body>
<div class="layout">
<?php sidebar('etudiant','catalogue'); ?>
<main class="main-content">
    <div class="page-header">
        <div><h1>🔍 Catalogue des cours</h1><p>Découvrez tous les cours disponibles sur la plateforme</p></div>
    </div>

    <!-- Recherche -->
    <form method="GET" style="margin-bottom:24px;">
        <div class="topbar-search" style="max-width:400px;">
            <span>🔍</span>
            <input type="text" name="q" value="<?=sanitize($search)?>" placeholder="Rechercher un cours..." style="width:100%;">
            <button type="submit" class="btn btn-primary btn-sm">Chercher</button>
        </div>
    </form>

    <!-- Par module -->
    <?php
    $arr = [];
    while ($c = $cours->fetch_assoc()) $arr[] = $c;

    $by_module = [];
    foreach ($arr as $c) {
        $key = $c['module_titre'] ?? '(Sans module)';
        $by_module[$key][] = $c;
    }

    $colors = ['c1','c2','c3','c4','c5'];
    $emojis = ['📘','📗','📙','📕','💻','🔬','🎨','📐'];
    $gi = 0;

    foreach ($by_module as $mod_titre => $cours_list):
    ?>
    <div style="margin-bottom:32px;">
        <h2 style="font-size:1rem;font-weight:700;color:var(--dark);margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <?= $mod_titre !== '(Sans module)' ? '📦 ' . sanitize($mod_titre) : '📚 ' . $mod_titre ?>
        </h2>
        <div class="cours-grid">
        <?php foreach ($cours_list as $c):
            $ci = $colors[$gi % 5]; $ei = $emojis[$gi % 8]; $gi++;
        ?>
        <div class="cours-card">
            <div class="cours-card-banner <?=$ci?>"><?=$ei?></div>
            <div class="cours-card-body">
                <h3><?=sanitize($c['titre'])?></h3>
                <p><?=sanitize($c['description'] ?? 'Aucune description')?></p>
                <div class="cours-card-meta">
                    <span class="cours-meta-item"> <?=sanitize($c['enseignant_nom'])?></span>
                    <span class="cours-meta-item"> <?=$c['nb_lecons']?> leçons</span>
                    <span class="cours-meta-item"> <?=$c['nb_inscrits']?> inscrits</span>
                </div>
            </div>
            <div class="cours-card-footer">
                <?php if ($c['inscrit']): ?>
                    <span class="badge badge-success">Inscrit</span>
                    <a href="voir_cours.php?id=<?=$c['id']?>" class="btn btn-primary btn-sm">Continuer →</a>
                <?php else: ?>
                    <span></span>
                    <a href="?inscrire=<?=$c['id']?>" class="btn btn-success btn-sm">S'inscrire</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($arr)): ?>
    <div class="empty-state">
        <div class="icon"></div>
        <h3>Aucun cours trouvé</h3>
        <p><?= $search ? "Essayez un autre mot-clé." : "Aucun cours disponible pour le moment." ?></p>
    </div>
    <?php endif; ?>
</main>
</div>
<?php require_once '../includes/footer.php'; pageFooter(); ?>
<script src="../js/app.js"></script>
</body></html>
