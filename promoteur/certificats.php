<?php
require_once '../includes/session.php'; verifierRole('promoteur');
require_once '../config/database.php'; require_once '../includes/sidebar.php'; require_once '../includes/head.php';
$id_prom=$_SESSION['user_id'];
// Auto-génération des certificats
$mods=$conn->query("SELECT * FROM modules WHERE id_promoteur=$id_prom");
while($m=$mods->fetch_assoc()){
    $id_m=$m['id']; $note_min=$m['note_validation'];
    $nb_c=$conn->query("SELECT COUNT(*) n FROM cours WHERE id_module=$id_m")->fetch_assoc()['n'];
    if(!$nb_c) continue;
    $ets=$conn->query("SELECT DISTINCT i.id_etudiant FROM inscriptions i JOIN cours c ON i.id_cours=c.id WHERE c.id_module=$id_m");
    while($et=$ets->fetch_assoc()){
        $id_et=$et['id_etudiant'];
        if($conn->query("SELECT id FROM certificats WHERE id_etudiant=$id_et AND id_module=$id_m")->num_rows) continue;
        $avg=$conn->query("SELECT AVG(re.pourcentage) avg,COUNT(DISTINCT re.id_evaluation) done,COUNT(DISTINCT ev.id) total FROM cours c JOIN lecons l ON l.id_cours=c.id JOIN evaluations ev ON ev.id_lecon=l.id LEFT JOIN resultats_evaluations re ON re.id_evaluation=ev.id AND re.id_etudiant=$id_et WHERE c.id_module=$id_m")->fetch_assoc();
        if(!$avg['total']||$avg['done']<$avg['total']) continue;
        if($avg['avg']>=$note_min){ $code=strtoupper(substr(md5($id_et.$id_m.time()),0,12)); $conn->query("INSERT IGNORE INTO certificats(id_etudiant,id_module,code_unique) VALUES($id_et,$id_m,'$code')"); }
    }
}
$certs=$conn->query("SELECT cert.*,u.nom nom_et,u.email,m.titre titre_m FROM certificats cert JOIN utilisateurs u ON cert.id_etudiant=u.id JOIN modules m ON cert.id_module=m.id WHERE m.id_promoteur=$id_prom ORDER BY cert.date_obtention DESC");
htmlHead('Certificats');
?>
<div class="layout"><?php sidebar('promoteur','certificats');?>
<main class="main-content">
<div class="page-header"><div><h1>Certificats delivres</h1><p>Generes automatiquement a la validation d'un module</p></div></div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Etudiant</th><th>Module valide</th><th>Code</th><th>Date</th></tr></thead>
<tbody>
<?php $arr=[]; while($c=$certs->fetch_assoc())$arr[]=$c;
if(empty($arr)):?><tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-certificate"></i></div><h3>Aucun certificat</h3><p>Les certificats apparaissent ici quand des etudiants valident un module.</p></div></td></tr>
<?php else: foreach($arr as $c):?>
<tr>
<td><strong><?=sanitize($c['nom_et'])?></strong><br><small style="color:#64748b"><?=sanitize($c['email'])?></small></td>
<td><?=sanitize($c['titre_m'])?></td>
<td><code style="background:#f1f5f9;padding:3px 7px;border-radius:5px;font-size:.78rem"><?=$c['code_unique']?></code></td>
<td><?=date('d/m/Y H:i',strtotime($c['date_obtention']))?></td>
</tr>
<?php endforeach; endif;?>
</tbody></table></div></div>
</main></div>
<?php htmlFoot();?>
