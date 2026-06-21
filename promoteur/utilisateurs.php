<?php
require_once '../includes/session.php'; verifierRole('promoteur');
require_once '../config/database.php'; require_once '../includes/sidebar.php'; require_once '../includes/head.php';
$msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nom=trim($_POST['nom']); $email=trim($_POST['email']); $mdp=password_hash($_POST['mot_de_passe'],PASSWORD_DEFAULT); $role=$_POST['role'];
    $s=$conn->prepare("INSERT INTO utilisateurs(nom,email,mot_de_passe,role) VALUES(?,?,?,?)");
    $s->bind_param("ssss",$nom,$email,$mdp,$role);
    if($s->execute())$msg="Utilisateur cree !"; else $err="Email deja utilise.";
}
if(isset($_GET['del'])){ $id=intval($_GET['del']); if($id!==$_SESSION['user_id']){$conn->query("DELETE FROM utilisateurs WHERE id=$id");$msg="Supprime.";} }
$users=$conn->query("SELECT * FROM utilisateurs ORDER BY date_inscription DESC");
htmlHead('Utilisateurs');
?>
<div class="layout"><?php sidebar('promoteur','utilisateurs'); ?>
<main class="main-content">
<div class="page-header"><div><h1>Utilisateurs</h1><p>Gerez les comptes de la plateforme</p></div><button class="btn btn-primary" onclick="openModal('modalUser')"><i class="fa-solid fa-plus"></i> Ajouter</button></div>
<?php if($msg):?><div class="alert alert-success"><i class="fa-solid fa-check"></i><?=$msg?></div><?php endif;?>
<?php if($err):?><div class="alert alert-danger"><i class="fa-solid fa-xmark"></i><?=$err?></div><?php endif;?>
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>#</th><th>Nom</th><th>Email</th><th>Role</th><th>Inscription</th><th>Actions</th></tr></thead>
<tbody>
<?php while($u=$users->fetch_assoc()): ?>
<tr>
    <td><?=$u['id']?></td>
    <td><strong><?=sanitize($u['nom'])?></strong></td>
    <td><?=sanitize($u['email'])?></td>
    <td><span class="badge badge-<?=$u['role']?>"><?=ucfirst($u['role'])?></span></td>
    <td><?=date('d/m/Y',strtotime($u['date_inscription']))?></td>
    <td><?php if($u['id']!==$_SESSION['user_id']):?><button class="btn btn-xs btn-danger" onclick="confirmDelete('Supprimer ?','?del=<?=$u['id']?>')"><i class="fa-solid fa-trash"></i></button><?php else:?><span style="color:#64748b;font-size:.75rem">Vous</span><?php endif;?></td>
</tr>
<?php endwhile;?>
</tbody></table></div></div>
</main></div>
<div class="modal-overlay" id="modalUser"><div class="modal">
<div class="modal-header"><h3><i class="fa-solid fa-user-plus"></i> Ajouter</h3><button class="modal-close" onclick="closeModal('modalUser')"><i class="fa-solid fa-xmark"></i></button></div>
<form method="POST">
<div class="form-group"><label>Nom</label><input class="form-control" name="nom" required placeholder="Nom complet"></div>
<div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required></div>
<div class="form-group"><label>Mot de passe</label><input class="form-control" type="password" name="mot_de_passe" required></div>
<div class="form-group"><label>Role</label><select class="form-control" name="role"><option value="etudiant">Etudiant</option><option value="enseignant">Enseignant</option><option value="promoteur">Promoteur</option></select></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalUser')">Annuler</button><button type="submit" class="btn btn-primary">Ajouter</button></div>
</form></div></div>
<?php htmlFoot();?>
