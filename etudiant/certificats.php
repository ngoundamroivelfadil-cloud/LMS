<?php
require_once '../includes/session.php';
verifierRole('etudiant');
require_once '../config/database.php';
require_once '../includes/sidebar.php';

$id = $_SESSION['user_id'];

$certificats = $conn->query("
    SELECT cert.*, m.titre module_titre, m.description module_desc,
           u.nom promoteur_nom
    FROM certificats cert
    JOIN modules m ON cert.id_module=m.id
    JOIN utilisateurs u ON m.id_promoteur=u.id
    WHERE cert.id_etudiant=$id
    ORDER BY cert.date_obtention DESC
");

// Modules en cours (non encore certifiés)
$modules_en_cours = $conn->query("
    SELECT m.*, m.note_validation,
           COUNT(DISTINCT ev.id) nb_evals,
           COUNT(DISTINCT re.id) nb_passes,
           ROUND(AVG(re.pourcentage),1) moy
    FROM modules m
    JOIN cours c ON c.id_module=m.id
    JOIN inscriptions i ON i.id_cours=c.id AND i.id_etudiant=$id
    LEFT JOIN lecons l ON l.id_cours=c.id
    LEFT JOIN evaluations ev ON ev.id_lecon=l.id
    LEFT JOIN resultats_evaluations re ON re.id_evaluation=ev.id AND re.id_etudiant=$id
    WHERE m.id NOT IN (SELECT id_module FROM certificats WHERE id_etudiant=$id)
    GROUP BY m.id
");
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Mes Certificats</title>
<link rel="icon" type="image/svg+xml" href="../img/logo.svg">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
@media print {
    .sidebar, .topbar, .no-print { display:none !important; }
    .main-content { margin-left:0 !important; }
    .certificat-card { break-inside: avoid; page-break-inside: avoid; }
}
</style>
</head>
<body>
<div class="layout">
<?php sidebar('etudiant','certificats'); ?>
<main class="main-content">
    <div class="page-header">
        <div><h1>🏆 Mes Certificats</h1><p>Vos modules validés et certificats obtenus</p></div>
    </div>

    <?php
    $certs = [];
    while ($c = $certificats->fetch_assoc()) $certs[] = $c;
    if (!empty($certs)):
    ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:24px;margin-bottom:32px;">
    <?php foreach ($certs as $c): ?>
    <div class="certificat-card">
        <div style="font-size:.75rem;color:#92400e;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Certificat de validation</div>
        <div class="certificat-title"><?=sanitize($c['module_titre'])?></div>
        <p style="margin-top:10px;color:#92400e;font-size:.9rem;">Décerné à <strong><?=sanitize($_SESSION['nom'])?></strong></p>
        <p style="color:#b45309;font-size:.85rem;margin-top:4px;">par <?=sanitize($c['promoteur_nom'])?></p>
        <div style="margin-top:16px;padding-top:16px;border-top:1px dashed #f59e0b;">
            <div class="certificat-code"><?=$c['code_unique']?></div>
            <p style="font-size:.78rem;color:#92400e;margin-top:8px;">Obtenu le <?=date('d/m/Y', strtotime($c['date_obtention']))?></p>
        </div>
        <button onclick="window.print()" class="btn btn-sm no-print" style="margin-top:16px;background:#f59e0b;color:white;">🖨️ Imprimer</button>
    </div>
    <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="icon">🏆</div>
                <h3>Aucun certificat pour l'instant</h3>
                <p>Complétez toutes les évaluations d'un module avec une bonne moyenne pour obtenir votre certificat.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modules en progression -->
    <?php
    $en_cours = [];
    while ($m = $modules_en_cours->fetch_assoc()) $en_cours[] = $m;
    if (!empty($en_cours)):
    ?>
    <div class="card">
        <div class="card-header"><h2>📦 Modules en progression</h2></div>
        <div class="card-body" style="display:grid;gap:16px;">
        <?php foreach ($en_cours as $m): ?>
        <div style="border:1px solid var(--border);border-radius:10px;padding:18px;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <div>
                    <strong><?=sanitize($m['titre'])?></strong>
                    <p style="font-size:.82rem;color:var(--text-muted);margin-top:4px;">
                        📝 <?=$m['nb_passes']?>/<?=$m['nb_evals']?> évaluations · Seuil : <?=$m['note_validation']?>%
                    </p>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:1.5rem;font-weight:800;color:<?=($m['moy']??0)>=$m['note_validation']?'var(--success)':'var(--primary)'?>"><?=$m['moy']??'—'?>%</div>
                    <div style="font-size:.78rem;color:var(--text-muted);">Moyenne actuelle</div>
                </div>
            </div>
            <div class="progress-wrap" style="margin-top:12px;">
                <div class="progress-bar">
                    <div class="progress-fill" data-width="<?=min(100,round(($m['nb_evals']>0?$m['nb_passes']/$m['nb_evals']:0)*100))?>" style="width:0%"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>
</div>
<?php require_once '../includes/footer.php'; pageFooter(); ?>
<script src="../js/app.js"></script>
</body></html>
