<?php
require_once '../includes/session.php';
verifierRole('etudiant');
require_once '../config/database.php';
require_once '../includes/sidebar.php';

$id = $_SESSION['user_id'];

$cours = $conn->query("
    SELECT c.*, u.nom enseignant_nom, m.titre module_titre,
           COUNT(DISTINCT l.id) nb_lecons,
           COUNT(DISTINCT lv.id) nb_vus,
           ROUND(AVG(re.pourcentage),1) moy_eval,
           COUNT(DISTINCT re.id) nb_evals
    FROM inscriptions i
    JOIN cours c ON i.id_cours=c.id
    LEFT JOIN utilisateurs u ON c.id_enseignant=u.id
    LEFT JOIN modules m ON c.id_module=m.id
    LEFT JOIN lecons l ON l.id_cours=c.id
    LEFT JOIN lecons_vues lv ON lv.id_lecon=l.id AND lv.id_etudiant=$id
    LEFT JOIN evaluations ev ON ev.id_lecon=l.id
    LEFT JOIN resultats_evaluations re ON re.id_evaluation=ev.id AND re.id_etudiant=$id
    WHERE i.id_etudiant=$id
    GROUP BY c.id ORDER BY i.date_inscription DESC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Mes cours</title>
<link rel="icon" type="image/svg+xml" href="../img/logo.svg">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"></head>
<body>
<div class="layout">
<?php sidebar('etudiant','cours'); ?>
<main class="main-content">
    <div class="page-header">
        <div><h1>Mes cours</h1><p>Suivez votre progression dans chaque cours</p></div>
        <a href="catalogue.php" class="btn btn-primary">Découvrir plus</a>
    </div>

    <div class="cours-grid">
    <?php
    $colors = ['c1','c2','c3','c4','c5']; $i = 0;
    while ($c = $cours->fetch_assoc()):
        $ci = $colors[$i % 5]; $i++;
        $pct = $c['nb_lecons'] > 0 ? min(100, round(($c['nb_vus'] / $c['nb_lecons']) * 100)) : 0;
    ?>
    <div class="cours-card">
        <div class="cours-card-banner <?=$ci?>" style="position:relative;">
            <?php if ($pct === 100): ?>
            <span style="position:absolute;top:10px;right:10px;background:var(--success);color:white;font-size:.7rem;font-weight:700;padding:4px 8px;border-radius:20px;">Terminé</span>
            <?php endif; ?>
        </div>
        <div class="cours-card-body">
            <h3><?=sanitize($c['titre'])?></h3>
            <p style="font-size:.82rem;color:var(--text-muted);">Enseignant : <?=sanitize($c['enseignant_nom'])?></p>
            <?php if ($c['module_titre']): ?>
            <span class="badge badge-enseignant" style="margin:6px 0;display:inline-block;">Module : <?=sanitize($c['module_titre'])?></span>
            <?php endif; ?>

            <div class="progress-wrap">
                <div class="progress-label"><span>Progression</span><span><?=$pct?>%</span></div>
                <div class="progress-bar"><div class="progress-fill <?=$pct>=80?'green':''?>" data-width="<?=$pct?>" style="width:0%"></div></div>
            </div>
            <?php if ($c['nb_evals'] > 0): ?>
            <p style="font-size:.78rem;color:var(--text-muted);margin-top:8px;">
                <?=$c['nb_evals']?> évaluation(s) passée(s) · Moyenne : <strong style="color:<?=($c['moy_eval']??0)>=50?'var(--success)':'var(--danger)'?>"><?=$c['moy_eval']??'—'?>%</strong>
            </p>
            <?php endif; ?>
        </div>
        <div class="cours-card-footer">
            <span class="cours-meta-item"><?=$c['nb_lecons']?> leçons</span>
            <a href="voir_cours.php?id=<?=$c['id']?>" class="btn btn-primary btn-sm">
                <?= $pct === 100 ? 'Revoir' : 'Continuer' ?>
            </a>
        </div>
    </div>
    <?php endwhile; if ($i === 0): ?>
    <div class="empty-state" style="grid-column:1/-1;">
        <div class="icon"></div>
        <h3>Aucun cours inscrit</h3>
        <p>Vous n'êtes encore inscrit à aucun cours.</p>
        <a href="catalogue.php" class="btn btn-primary" style="margin-top:16px;">Voir le catalogue</a>
    </div>
    <?php endif; ?>
    </div>
</main>
</div>
<?php require_once '../includes/footer.php'; pageFooter(); ?>
<script src="../js/app.js"></script>
</body></html>
