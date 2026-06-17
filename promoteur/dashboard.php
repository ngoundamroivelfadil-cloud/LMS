<?php
require_once '../includes/session.php'; verifierRole('promoteur');
require_once '../config/database.php'; require_once '../includes/sidebar.php'; require_once '../includes/head.php';
$id=$_SESSION['user_id'];
$nb_modules=$conn->query("SELECT COUNT(*) n FROM modules WHERE id_promoteur=$id")->fetch_assoc()['n'];
$nb_cours=$conn->query("SELECT COUNT(*) n FROM cours")->fetch_assoc()['n'];
$nb_etudiants=$conn->query("SELECT COUNT(*) n FROM utilisateurs WHERE role='etudiant'")->fetch_assoc()['n'];
$nb_certs=$conn->query("SELECT COUNT(*) n FROM certificats cert JOIN modules m ON cert.id_module=m.id WHERE m.id_promoteur=$id")->fetch_assoc()['n'];
$nb_enseignants=$conn->query("SELECT COUNT(*) n FROM utilisateurs WHERE role='enseignant'")->fetch_assoc()['n'];
$modules=$conn->query("SELECT m.*,COUNT(DISTINCT c.id) nb_c,COUNT(DISTINCT cert.id) nb_cert FROM modules m LEFT JOIN cours c ON c.id_module=m.id LEFT JOIN certificats cert ON cert.id_module=m.id WHERE m.id_promoteur=$id GROUP BY m.id ORDER BY m.date_creation DESC LIMIT 5");
htmlHead('Tableau de bord');
?>
<div class="layout">
<?php sidebar('promoteur','dashboard'); ?>
<main class="main-content">
<div class="topbar">
    <div><div class="topbar-title">Tableau de bord</div><div class="topbar-sub">Bienvenue, <?=sanitize($_SESSION['nom'])?></div></div>
    <div class="topbar-actions"><a href="modules.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Nouveau module</a></div>
</div>
<div class="stats-grid">
    <div class="stat-card purple"><div class="stat-icon purple"><i class="fa-solid fa-layer-group"></i></div><div class="stat-info"><h3><?=$nb_modules?></h3><p>Modules</p></div></div>
    <div class="stat-card blue"><div class="stat-icon blue"><i class="fa-solid fa-book-open"></i></div><div class="stat-info"><h3><?=$nb_cours?></h3><p>Cours</p></div></div>
    <div class="stat-card green"><div class="stat-icon green"><i class="fa-solid fa-user-graduate"></i></div><div class="stat-info"><h3><?=$nb_etudiants?></h3><p>Etudiants</p></div></div>
    <div class="stat-card orange"><div class="stat-icon orange"><i class="fa-solid fa-chalkboard-user"></i></div><div class="stat-info"><h3><?=$nb_enseignants?></h3><p>Enseignants</p></div></div>
    <div class="stat-card cyan"><div class="stat-icon cyan"><i class="fa-solid fa-certificate"></i></div><div class="stat-info"><h3><?=$nb_certs?></h3><p>Certificats</p></div></div>
</div>
<div class="card">
    <div class="card-header"><h2><i class="fa-solid fa-layer-group"></i> Mes modules</h2><a href="modules.php" class="btn btn-primary btn-sm">Voir tous</a></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Module</th><th>Note validation</th><th>Cours</th><th>Certificats</th><th>Actions</th></tr></thead>
        <tbody>
        <?php $has=false; while($m=$modules->fetch_assoc()):$has=true; ?>
        <tr>
            <td><strong><?=sanitize($m['titre'])?></strong><br><small style="color:#64748b"><?=sanitize(substr($m['description']??'',0,50))?></small></td>
            <td><span class="badge badge-warning">Min. <?=$m['note_validation']?>%</span></td>
            <td><?=$m['nb_c']?> cours</td>
            <td><?=$m['nb_cert']?></td>
            <td><a href="voir_module.php?id=<?=$m['id']?>" class="btn btn-xs btn-outline"><i class="fa-solid fa-eye"></i></a> <a href="modules.php?edit=<?=$m['id']?>" class="btn btn-xs btn-secondary"><i class="fa-solid fa-pen"></i></a></td>
        </tr>
        <?php endwhile; if(!$has): ?><tr><td colspan="5"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-layer-group"></i></div><h3>Aucun module</h3><p><a href="modules.php" style="color:#4f46e5">Creez votre premier module</a></p></div></td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
</main></div>
<?php htmlFoot(); ?>
