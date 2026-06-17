<?php
require_once '../includes/session.php';
verifierRole('enseignant');
require_once '../config/database.php';
require_once '../includes/sidebar.php';

$id_cours = intval($_GET['id']??0);
$id_prof  = $_SESSION['user_id'];

$s=$conn->prepare("SELECT c.*,m.titre module_titre FROM cours c LEFT JOIN modules m ON c.id_module=m.id WHERE c.id=? AND c.id_enseignant=?");
$s->bind_param("ii",$id_cours,$id_prof);$s->execute();
$cours=$s->get_result()->fetch_assoc();
if(!$cours){header("Location: mes_cours.php");exit();}

$msg='';$err='';

/* ---- AJOUTER LEÇON ---- */
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')!==''){
    $action=$_POST['action'];

    if($action==='add_lecon'){
        $titre=trim($_POST['titre_lecon']);
        $desc=trim($_POST['desc_lecon']??'');
        $type=$_POST['type_contenu'];
        $ordre=intval($_POST['ordre']??1);
        $pdf_nom=null;$vid_url=null;$vid_fic=null;

        if($type==='pdf' && isset($_FILES['fichier_pdf']) && $_FILES['fichier_pdf']['error']===0){
            $up=uploadFichier($_FILES['fichier_pdf'],UPLOAD_PDF,['pdf']);
            if(!$up['ok']){$err=$up['msg'];}else{$pdf_nom=$up['nom'];}
        }elseif($type==='video_url'){
            $vid_url=trim($_POST['video_url']??'');
        }elseif($type==='video_fichier' && isset($_FILES['video_fichier']) && $_FILES['video_fichier']['error']===0){
            $up=uploadFichier($_FILES['video_fichier'],UPLOAD_VIDEO,['mp4','webm','ogg'],209715200);
            if(!$up['ok']){$err=$up['msg'];}else{$vid_fic=$up['nom'];}
        }
        if(!$err){
            $s=$conn->prepare("INSERT INTO lecons(titre,description,type_contenu,fichier_pdf,video_url,video_fichier,ordre,id_cours) VALUES(?,?,?,?,?,?,?,?)");
            $s->bind_param("ssssssii",$titre,$desc,$type,$pdf_nom,$vid_url,$vid_fic,$ordre,$id_cours);
            if($s->execute()) $msg="Leçon ajoutée !"; else $err="Erreur ajout leçon.";
        }
    }

    if($action==='del_lecon'){
        $lid=intval($_POST['lid']);
        // Note: les fichiers sont maintenant stockes sur Cloudinary, pas besoin de suppression locale
        $conn->query("DELETE FROM lecons WHERE id=$lid AND id_cours=$id_cours");
        $msg="Leçon supprimée.";
    }

    if($action==='add_eval'){
        $lid=intval($_POST['id_lecon']);
        $titre_ev=trim($_POST['titre_eval']);
        $duree=intval($_POST['duree']??30);
        $note_pass=intval($_POST['note_passage']??50);
        // Vérifier si une évaluation existe déjà pour cette leçon
        $existing = $conn->query("SELECT id FROM evaluations WHERE id_lecon=$lid LIMIT 1")->fetch_assoc();
        if($existing){
            $err="Cette leçon a déjà une évaluation. Supprimez-la d'abord pour en créer une nouvelle.";
        } else {
            $s=$conn->prepare("INSERT INTO evaluations(titre,id_lecon,duree_minutes,note_passage) VALUES(?,?,?,?)");
            $s->bind_param("siii",$titre_ev,$lid,$duree,$note_pass);
            if($s->execute()) $msg="Évaluation créée !";
            else $err="Erreur lors de la création de l'évaluation.";
        }
    }

    if($action==='add_question'){
        $id_eval=intval($_POST['id_eval']);
        $type_q=$_POST['type_question'];
        $question=trim($_POST['question']);
        $points=intval($_POST['points']??1);
        $ra=trim($_POST['rep_a']??'');$rb=trim($_POST['rep_b']??'');
        $rc=trim($_POST['rep_c']??'');$rd=trim($_POST['rep_d']??'');
        $br=($_POST['bonne_rep']??null);
        $s=$conn->prepare("INSERT INTO questions(id_evaluation,type,question,reponse_a,reponse_b,reponse_c,reponse_d,bonne_reponse,points) VALUES(?,?,?,?,?,?,?,?,?)");
        $s->bind_param("isssssssi",$id_eval,$type_q,$question,$ra,$rb,$rc,$rd,$br,$points);
        $s->execute(); $msg="Question ajoutée !";
    }

    if($action==='del_question'){
        $conn->query("DELETE FROM questions WHERE id=".intval($_POST['qid']));
        $msg="Question supprimée.";
    }
}

$lecons=$conn->query("SELECT l.*,(SELECT COUNT(*) FROM evaluations WHERE id_lecon=l.id) has_eval FROM lecons l WHERE l.id_cours=$id_cours ORDER BY l.ordre ASC");
$lecons_arr=[];
while($l=$lecons->fetch_assoc()) $lecons_arr[]=$l;

$inscrits=$conn->query("SELECT u.nom,u.email,i.date_inscription FROM inscriptions i JOIN utilisateurs u ON i.id_etudiant=u.id WHERE i.id_cours=$id_cours");
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Gérer: <?=sanitize($cours['titre'])?></title>
<link rel="icon" type="image/svg+xml" href="../img/logo.svg">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.lecon-row{background:#fff;border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:12px;}
.lecon-row-header{display:flex;align-items:center;gap:12px;cursor:pointer;}
.lecon-body{display:none;margin-top:14px;padding-top:14px;border-top:1px solid var(--border);}
.lecon-body.open{display:block;}
</style>
</head>
<body>
<div class="layout">
<?php sidebar('enseignant','cours'); ?>
<main class="main-content">
    <div class="page-header">
        <div>
            <h1><?=sanitize($cours['titre'])?></h1>
            <p><?=sanitize($cours['description']??'')?> <?php if($cours['module_titre']): ?> · <span class="badge badge-enseignant">Module : <?=sanitize($cours['module_titre'])?></span><?php endif; ?></p>
        </div>
        <a href="mes_cours.php" class="btn btn-secondary">Mes cours</a>
    </div>

    <?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>
    <?php if($err): ?><div class="alert alert-danger"><?=$err?></div><?php endif; ?>

    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab(this,'tabLecons')">Leçons (<?=count($lecons_arr)?>)</button>
        <button class="tab-btn" onclick="switchTab(this,'tabInscrits')">Inscrits</button>
    </div>

    <!-- ONGLET LEÇONS -->
    <div class="tab-content active" id="tabLecons">
        <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
            <button class="btn btn-primary" onclick="openModal('modalLecon')">Ajouter une leçon</button>
        </div>

        <?php if(empty($lecons_arr)): ?>
        <div class="empty-state"><div class="icon"></div><h3>Aucune leçon</h3><p>Ajoutez votre première leçon (PDF ou vidéo).</p></div>
        <?php else: ?>
        <?php foreach($lecons_arr as $idx=>$l): ?>
        <div class="lecon-row">
            <div class="lecon-row-header" onclick="this.nextElementSibling.classList.toggle('open')">
                <div class="lecon-num" style="background:var(--primary);color:white;"><?=$l['ordre']?></div>
                <div style="flex:1">
                    <strong><?=sanitize($l['titre'])?></strong>
                    <span class="badge badge-<?=$l['type_contenu']==='pdf'?'pdf':'video'?>" style="margin-left:8px;">
                        <?=$l['type_contenu']==='pdf'?'PDF':($l['type_contenu']==='video_url'?'Vidéo URL':'Vidéo Fichier')?>
                    </span>
                    <?php if($l['has_eval']): ?><span class="badge badge-success" style="margin-left:4px;">Évaluation</span><?php else: ?><span class="badge badge-warning" style="margin-left:4px;">Sans évaluation</span><?php endif; ?>
                </div>
                <span style="color:var(--text-muted);font-size:1.2rem;">v</span>
            </div>
            <div class="lecon-body">
                <?php if($l['description']): ?><p style="color:var(--text-muted);margin-bottom:12px;"><?=sanitize($l['description'])?></p><?php endif; ?>

                <!-- Évaluations de cette leçon -->
                <?php
                $evals=$conn->query("SELECT * FROM evaluations WHERE id_lecon=".$l['id']);
                $eval=$evals->fetch_assoc();
                if($eval):
                    $questions=$conn->query("SELECT * FROM questions WHERE id_evaluation=".$eval['id']." ORDER BY id");
                    $q_arr=[];while($q=$questions->fetch_assoc())$q_arr[]=$q;
                ?>
                <div class="eval-card">
                    <h3><?=sanitize($eval['titre'])?> — <?=$eval['duree_minutes']?> min — Seuil : <?=$eval['note_passage']?>%</h3>
                    <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:14px;"><?=count($q_arr)?> question(s)</p>
                    <?php foreach($q_arr as $q): ?>
                    <div style="background:white;border-radius:8px;padding:12px;margin-bottom:8px;border:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <span class="badge <?=$q['type']==='qcm'?'badge-enseignant':'badge-warning'?>" style="font-size:.7rem;"><?=$q['type']==='qcm'?'QCM':'Ouverte'?></span>
                            <span style="font-size:.875rem;margin-left:8px;font-weight:600;"><?=sanitize(substr($q['question'],0,80))?><?=strlen($q['question'])>80?'...':''?></span>
                        </div>
                        <form method="POST"><input type="hidden" name="action" value="del_question"><input type="hidden" name="qid" value="<?=$q['id']?>"><button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ?')">Supprimer</button></form>
                    </div>
                    <?php endforeach; ?>
                    <!-- Ajouter question -->
                    <button class="btn btn-sm btn-outline" style="margin-top:8px;" onclick="openModal('modalQ<?=$eval['id']?>')">Question</button>
                </div>
                <?php else: ?>
                <div style="border:2px dashed var(--border);border-radius:10px;padding:16px;text-align:center;">
                    <p style="color:var(--text-muted);margin-bottom:10px;">Cette leçon n'a pas encore d'évaluation</p>
                    <button class="btn btn-primary btn-sm" onclick="openModal('modalEval<?=$l['id']?>')">Créer une évaluation</button>
                </div>
                <?php endif; ?>

                <div style="margin-top:12px;display:flex;gap:8px;">
                    <form method="POST" style="display:inline;"><input type="hidden" name="action" value="del_lecon"><input type="hidden" name="lid" value="<?=$l['id']?>"><button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette leçon ?')">Supprimer la leçon</button></form>
                </div>
            </div>
        </div>

        <!-- Modal Eval pour leçon -->
        <?php if(!$eval): ?>
        <div class="modal-overlay" id="modalEval<?=$l['id']?>">
            <div class="modal"><div class="modal-header"><h3>Créer une évaluation pour : <?=sanitize($l['titre'])?></h3><button class="modal-close" onclick="closeModal('modalEval<?=$l['id']?>')">✕</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="add_eval">
                <input type="hidden" name="id_lecon" value="<?=$l['id']?>">
                <div class="form-group"><label>Titre de l'évaluation</label><input class="form-control" name="titre_eval" required placeholder="Ex: Quiz — Les bases de HTML"></div>
                <div class="form-grid">
                    <div class="form-group"><label>Durée (minutes)</label><input class="form-control" type="number" name="duree" value="30" min="1"></div>
                    <div class="form-group"><label>Note de passage (%)</label><input class="form-control" type="number" name="note_passage" value="50" min="0" max="100"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalEval<?=$l['id']?>')">Annuler</button><button type="submit" class="btn btn-primary">Créer</button></div>
            </form></div>
        </div>
        <?php endif; ?>

        <!-- Modal Question -->
        <?php if($eval): ?>
        <div class="modal-overlay" id="modalQ<?=$eval['id']?>">
            <div class="modal modal-lg"><div class="modal-header"><h3>Ajouter une question</h3><button class="modal-close" onclick="closeModal('modalQ<?=$eval['id']?>')">✕</button></div>
            <form method="POST">
                <input type="hidden" name="action" value="add_question">
                <input type="hidden" name="id_eval" value="<?=$eval['id']?>">
                <div class="form-group"><label>Type de question</label>
                    <select class="form-control" name="type_question" id="typeQ<?=$eval['id']?>" onchange="toggleQType(this,'qcmBlock<?=$eval['id']?>')">
                        <option value="qcm">QCM (Choix multiple)</option>
                        <option value="ouverte">Question ouverte</option>
                    </select>
                </div>
                <div class="form-group"><label>Question *</label><textarea class="form-control" name="question" required placeholder="Rédigez votre question..."></textarea></div>
                <div id="qcmBlock<?=$eval['id']?>">
                    <div class="form-grid">
                        <div class="form-group"><label>Réponse A</label><input class="form-control" name="rep_a" placeholder="Réponse A"></div>
                        <div class="form-group"><label>Réponse B</label><input class="form-control" name="rep_b" placeholder="Réponse B"></div>
                        <div class="form-group"><label>Réponse C</label><input class="form-control" name="rep_c" placeholder="Réponse C (opt.)"></div>
                        <div class="form-group"><label>Réponse D</label><input class="form-control" name="rep_d" placeholder="Réponse D (opt.)"></div>
                    </div>
                    <div class="form-group"><label>Bonne réponse</label>
                        <select class="form-control" name="bonne_rep"><option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option></select>
                    </div>
                </div>
                <div class="form-group"><label>Points</label><input class="form-control" type="number" name="points" value="1" min="1"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalQ<?=$eval['id']?>')">Annuler</button><button type="submit" class="btn btn-primary">Ajouter</button></div>
            </form></div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ONGLET INSCRITS -->
    <div class="tab-content" id="tabInscrits">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Progression</th>
                            <th>Dernière Note</th>
                            <th>Date Inscription</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $inscrits_res = $conn->query("
                        SELECT u.id, u.nom, u.email, i.date_inscription,
                        (SELECT COUNT(*) FROM lecons_vues lv JOIN lecons l2 ON lv.id_lecon=l2.id WHERE lv.id_etudiant=u.id AND l2.id_cours=$id_cours) as nb_vus,
                        (SELECT pourcentage FROM resultats_evaluations re JOIN evaluations ev2 ON re.id_evaluation=ev2.id JOIN lecons l3 ON ev2.id_lecon=l3.id WHERE re.id_etudiant=u.id AND l3.id_cours=$id_cours ORDER BY re.date_passage DESC LIMIT 1) as derniere_note
                        FROM inscriptions i 
                        JOIN utilisateurs u ON i.id_etudiant=u.id 
                        WHERE i.id_cours=$id_cours
                    ");
                    $nb_total_lecons = count($lecons_arr);
                    $count_ins = 0;
                    while($i=$inscrits_res->fetch_assoc()):
                        $count_ins++;
                        $prog = $nb_total_lecons > 0 ? round(($i['nb_vus']/$nb_total_lecons)*100) : 0;
                    ?>
                    <tr>
                        <td>
                            <strong><?=sanitize($i['nom'])?></strong><br>
                            <small style="color:var(--text-muted)"><?=sanitize($i['email'])?></small>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="progress-bar" style="width:100px;margin-bottom:0;"><div class="progress-fill" data-width="<?=$prog?>" style="width:0%"></div></div>
                                <span style="font-size:.8rem;font-weight:700;"><?=$prog?>%</span>
                            </div>
                        </td>
                        <td>
                            <?php if($i['derniere_note']!==null): ?>
                                <span class="badge <?=$i['derniere_note']>=50?'badge-success':'badge-danger'?>"><?=$i['derniere_note']?>%</span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);font-size:.85rem;">Aucune éval.</span>
                            <?php endif; ?>
                        </td>
                        <td><?=date('d/m/Y',strtotime($i['date_inscription']))?></td>
                    </tr>
                    <?php endwhile; if($count_ins===0):?>
                    <tr><td colspan="4" style="text-align:center;padding:48px;color:var(--text-muted)">Aucun étudiant inscrit</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</div>

<!-- Modal Ajouter Leçon -->
<div class="modal-overlay" id="modalLecon">
    <div class="modal modal-lg">
        <div class="modal-header"><h3>Ajouter une leçon</h3><button class="modal-close" onclick="closeModal('modalLecon')">✕</button></div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_lecon">
            <div class="form-grid">
                <div class="full form-group"><label>Titre de la leçon *</label><input class="form-control" name="titre_lecon" required placeholder="Ex: Chapitre 1 — Introduction"></div>
                <div class="full form-group"><label>Description (optionnel)</label><textarea class="form-control" name="desc_lecon" placeholder="Résumez cette leçon..."></textarea></div>
                <div class="form-group"><label>Ordre d'affichage</label><input class="form-control" type="number" name="ordre" value="<?=count($lecons_arr)+1?>" min="1"></div>
                <div class="form-group"><label>Type de contenu</label>
                    <select class="form-control" name="type_contenu" id="typeContenu" onchange="toggleContentType(this.value)">
                        <option value="pdf">Document PDF</option>
                        <option value="video_url">Vidéo (URL YouTube/autre)</option>
                        <option value="video_fichier">Vidéo (fichier MP4)</option>
                    </select>
                </div>
                <div class="full" id="blockPdf">
                    <div class="form-group"><label>Fichier PDF</label>
                        <div class="upload-zone"><input type="file" name="fichier_pdf" accept=".pdf" style="opacity:0;position:absolute;width:100%;height:100%;cursor:pointer;"><span style="font-size:2rem;"></span><p>Cliquez ou glissez votre PDF ici (max 50MB)</p></div>
                    </div>
                </div>
                <div class="full" id="blockVideoUrl" style="display:none;">
                    <div class="form-group"><label>URL de la vidéo</label><input class="form-control" name="video_url" placeholder="https://www.youtube.com/watch?v=..."></div>
                </div>
                <div class="full" id="blockVideoFic" style="display:none;">
                    <div class="form-group"><label>Fichier vidéo (MP4)</label>
                        <div class="upload-zone" style="position:relative;"><input type="file" name="video_fichier" accept="video/*" style="opacity:0;position:absolute;width:100%;height:100%;cursor:pointer;"><span style="font-size:2rem;"></span><p>Cliquez ou glissez votre vidéo ici (max 200MB)</p></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalLecon')">Annuler</button><button type="submit" class="btn btn-primary">Ajouter la leçon</button></div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; pageFooter(); ?>
<script src="../js/app.js"></script>
<script>
function toggleContentType(v){
    document.getElementById('blockPdf').style.display=v==='pdf'?'':'none';
    document.getElementById('blockVideoUrl').style.display=v==='video_url'?'':'none';
    document.getElementById('blockVideoFic').style.display=v==='video_fichier'?'':'none';
}
function toggleQType(sel,blockId){
    document.getElementById(blockId).style.display=sel.value==='ouverte'?'none':'';
}
</script>
</body></html>
