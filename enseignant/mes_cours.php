<?php
require_once '../includes/session.php';
verifierRole('enseignant');
require_once '../config/database.php';
require_once '../includes/sidebar.php';

$id=$_SESSION['user_id'];
if(isset($_GET['del'])){
    $cid=intval($_GET['del']);
    $conn->query("DELETE FROM cours WHERE id=$cid AND id_enseignant=$id");
    header("Location: mes_cours.php");exit();
}
$cours=$conn->query("
    SELECT c.*,m.titre module_titre,COUNT(DISTINCT l.id) nb_l,COUNT(DISTINCT i.id) nb_i
    FROM cours c LEFT JOIN modules m ON c.id_module=m.id
    LEFT JOIN lecons l ON l.id_cours=c.id
    LEFT JOIN inscriptions i ON i.id_cours=c.id
    WHERE c.id_enseignant=$id GROUP BY c.id ORDER BY c.date_creation DESC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Mes cours</title><link rel="icon" type="image/svg+xml" href="../img/logo.svg">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"></head>
<body>
<div class="layout">
<?php sidebar('enseignant','cours'); ?>
<main class="main-content">
    <div class="page-header"><div><h1>Mes cours</h1><p>Gérez vos cours et leur contenu</p></div><a href="creer_cours.php" class="btn btn-primary">Nouveau cours</a></div>
    <div class="cours-grid">
    <?php
    $colors=['c1','c2','c3','c4','c5'];$i=0;
    while($c=$cours->fetch_assoc()):$ci=$colors[$i%5];$i++;?>
    <div class="cours-card">
        <div class="cours-card-banner <?=$ci?>"></div>
        <div class="cours-card-body">
            <h3><?=sanitize($c['titre'])?></h3>
            <p><?=sanitize($c['description']??'Aucune description')?></p>
            <div class="cours-card-meta">
                <span class="cours-meta-item"><?=$c['nb_l']?> leçons</span>
                <span class="cours-meta-item"><?=$c['nb_i']?> inscrits</span>
            </div>
            <?php if($c['module_titre']): ?><div style="margin-top:8px;"><span class="badge badge-enseignant">Module : <?=sanitize($c['module_titre'])?></span></div><?php endif; ?>
        </div>
        <div class="cours-card-footer">
            <a href="gerer_cours.php?id=<?=$c['id']?>" class="btn btn-primary btn-sm">Gérer</a>
            <button class="btn btn-danger btn-sm" onclick="confirmDelete('Supprimer ce cours ?','?del=<?=$c['id']?>')">Supprimer</button>
        </div>
    </div>
    <?php endwhile; if($i===0): ?>
    <div class="empty-state" style="grid-column:1/-1;"><div class="icon"></div><h3>Aucun cours</h3><p>Créez votre premier cours.</p><a href="creer_cours.php" class="btn btn-primary" style="margin-top:16px;">Créer un cours</a></div>
    <?php endif; ?>
    </div>
</main>
</div>
<?php require_once '../includes/footer.php'; pageFooter(); ?>
<script src="../js/app.js"></script>
</body></html>
