<?php
require_once '../includes/session.php'; verifierRole('promoteur');
require_once '../config/database.php'; require_once '../includes/sidebar.php'; require_once '../includes/head.php';
$classement=$conn->query("SELECT u.id,u.nom,u.avatar,COUNT(DISTINCT i.id_cours) nb_cours,COUNT(DISTINCT re.id) nb_evals,ROUND(AVG(re.pourcentage),1) moy,COUNT(DISTINCT cert.id) nb_cert FROM utilisateurs u LEFT JOIN inscriptions i ON i.id_etudiant=u.id LEFT JOIN resultats_evaluations re ON re.id_etudiant=u.id LEFT JOIN certificats cert ON cert.id_etudiant=u.id WHERE u.role='etudiant' GROUP BY u.id ORDER BY moy DESC,nb_cert DESC,nb_evals DESC");
htmlHead('Classement');
?>
<div class="layout"><?php sidebar('promoteur','classement');?>
<main class="main-content">
<div class="page-header"><div><h1>Classement des etudiants</h1><p>Classement base sur la moyenne des evaluations</p></div></div>
<div class="card">
<div class="card-header"><h2><i class="fa-solid fa-ranking-star"></i> Top etudiants</h2></div>
<?php $rank=0; while($u=$classement->fetch_assoc()): $rank++; $cls=$rank===1?'gold':($rank===2?'silver':($rank===3?'bronze':'')); ?>
<div class="rank-item">
    <div class="rank-num <?=$cls?>"><?php if($rank<=3):?><i class="fa-solid fa-trophy"></i><?php else:?><?=$rank?><?php endif;?></div>
    <div class="rank-avatar"><?=initiale($u['nom'])?></div>
    <div class="rank-info">
        <div class="rank-name"><?=sanitize($u['nom'])?></div>
        <div class="rank-sub"><?=$u['nb_cours']?> cours · <?=$u['nb_evals']?> eval. · <?=$u['nb_cert']?> cert.</div>
    </div>
    <div class="rank-score"><?=$u['moy']??'—'?>%</div>
</div>
<?php endwhile;?>
</div>
</main></div>
<?php htmlFoot();?>
