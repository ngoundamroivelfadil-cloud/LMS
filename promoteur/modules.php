<?php
require_once '../includes/session.php'; verifierRole('promoteur');
require_once '../config/database.php'; require_once '../includes/sidebar.php'; require_once '../includes/head.php';
$id_prom=$_SESSION['user_id']; $msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['action'])){
    $titre=trim($_POST['titre']); $desc=trim($_POST['description']); $note=intval($_POST['note_validation']??60);
    if($_POST['action']==='ajouter'){
        $s=$conn->prepare("INSERT INTO modules(titre,description,id_promoteur,note_validation) VALUES(?,?,?,?)");
        $s->bind_param("ssii",$titre,$desc,$id_prom,$note); $s->execute(); $msg="Module cree !";
    }elseif($_POST['action']==='modifier'){
        $mid=intval($_POST['module_id']);
        $s=$conn->prepare("UPDATE modules SET titre=?,description=?,note_validation=? WHERE id=? AND id_promoteur=?");
        $s->bind_param("ssiii",$titre,$desc,$note,$mid,$id_prom); $s->execute(); $msg="Module modifie.";
    }
}
if(isset($_GET['delete'])){ $conn->query("DELETE FROM modules WHERE id=".intval($_GET['delete'])." AND id_promoteur=$id_prom"); $msg="Module supprime."; }
$edit_m=null;
if(isset($_GET['edit'])){ $r=$conn->query("SELECT * FROM modules WHERE id=".intval($_GET['edit'])." AND id_promoteur=$id_prom"); $edit_m=$r->fetch_assoc(); }
$modules=$conn->query("SELECT m.*,COUNT(DISTINCT c.id) nb_c,COUNT(DISTINCT i.id_etudiant) nb_i,COUNT(DISTINCT cert.id) nb_cert FROM modules m LEFT JOIN cours c ON c.id_module=m.id LEFT JOIN inscriptions i ON i.id_cours=c.id LEFT JOIN certificats cert ON cert.id_module=m.id WHERE m.id_promoteur=$id_prom GROUP BY m.id ORDER BY m.date_creation DESC");
$cours_libres=$conn->query("SELECT c.*,u.nom enseignant FROM cours c JOIN utilisateurs u ON c.id_enseignant=u.id WHERE c.id_module IS NULL");
htmlHead('Modules');
?>
<div class="layout"><?php sidebar('promoteur','modules'); ?>
<main class="main-content">
<div class="page-header">
    <div><h1>Gestion des Modules</h1><p>Organisez les cours en modules</p></div>
    <button class="btn btn-primary" onclick="openModal('modalModule')"><i class="fa-solid fa-plus"></i> Creer un module</button>
</div>
<?php if($msg): ?><div class="alert alert-success"><i class="fa-solid fa-check"></i><?=$msg?></div><?php endif; ?>
<?php if($edit_m): ?>
<div class="card" style="border:2px solid #4f46e5;margin-bottom:20px;">
    <div class="card-header"><h2><i class="fa-solid fa-pen"></i> Modifier : <?=sanitize($edit_m['titre'])?></h2></div>
    <div class="card-body"><form method="POST" class="form-grid">
        <input type="hidden" name="action" value="modifier"><input type="hidden" name="module_id" value="<?=$edit_m['id']?>">
        <div class="full form-group"><label>Titre</label><input class="form-control" name="titre" value="<?=sanitize($edit_m['titre'])?>" required></div>
        <div class="full form-group"><label>Description</label><textarea class="form-control" name="description"><?=sanitize($edit_m['description']??'')?></textarea></div>
        <div class="form-group"><label>Note de validation (%)</label><input class="form-control" type="number" name="note_validation" value="<?=$edit_m['note_validation']?>" min="0" max="100"></div>
        <div style="display:flex;align-items:flex-end;gap:8px;"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button><a href="modules.php" class="btn btn-secondary">Annuler</a></div>
    </form></div>
</div>
<?php endif; ?>
<div class="cours-grid">
<?php $colors=['c1','c2','c3','c4','c5'];$icons=['fa-layer-group','fa-graduation-cap','fa-lightbulb','fa-flask','fa-compass'];$i=0;
while($m=$modules->fetch_assoc()):$ci=$colors[$i%5];$ico=$icons[$i%5];$i++; ?>
<div class="cours-card">
    <div class="cours-card-banner <?=$ci?>"><i class="fa-solid <?=$ico?>"></i></div>
    <div class="cours-card-body">
        <h3><?=sanitize($m['titre'])?></h3>
        <p><?=sanitize($m['description']??'Aucune description')?></p>
        <div class="cours-card-meta">
            <span class="cours-meta-item"><i class="fa-solid fa-book-open"></i><?=$m['nb_c']?> cours</span>
            <span class="cours-meta-item"><i class="fa-solid fa-users"></i><?=$m['nb_i']?> inscrits</span>
            <span class="cours-meta-item"><i class="fa-solid fa-certificate"></i><?=$m['nb_cert']?> certifies</span>
        </div>
        <div style="margin-top:8px;"><span class="badge badge-warning">Min. <?=$m['note_validation']?>%</span></div>
    </div>
    <div class="cours-card-footer">
        <a href="voir_module.php?id=<?=$m['id']?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-eye"></i> Details</a>
        <div style="display:flex;gap:5px;">
            <a href="?edit=<?=$m['id']?>" class="btn btn-sm btn-secondary"><i class="fa-solid fa-pen"></i></a>
            <button class="btn btn-sm btn-danger" onclick="confirmDelete('Supprimer ce module ?','?delete=<?=$m['id']?>')"><i class="fa-solid fa-trash"></i></button>
        </div>
    </div>
</div>
<?php endwhile; if($i===0): ?>
<div class="empty-state" style="grid-column:1/-1"><div class="empty-icon"><i class="fa-solid fa-layer-group"></i></div><h3>Aucun module</h3><p>Creez votre premier module.</p><button class="btn btn-primary" onclick="openModal('modalModule')" style="margin-top:14px"><i class="fa-solid fa-plus"></i> Creer</button></div>
<?php endif; ?>
</div>
<?php $libres=[]; while($c=$cours_libres->fetch_assoc())$libres[]=$c; if(!empty($libres)): ?>
<div class="card" style="margin-top:20px">
    <div class="card-header"><h2><i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b"></i> Cours sans module (<?=count($libres)?>)</h2></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Cours</th><th>Enseignant</th><th>Rattacher</th></tr></thead>
        <tbody>
        <?php foreach($libres as $c): ?>
        <tr>
            <td><strong><?=sanitize($c['titre'])?></strong></td>
            <td><?=sanitize($c['enseignant'])?></td>
            <td><form method="POST" action="rattacher_cours.php" style="display:flex;gap:6px">
                <input type="hidden" name="id_cours" value="<?=$c['id']?>">
                <select name="id_module" class="form-control" style="padding:5px 9px">
                    <?php $mods=$conn->query("SELECT id,titre FROM modules WHERE id_promoteur=$id_prom");
                    while($mo=$mods->fetch_assoc()): ?><option value="<?=$mo['id']?>"><?=sanitize($mo['titre'])?></option><?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-link"></i> Rattacher</button>
            </form></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>
</main></div>
<div class="modal-overlay" id="modalModule">
    <div class="modal"><div class="modal-header"><h3><i class="fa-solid fa-plus"></i> Creer un module</h3><button class="modal-close" onclick="closeModal('modalModule')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST"><input type="hidden" name="action" value="ajouter">
        <div class="form-group"><label>Titre *</label><input class="form-control" name="titre" required placeholder="Ex: Developpement Web"></div>
        <div class="form-group"><label>Description</label><textarea class="form-control" name="description" placeholder="Decrivez ce module..."></textarea></div>
        <div class="form-group"><label>Note minimale de validation (%)</label><input class="form-control" type="number" name="note_validation" value="60" min="0" max="100"><small style="color:#64748b">L'etudiant doit avoir cette moyenne pour obtenir le certificat.</small></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalModule')">Annuler</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Creer</button></div>
    </form></div>
</div>
<?php htmlFoot(); ?>
