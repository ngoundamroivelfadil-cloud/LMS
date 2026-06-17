<?php
require_once '../includes/session.php'; verifierRole('promoteur');
require_once '../config/database.php'; require_once '../includes/sidebar.php'; require_once '../includes/head.php';
$id=$_SESSION['user_id'];
$s=['modules'=>$conn->query("SELECT COUNT(*) n FROM modules WHERE id_promoteur=$id")->fetch_assoc()['n'],
    'cours'=>$conn->query("SELECT COUNT(*) n FROM cours")->fetch_assoc()['n'],
    'etudiants'=>$conn->query("SELECT COUNT(*) n FROM utilisateurs WHERE role='etudiant'")->fetch_assoc()['n'],
    'enseignants'=>$conn->query("SELECT COUNT(*) n FROM utilisateurs WHERE role='enseignant'")->fetch_assoc()['n'],
    'certs'=>$conn->query("SELECT COUNT(*) n FROM certificats cert JOIN modules m ON cert.id_module=m.id WHERE m.id_promoteur=$id")->fetch_assoc()['n']];
$top=$conn->query("SELECT c.titre,COUNT(i.id) nb FROM cours c LEFT JOIN inscriptions i ON i.id_cours=c.id GROUP BY c.id ORDER BY nb DESC LIMIT 5");
$mods=$conn->query("SELECT m.titre,m.note_validation,COUNT(DISTINCT c.id) nb_c,COUNT(DISTINCT i.id_etudiant) nb_i,ROUND(AVG(re.pourcentage),1) moy,COUNT(DISTINCT cert.id) nb_cert FROM modules m LEFT JOIN cours c ON c.id_module=m.id LEFT JOIN inscriptions i ON i.id_cours=c.id LEFT JOIN lecons l ON l.id_cours=c.id LEFT JOIN evaluations ev ON ev.id_lecon=l.id LEFT JOIN resultats_evaluations re ON re.id_evaluation=ev.id LEFT JOIN certificats cert ON cert.id_module=m.id WHERE m.id_promoteur=$id GROUP BY m.id");
htmlHead('Statistiques');
?>
<div class="layout"><?php sidebar('promoteur','stats');?>
<main class="main-content">
<div class="page-header"><div><h1>Statistiques</h1><p>Vue d'ensemble de la plateforme</p></div></div>
<div class="stats-grid">
<div class="stat-card purple"><div class="stat-icon purple"><i class="fa-solid fa-layer-group"></i></div><div class="stat-info"><h3><?=$s['modules']?></h3><p>Modules</p></div></div>
<div class="stat-card blue"><div class="stat-icon blue"><i class="fa-solid fa-book-open"></i></div><div class="stat-info"><h3><?=$s['cours']?></h3><p>Cours</p></div></div>
<div class="stat-card green"><div class="stat-icon green"><i class="fa-solid fa-user-graduate"></i></div><div class="stat-info"><h3><?=$s['etudiants']?></h3><p>Etudiants</p></div></div>
<div class="stat-card orange"><div class="stat-icon orange"><i class="fa-solid fa-chalkboard-user"></i></div><div class="stat-info"><h3><?=$s['enseignants']?></h3><p>Enseignants</p></div></div>
<div class="stat-card cyan"><div class="stat-icon cyan"><i class="fa-solid fa-certificate"></i></div><div class="stat-info"><h3><?=$s['certs']?></h3><p>Certificats</p></div></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card"><div class="card-header"><h2><i class="fa-solid fa-fire" style="color:#ef4444"></i> Top 5 cours</h2></div><div class="card-body">
<?php $top_arr=[];$max=1; while($r=$top->fetch_assoc()){$top_arr[]=$r;if($r['nb']>$max)$max=$r['nb'];}
foreach($top_arr as $r): $pct=$max>0?round(($r['nb']/$max)*100):0; ?>
<div style="margin-bottom:14px"><div style="display:flex;justify-content:space-between;margin-bottom:4px"><span style="font-size:.82rem;font-weight:600"><?=sanitize($r['titre'])?></span><span style="font-size:.78rem;color:#64748b"><?=$r['nb']?> inscrit(s)</span></div>
<div class="progress-bar"><div class="progress-fill" data-width="<?=$pct?>" style="width:0%"></div></div></div>
<?php endforeach; if(empty($top_arr)):?><p style="color:#64748b;text-align:center">Aucune donnee</p><?php endif;?>
</div></div>
<div class="card"><div class="card-header"><h2><i class="fa-solid fa-chart-bar"></i> Par module</h2></div><div class="table-wrap"><table>
<thead><tr><th>Module</th><th>Etudiants</th><th>Moy.</th><th>Certs</th></tr></thead>
<tbody>
<?php $ms=[]; while($m=$mods->fetch_assoc())$ms[]=$m;
if(empty($ms)):?><tr><td colspan="4" style="text-align:center;color:#64748b;padding:20px">Aucun module</td></tr>
<?php else: foreach($ms as $m):?>
<tr><td><strong><?=sanitize($m['titre'])?></strong></td><td><?=$m['nb_i']?></td>
<td><?php if($m['moy']):?><span style="color:<?=$m['moy']>=$m['note_validation']?'#10b981':'#ef4444'?>;font-weight:700"><?=$m['moy']?>%</span><?php else:?><span style="color:#64748b">—</span><?php endif;?></td>
<td><?=$m['nb_cert']?></td></tr>
<?php endforeach; endif;?>
</tbody></table></div></div>
</div>
</main></div>
<?php htmlFoot();?>
