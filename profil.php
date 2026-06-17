<?php
require_once 'includes/session.php'; if(!estConnecte()){header("Location: index.php");exit();}
require_once 'config/database.php'; require_once 'includes/sidebar.php'; require_once 'includes/head.php';
$id=$_SESSION['user_id']; $role=$_SESSION['role']; $msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nom=trim($_POST['nom']); $bio=trim($_POST['bio']);
    $avatar_nom=$_SESSION['avatar'];
    if(isset($_FILES['avatar'])&&$_FILES['avatar']['error']===0){
        $up=uploadFichier($_FILES['avatar'],UPLOAD_AVATAR,['jpg','jpeg','png','gif','webp']);
        if($up['ok'])$avatar_nom=$up['nom']; else $err=$up['msg'];
    }
    if(!$err){
        $s=$conn->prepare("UPDATE utilisateurs SET nom=?,bio=?,avatar=? WHERE id=?");
        $s->bind_param("sssi",$nom,$bio,$avatar_nom,$id);
        if($s->execute()){ $_SESSION['nom']=$nom; $_SESSION['avatar']=$avatar_nom; $msg="Profil mis a jour !"; }
    }
}
$user=$conn->query("SELECT * FROM utilisateurs WHERE id=$id")->fetch_assoc();
htmlHead('Mon profil', '');
?>
<div class="layout"><?php sidebar($role,'profil','');?>
<main class="main-content">
<div class="profil-header">
    <?php if($user['avatar']):?>
    <img src="<?=sanitize($user['avatar'])?>" class="profil-avatar-big" alt="">
    <?php else:?>
    <div class="profil-avatar-placeholder"><?=initiale($user['nom'])?></div>
    <?php endif;?>
    <div>
        <h1 style="font-size:1.4rem;font-weight:800"><?=sanitize($user['nom'])?></h1>
        <p style="opacity:.8;margin-top:4px"><?=sanitize($user['email'])?></p>
        <span style="background:rgba(255,255,255,.2);padding:3px 10px;border-radius:20px;font-size:.75rem;margin-top:8px;display:inline-block"><?=ucfirst($user['role'])?></span>
    </div>
</div>
<?php if($msg):?><div class="alert alert-success"><i class="fa-solid fa-check"></i><?=$msg?></div><?php endif;?>
<?php if($err):?><div class="alert alert-danger"><i class="fa-solid fa-xmark"></i><?=$err?></div><?php endif;?>
<div class="card" style="max-width:600px"><div class="card-header"><h2><i class="fa-solid fa-user-pen"></i> Modifier mon profil</h2></div>
<div class="card-body"><form method="POST" enctype="multipart/form-data">
<div class="form-group"><label>Photo de profil</label>
<div class="upload-zone" style="position:relative">
<input type="file" name="avatar" accept="image/*" style="opacity:0;position:absolute;inset:0;cursor:pointer;width:100%;height:100%">
<i class="fa-solid fa-camera"></i><p>Cliquez pour changer votre photo (JPG, PNG)</p>
</div></div>
<div class="form-group"><label>Nom complet</label><input class="form-control" name="nom" value="<?=sanitize($user['nom'])?>" required></div>
<div class="form-group"><label>Email</label><input class="form-control" value="<?=sanitize($user['email'])?>" disabled style="opacity:.6"></div>
<div class="form-group"><label>Bio / Presentation</label><textarea class="form-control" name="bio" placeholder="Parlez de vous..."><?=sanitize($user['bio']??'')?></textarea></div>
<button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
</form></div></div>
</main></div>
<?php htmlFoot('');?>
