<?php
require_once '../includes/session.php'; verifierRole('promoteur');
require_once '../config/database.php'; require_once '../includes/sidebar.php'; require_once '../includes/head.php';
$id_m=intval($_GET['id']??0); $id_prom=$_SESSION['user_id'];
$s=$conn->prepare("SELECT * FROM modules WHERE id=? AND id_promoteur=?"); $s->bind_param("ii",$id_m,$id_prom); $s->execute();
$module=$s->get_result()->fetch_assoc(); if(!$module){header("Location: modules.php");exit();}
$cours=$conn->query("SELECT c.*,u.nom ens,COUNT(DISTINCT l.id) nb_l,COUNT(DISTINCT i.id) nb_i FROM cours c LEFT JOIN utilisateurs u ON c.id_enseignant=u.id LEFT JOIN lecons l ON l.id_cours=c.id LEFT JOIN inscriptions i ON i.id_cours=c.id WHERE c.id_module=$id_m GROUP BY c.id");
$certs=$conn->query("SELECT u.nom,u.email,cert.code_unique,cert.date_obtention FROM certificats cert JOIN utilisateurs u ON cert.id_etudiant=u.id WHERE cert.id_module=$id_m ORDER BY cert.date_obtention DESC");
htmlHead('Module '.sanitize($module['titre']));
?>
<div class="layout"><?php sidebar('promoteur','modules');?>
<main class="main-content">
<div class="page-header"><div><h1><?=sanitize($module['titre'])?></h1><p><?=sanitize($module['description']??'')?> · Validation min. <?=$module['note_validation']?>%</p></div><a href="modules.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Retour</a></div>
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
<div class="card"><div class="card-header"><h2><i class="fa-solid fa-book-open"></i> Cours du module</h2></div>
<div class="table-wrap"><table><thead><tr><th>Cours</th><th>Enseignant</th><th>Lecons</th><th>Inscrits</th></tr></thead><tbody>
<?php $ca=[]; while($c=$cours->fetch_assoc())$ca[]=$c;
if(empty($ca)):?><tr><td colspan="4" style="text-align:center;color:#64748b;padding:20px">Aucun cours rattache</td></tr>
<?php else: foreach($ca as $c):?>
<tr><td><strong><?=sanitize($c['titre'])?></strong></td><td><?=sanitize($c['ens'])?></td><td><?=$c['nb_l']?></td><td><?=$c['nb_i']?></td></tr>
<?php endforeach; endif;?>
</tbody></table></div></div>
<div class="card"><div class="card-header"><h2><i class="fa-solid fa-certificate"></i> Certifies</h2></div><div class="card-body">
<?php $ca=[]; while($c=$certs->fetch_assoc())$ca[]=$c;
if(empty($ca)):?><div class="empty-state" style="padding:20px"><div class="empty-icon"><i class="fa-solid fa-certificate"></i></div><p>Aucun certifie</p></div>
<?php else: foreach($ca as $c):?>
<div style="border:1px solid #e2e8f0;border-radius:8px;padding:11px;margin-bottom:9px">
<strong style="font-size:.84rem"><?=sanitize($c['nom'])?></strong>
<p style="font-size:.76rem;color:#64748b"><?=sanitize($c['email'])?></p>
<code style="font-size:.73rem;background:#f1f5f9;padding:2px 6px;border-radius:4px"><?=$c['code_unique']?></code>
<p style="font-size:.72rem;color:#64748b;margin-top:3px"><?=date('d/m/Y',strtotime($c['date_obtention']))?></p>
</div>
<?php endforeach; endif;?>
</div></div>
</div>
</main></div>
<?php htmlFoot();?>
