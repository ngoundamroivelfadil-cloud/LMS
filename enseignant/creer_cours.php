<?php
require_once '../includes/session.php';
verifierRole('enseignant');
require_once '../config/database.php';
require_once '../includes/sidebar.php';

$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $titre=trim($_POST['titre']);
    $desc=trim($_POST['description']);
    $id_module = !empty($_POST['id_module']) ? intval($_POST['id_module']) : null;
    $id_prof=$_SESSION['user_id'];
    if(empty($titre)){$err="Le titre est obligatoire.";}
    else{
        if($id_module){
            $s=$conn->prepare("INSERT INTO cours(titre,description,id_module,id_enseignant) VALUES(?,?,?,?)");
            $s->bind_param("ssii",$titre,$desc,$id_module,$id_prof);
        } else {
            // Pas de module : on envoie NULL explicitement
            $s=$conn->prepare("INSERT INTO cours(titre,description,id_enseignant) VALUES(?,?,?)");
            $s->bind_param("ssi",$titre,$desc,$id_prof);
        }
        if($s->execute()){header("Location: gerer_cours.php?id=".$conn->insert_id);exit();}
        else $err="Erreur lors de la création : ".$conn->error;
    }
}
$modules=$conn->query("SELECT * FROM modules ORDER BY titre");
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Créer un cours</title>
<link rel="icon" type="image/svg+xml" href="../img/logo.svg">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"></head>
<body>
<div class="layout">
<?php sidebar('enseignant','creer'); ?>
<main class="main-content">
    <div class="page-header">
        <div><h1>Créer un nouveau cours</h1><p>Renseignez les informations du cours</p></div>
        <a href="mes_cours.php" class="btn btn-secondary">Retour</a>
    </div>
    <?php if($err): ?><div class="alert alert-danger"><?=$err?></div><?php endif; ?>
    <div class="card" style="max-width:640px;">
        <div class="card-body">
            <form method="POST">
                <div class="form-group"><label>Titre du cours *</label><input class="form-control" name="titre" required placeholder="Ex: Introduction à Python" value="<?=sanitize($_POST['titre']??'')?>"></div>
                <div class="form-group"><label>Description</label><textarea class="form-control" name="description" placeholder="Décrivez le contenu de ce cours..."><?=sanitize($_POST['description']??'')?></textarea></div>
                <div class="form-group">
                    <label>Module (optionnel)</label>
                    <select class="form-control" name="id_module">
                        <option value="">— Aucun module —</option>
                        <?php while($m=$modules->fetch_assoc()): ?><option value="<?=$m['id']?>"><?=sanitize($m['titre'])?></option><?php endwhile; ?>
                    </select>
                    <small style="color:var(--text-muted)">Le promoteur peut aussi rattacher votre cours à un module plus tard.</small>
                </div>
                <div style="display:flex;gap:10px;margin-top:8px;">
                    <a href="mes_cours.php" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Créer le cours</button>
                </div>
            </form>
        </div>
    </div>
</main>
</div>
<?php require_once '../includes/footer.php'; pageFooter(); ?>
<script src="../js/app.js"></script>
</body></html>
