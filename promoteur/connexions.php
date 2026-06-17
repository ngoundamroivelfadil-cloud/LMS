<?php
require_once '../includes/session.php'; verifierRole('promoteur');
require_once '../config/database.php'; require_once '../includes/sidebar.php'; require_once '../includes/head.php';
$conns=$conn->query("SELECT c.*,u.nom,u.role FROM connexions c JOIN utilisateurs u ON c.id_utilisateur=u.id ORDER BY c.date_connexion DESC LIMIT 100");
htmlHead('Historique connexions');
?>
<div class="layout"><?php sidebar('promoteur','connexions');?>
<main class="main-content">
<div class="page-header"><div><h1>Historique des connexions</h1><p>100 dernieres connexions sur la plateforme</p></div></div>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Utilisateur</th><th>Role</th><th>Adresse IP</th><th>Navigateur</th><th>Date</th></tr></thead>
<tbody>
<?php while($c=$conns->fetch_assoc()):?>
<tr>
<td><strong><?=sanitize($c['nom'])?></strong></td>
<td><span class="badge badge-<?=$c['role']?>"><?=ucfirst($c['role'])?></span></td>
<td><code style="font-size:.78rem"><?=sanitize($c['ip'])?></code></td>
<td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.78rem;color:#64748b"><?=sanitize(substr($c['navigateur'],0,60))?></td>
<td><?=date('d/m/Y H:i',strtotime($c['date_connexion']))?></td>
</tr>
<?php endwhile;?>
</tbody></table></div></div>
</main></div>
<?php htmlFoot();?>
