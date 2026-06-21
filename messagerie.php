<?php
require_once 'includes/session.php'; if(!estConnecte()){header("Location: index.php");exit();}
require_once 'config/database.php'; require_once 'includes/sidebar.php'; require_once 'includes/head.php'; require_once 'includes/topbar.php';
$id=$_SESSION['user_id']; $role=$_SESSION['role']; $msg='';
// Envoyer message
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['action'])&&$_POST['action']==='envoyer'){
    verifier_csrf();
    $dest=intval($_POST['id_destinataire']); $sujet=trim($_POST['sujet']); $contenu=trim($_POST['contenu']);
    if($dest&&$sujet&&$contenu){
        $s=$conn->prepare("INSERT INTO messages(id_expediteur,id_destinataire,sujet,contenu) VALUES(?,?,?,?)");
        $s->bind_param("iiss",$id,$dest,$sujet,$contenu); $s->execute(); 
        $msg="Message envoyé !";
        envoyerNotification($conn, $dest, 'message', 'Nouveau message', 'Vous avez reçu un message de ' . $_SESSION['nom'], 'messagerie.php?id=' . $conn->insert_id);
    }
}
// Marquer lu
if(isset($_GET['lire'])){ $mid=intval($_GET['lire']); $conn->query("UPDATE messages SET lu=1 WHERE id=$mid AND id_destinataire=$id"); }
// Message actif
$msg_actif=null;
if(isset($_GET['id'])){ $mid=intval($_GET['id']); $conn->query("UPDATE messages SET lu=1 WHERE id=$mid AND id_destinataire=$id"); $r=$conn->query("SELECT m.*,exp.nom exp_nom,dest.nom dest_nom FROM messages m JOIN utilisateurs exp ON m.id_expediteur=exp.id JOIN utilisateurs dest ON m.id_destinataire=dest.id WHERE m.id=$mid AND (m.id_destinataire=$id OR m.id_expediteur=$id)"); $msg_actif=$r->fetch_assoc(); if(!$msg_actif) { $msg_actif = ['sujet'=>'Erreur', 'contenu'=>'Message introuvable ou accès refusé.', 'exp_nom'=>'Système', 'dest_nom'=>'Vous', 'date_envoi'=>date('Y-m-d H:i:s')]; } }
$recus=$conn->query("SELECT m.*,u.nom exp_nom FROM messages m JOIN utilisateurs u ON m.id_expediteur=u.id WHERE m.id_destinataire=$id ORDER BY m.date_envoi DESC");
$envoyes=$conn->query("SELECT m.*,u.nom dest_nom FROM messages m JOIN utilisateurs u ON m.id_destinataire=u.id WHERE m.id_expediteur=$id ORDER BY m.date_envoi DESC");
$contacts=$conn->query("SELECT id,nom,role FROM utilisateurs WHERE id!=$id ORDER BY nom");
htmlHead('Messagerie', '');
?>
<div class="layout"><?php sidebar($role,'messages','');?>
<main class="main-content">
<?php topbar("Messagerie", "Échangez avec les enseignants et étudiants", ""); ?>
<div class="page-header" style="margin-top:20px;">
    <div><h1>Boîte de réception</h1></div>
    <button class="btn btn-primary" onclick="openModal('modalNvMsg')"><i class="fa-solid fa-pen-to-square"></i> Nouveau message</button>
</div>
<?php if($msg):?><div class="alert alert-success"><i class="fa-solid fa-check"></i><?=$msg?></div><?php endif;?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="card"><div class="card-header"><h2><i class="fa-solid fa-inbox"></i> Messages recus</h2></div>
<ul class="msg-list">
<?php $arr=[]; while($m=$recus->fetch_assoc())$arr[]=$m;
if(empty($arr)):?><li style="padding:20px;text-align:center;color:#64748b">Aucun message recu</li>
<?php else: foreach($arr as $m):?>
<li class="msg-item <?=$m['lu']?'':'unread'?>" onclick="window.location='?id=<?=$m['id']?>'">
    <div class="msg-avatar"><?=initiale($m['exp_nom'])?></div>
    <div class="msg-body"><div class="msg-from"><?=sanitize($m['exp_nom'])?></div>
    <div class="msg-sujet"><?=sanitize($m['sujet'])?></div>
    <div class="msg-preview"><?=sanitize(substr($m['contenu'],0,60))?></div></div>
    <div class="msg-date"><?=date('d/m H:i',strtotime($m['date_envoi']))?><?php if(!$m['lu']):?><br><span class="nav-badge" style="margin-left:0">New</span><?php endif;?></div>
</li>
<?php endforeach; endif;?>
</ul></div>
<div><?php if($msg_actif):?>
<div class="card"><div class="card-header"><h2><i class="fa-solid fa-envelope-open"></i> <?=sanitize($msg_actif['sujet'])?></h2></div>
<div class="card-body">
<div style="display:flex;gap:8px;margin-bottom:12px;font-size:.82rem;color:#64748b"><span><strong>De :</strong> <?=sanitize($msg_actif['exp_nom'])?></span><span>|</span><span><strong>A :</strong> <?=sanitize($msg_actif['dest_nom'])?></span><span>|</span><span><?=date('d/m/Y H:i',strtotime($msg_actif['date_envoi']))?></span></div>
<div style="background:#f8fafc;border-radius:8px;padding:16px;font-size:.88rem;line-height:1.7;white-space:pre-wrap"><?=sanitize($msg_actif['contenu'])?></div>
</div></div>
<?php else:?>
<div class="card"><div class="card-body"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-envelope"></i></div><h3>Selectionnez un message</h3><p>Cliquez sur un message pour le lire</p></div></div></div>
<?php endif;?>
<div class="card" style="margin-top:0"><div class="card-header"><h2><i class="fa-solid fa-paper-plane"></i> Messages envoyes</h2></div>
<ul class="msg-list">
<?php $arr=[]; while($m=$envoyes->fetch_assoc())$arr[]=$m;
if(empty($arr)):?><li style="padding:16px;text-align:center;color:#64748b">Aucun message envoye</li>
<?php else: foreach($arr as $m):?>
<li class="msg-item"><div class="msg-avatar"><?=initiale($m['dest_nom'])?></div>
<div class="msg-body"><div class="msg-from">A : <?=sanitize($m['dest_nom'])?></div>
<div class="msg-sujet"><?=sanitize($m['sujet'])?></div></div>
<div class="msg-date"><?=date('d/m',strtotime($m['date_envoi']))?></div>
</li>
<?php endforeach; endif;?>
</ul></div>
</div>
</div>
</main></div>
<div class="modal-overlay" id="modalNvMsg"><div class="modal">
<div class="modal-header"><h3><i class="fa-solid fa-pen-to-square"></i> Nouveau message</h3><button class="modal-close" onclick="closeModal('modalNvMsg')"><i class="fa-solid fa-xmark"></i></button></div>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="envoyer">
<div class="form-group"><label>Destinataire</label>
<?php
$contacts_arr = [];
$contacts_check = $conn->query("SELECT id,nom,role FROM utilisateurs WHERE id!=$id ORDER BY nom");
while($c=$contacts_check->fetch_assoc()) $contacts_arr[] = $c;
?>
<select class="form-control" name="id_destinataire" required>
<option value="">-- Choisir --</option>
<?php foreach($contacts_arr as $c):?><option value="<?=$c['id']?>"><?=sanitize($c['nom'])?> (<?=ucfirst($c['role'])?>)</option><?php endforeach;?>
</select>
<?php if(empty($contacts_arr)): ?>
<small style="color:#ef4444;display:block;margin-top:6px;"><i class="fa-solid fa-circle-info"></i> Aucun autre utilisateur n'est encore inscrit sur la plateforme.</small>
<?php endif; ?>
</div>
<div class="form-group"><label>Sujet</label><input class="form-control" name="sujet" required placeholder="Sujet du message"></div>
<div class="form-group"><label>Message</label><textarea class="form-control" name="contenu" required placeholder="Redigez votre message..." style="min-height:120px"></textarea></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalNvMsg')">Annuler</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Envoyer</button></div>
</form></div></div>
<?php htmlFoot('');?>
