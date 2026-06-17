<?php
require_once '../includes/session.php';
verifierRole('etudiant');
require_once '../config/database.php';
require_once '../includes/sidebar.php';

$id=$_SESSION['user_id'];
$nb_cours=$conn->query("SELECT COUNT(*) n FROM inscriptions WHERE id_etudiant=$id")->fetch_assoc()['n'];
$nb_cert=$conn->query("SELECT COUNT(*) n FROM certificats WHERE id_etudiant=$id")->fetch_assoc()['n'];
$nb_eval=$conn->query("SELECT COUNT(*) n FROM resultats_evaluations WHERE id_etudiant=$id")->fetch_assoc()['n'];
$avg=$conn->query("SELECT AVG(pourcentage) avg FROM resultats_evaluations WHERE id_etudiant=$id")->fetch_assoc()['avg'];

$mes_cours=$conn->query("
    SELECT c.*,u.nom enseignant_nom,m.titre module_titre,
           COUNT(DISTINCT l.id) nb_lecons,
           COUNT(DISTINCT lv.id) nb_vus,
           ROUND(AVG(re.pourcentage),1) moy_eval
    FROM inscriptions i
    JOIN cours c ON i.id_cours=c.id
    LEFT JOIN utilisateurs u ON c.id_enseignant=u.id
    LEFT JOIN modules m ON c.id_module=m.id
    LEFT JOIN lecons l ON l.id_cours=c.id
    LEFT JOIN lecons_vues lv ON lv.id_lecon=l.id AND lv.id_etudiant=$id
    LEFT JOIN evaluations ev ON ev.id_lecon=l.id
    LEFT JOIN resultats_evaluations re ON re.id_evaluation=ev.id AND re.id_etudiant=$id
    WHERE i.id_etudiant=$id
    GROUP BY c.id ORDER BY i.date_inscription DESC LIMIT 4
");
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Étudiant — Tableau de bord</title><link rel="icon" type="image/svg+xml" href="../img/logo.svg">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"></head>
<body>
<div class="layout">
<?php sidebar('etudiant','dashboard'); ?>
<main class="main-content">
    <div class="topbar">
        <div><strong>Bienvenue, <?=sanitize($_SESSION['nom'])?></strong><p style="font-size:.82rem;color:var(--text-muted);">Continuez votre apprentissage là où vous vous êtes arrêté.</p></div>
        <a href="catalogue.php" class="btn btn-primary btn-sm">Découvrir des cours</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card blue"><div class="stat-icon blue"></div><div class="stat-info"><h3><?=$nb_cours?></h3><p>Cours suivis</p></div></div>
        <div class="stat-card green"><div class="stat-icon green"></div><div class="stat-info"><h3><?=$nb_eval?></h3><p>Évaluations passées</p></div></div>
        <div class="stat-card purple"><div class="stat-icon purple"></div><div class="stat-info"><h3><?=round($avg??0,1)?>%</h3><p>Moyenne générale</p></div></div>
        <div class="stat-card orange"><div class="stat-icon orange"></div><div class="stat-info"><h3><?=$nb_cert?></h3><p>Certificats obtenus</p></div></div>
    </div>

    <div class="card">
        <div class="card-header"><h2>Mes cours en cours</h2><a href="mes_cours.php" class="btn btn-primary btn-sm">Voir tous</a></div>
        <div class="cours-grid" style="padding:20px;">
        <?php
        $colors=['c1','c2','c3','c4'];$i=0;
        while($c=$mes_cours->fetch_assoc()):
            $ci=$colors[$i%4];$i++;
            $pct=$c['nb_lecons']>0?min(100,round(($c['nb_vus']/$c['nb_lecons'])*100)):0;
        ?>
        <div class="cours-card">
            <div class="cours-card-body">
                <h3><?=sanitize($c['titre'])?></h3>
                <p style="font-size:.8rem;color:var(--text-muted);">Enseignant : <?=sanitize($c['enseignant_nom'])?></p>
                <?php if($c['module_titre']): ?><span class="badge badge-enseignant" style="margin:4px 0;display:inline-block;">Module : <?=sanitize($c['module_titre'])?></span><?php endif; ?>
                <div class="progress-wrap">
                    <div class="progress-label"><span>Progression</span><span><?=$pct?>%</span></div>
                    <div class="progress-bar"><div class="progress-fill" data-width="<?=$pct?>" style="width:0%"></div></div>
                </div>
                <?php if($c['moy_eval']): ?><p style="font-size:.78rem;color:var(--text-muted);margin-top:4px;"> Moyenne éval. : <strong><?=$c['moy_eval']?>%</strong></p><?php endif; ?>
            </div>
            <div class="cours-card-footer">
                <span class="cours-meta-item"><?=$c['nb_lecons']?> leçons</span>
                <a href="voir_cours.php?id=<?=$c['id']?>" class="btn btn-primary btn-sm">Continuer</a>
            </div>
        </div>
        <?php endwhile; if($i===0): ?>
        <div class="empty-state" style="grid-column:1/-1;"><div class="icon"></div><h3>Aucun cours</h3><p><a href="catalogue.php" style="color:var(--primary);">Découvrez le catalogue</a> et inscrivez-vous.</p></div>
        <?php endif; ?>
        </div>
    </div>
</main>
</div>
<?php require_once '../includes/footer.php'; pageFooter(); ?>
<script src="../js/app.js"></script>
</body></html>
