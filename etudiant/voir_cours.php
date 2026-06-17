<?php
require_once '../includes/session.php';
verifierRole('etudiant');
require_once '../config/database.php';
require_once '../includes/sidebar.php';

$id_etudiant=$_SESSION['user_id'];
$id_cours=intval($_GET['id']??0);
$id_lecon=intval($_GET['lecon']??0);

// Vérifier inscription
$ins=$conn->prepare("SELECT * FROM inscriptions WHERE id_etudiant=? AND id_cours=?");
$ins->bind_param("ii",$id_etudiant,$id_cours);$ins->execute();
if(!$ins->get_result()->fetch_assoc()){header("Location: catalogue.php");exit();}

$s=$conn->prepare("SELECT c.*,u.nom enseignant_nom,m.titre module_titre FROM cours c LEFT JOIN utilisateurs u ON c.id_enseignant=u.id LEFT JOIN modules m ON c.id_module=m.id WHERE c.id=?");
$s->bind_param("i",$id_cours);$s->execute();
$cours=$s->get_result()->fetch_assoc();

$lecons_r=$conn->query("SELECT l.*,(SELECT id FROM evaluations WHERE id_lecon=l.id LIMIT 1) id_eval, (SELECT COUNT(*) FROM lecons_vues WHERE id_lecon=l.id AND id_etudiant=$id_etudiant) vue FROM lecons l WHERE l.id_cours=$id_cours ORDER BY l.ordre ASC");
$lecons=[];while($l=$lecons_r->fetch_assoc())$lecons[]=$l;

// Leçon active
$lecon=null;
if($id_lecon){foreach($lecons as $l){if($l['id']==$id_lecon){$lecon=$l;break;}}}
if(!$lecon && !empty($lecons))$lecon=$lecons[0];

// Marquer comme vue (AJAX)
if(isset($_GET['mark_vue']) && $lecon){
    $conn->query("INSERT IGNORE INTO lecons_vues(id_etudiant,id_lecon) VALUES($id_etudiant,".$lecon['id'].")");
    echo "ok"; exit();
}

// Convertir URL YouTube en embed
function youtubeEmbed($url){
    preg_match('/(?:v=|youtu\.be\/)([^&\s]+)/',$url,$m);
    return isset($m[1])?"https://www.youtube.com/embed/{$m[1]}":$url;
}

// Évaluation de la leçon active
$eval=null;$questions=[];
if($lecon && $lecon['id_eval']){
    $eval=$conn->query("SELECT * FROM evaluations WHERE id=".$lecon['id_eval'])->fetch_assoc();
    $qr=$conn->query("SELECT * FROM questions WHERE id_evaluation=".$eval['id']." ORDER BY id");
    while($q=$qr->fetch_assoc())$questions[]=$q;
    // Résultat existant
    $res_exist=$conn->query("SELECT * FROM resultats_evaluations WHERE id_etudiant=$id_etudiant AND id_evaluation=".$eval['id']." ORDER BY id DESC LIMIT 1")->fetch_assoc();
}

// Progression basée sur les notes des évaluations
$nb_total_evals = $conn->query("SELECT COUNT(*) n FROM evaluations ev JOIN lecons l ON ev.id_lecon=l.id WHERE l.id_cours=$id_cours")->fetch_assoc()['n'];
if ($nb_total_evals > 0) {
    $somme_pct = $conn->query("
        SELECT SUM(re.pourcentage) s FROM resultats_evaluations re 
        JOIN evaluations ev ON re.id_evaluation=ev.id
        JOIN lecons l ON ev.id_lecon=l.id
        WHERE l.id_cours=$id_cours AND re.id_etudiant=$id_etudiant
    ")->fetch_assoc()['s'] ?? 0;
    $pct = min(100, round(($somme_pct / ($nb_total_evals * 100)) * 100));
} else {
    // Si pas d'évaluations, on reste sur les leçons vues
    $nb_total=count($lecons);
    $nb_vus=$conn->query("SELECT COUNT(*) n FROM lecons_vues lv JOIN lecons l ON lv.id_lecon=l.id WHERE l.id_cours=$id_cours AND lv.id_etudiant=$id_etudiant")->fetch_assoc()['n'];
    $pct=$nb_total>0?min(100,round(($nb_vus/$nb_total)*100)):0;
}

// Index leçon active
$idx_active=0;
foreach($lecons as $k=>$l){if($lecon&&$l['id']==$lecon['id']){$idx_active=$k;break;}}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title><?=sanitize($cours['titre'])?></title>
<link rel="icon" type="image/svg+xml" href="../img/logo.svg">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"></head>
<body>
<div class="layout">
<?php sidebar('etudiant','cours'); ?>
<main class="main-content">

    <div style="background:white;border-radius:var(--radius);padding:16px 24px;box-shadow:var(--shadow);margin-bottom:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <div style="flex:1;">
            <strong style="font-size:1rem;"><?=sanitize($cours['titre'])?></strong>
            <span style="color:var(--text-muted);font-size:.85rem;margin-left:8px;">· <?=sanitize($cours['enseignant_nom'])?></span>
            <?php if($cours['module_titre']): ?><span class="badge badge-enseignant" style="margin-left:6px;">Module : <?=sanitize($cours['module_titre'])?></span><?php endif; ?>
        </div>
        <div style="min-width:220px;">
            <div class="progress-label"><span style="font-size:.8rem;">Progression</span><span style="font-size:.8rem;"><?=$pct?>%</span></div>
            <div class="progress-bar"><div class="progress-fill" data-width="<?=$pct?>" style="width:0%"></div></div>
        </div>
        <a href="mes_cours.php" class="btn btn-secondary btn-sm">Retour</a>
    </div>

    <div class="lecon-layout">
        <!-- Liste des leçons -->
        <div class="lecon-sidebar-list">
            <div class="lecon-sidebar-header">Leçons (<?=count($lecons)?>)</div>
            <?php foreach($lecons as $k=>$l): ?>
            <a href="?id=<?=$id_cours?>&lecon=<?=$l['id']?>" class="lecon-item-link <?=$lecon&&$l['id']==$lecon['id']?'active':''?> <?=$l['vue']?'done':''?>">
                <div class="lecon-num"><?=$l['vue']?'V':($k+1)?></div>
                <div class="lecon-item-info">
                    <div class="lecon-item-title"><?=sanitize($l['titre'])?></div>
                    <div class="lecon-item-type">
                        <?=$l['type_contenu']==='pdf'?'PDF':($l['type_contenu']==='video_url'?'Vidéo URL':'Vidéo')?>
                        <?php if($l['id_eval']): ?> · Évaluation<?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Contenu leçon -->
        <div>
            <?php if($lecon): ?>
            <div class="lecon-content-box" id="leconBox">
                <h2 style="font-size:1.25rem;margin-bottom:6px;"><?=sanitize($lecon['titre'])?></h2>
                <?php if($lecon['description']): ?><p style="color:var(--text-muted);margin-bottom:18px;"><?=sanitize($lecon['description'])?></p><?php endif; ?>

                <!-- CONTENU PDF -->
                <?php if($lecon['type_contenu']==='pdf' && $lecon['fichier_pdf']): ?>
                <iframe class="pdf-viewer" src="<?=sanitize($lecon['fichier_pdf'])?>"></iframe>

                <!-- CONTENU VIDEO URL -->
                <?php elseif($lecon['type_contenu']==='video_url' && $lecon['video_url']): ?>
                <div class="video-player">
                    <?php $embed=youtubeEmbed($lecon['video_url']); if(strpos($embed,'youtube.com/embed')!==false): ?>
                    <iframe src="<?=$embed?>" allowfullscreen></iframe>
                    <?php else: ?>
                    <video controls style="width:100%;height:100%;border-radius:var(--radius-sm);" onplay="markVue()"><source src="<?=sanitize($lecon['video_url'])?>">Votre navigateur ne supporte pas la vidéo.</video>
                    <?php endif; ?>
                </div>

                <!-- CONTENU VIDEO FICHIER -->
                <?php elseif($lecon['type_contenu']==='video_fichier' && $lecon['video_fichier']): ?>
                <video class="video-player" controls style="border-radius:var(--radius-sm);" onplay="markVue()">
                    <source src="<?=sanitize($lecon['video_fichier'])?>">Votre navigateur ne supporte pas la vidéo.
                </video>
                <?php else: ?>
                <div class="empty-state"><div class="icon"></div><p>Aucun contenu disponible pour cette leçon.</p></div>
                <?php endif; ?>

                <!-- Bouton marquer comme vue -->
                <div style="margin-top:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                    <?php if(!$lecon['vue']): ?>
                    <button class="btn btn-success" id="btnVue" onclick="markVue()">Marquer comme vue</button>
                    <?php else: ?>
                    <span class="badge badge-success" style="padding:8px 14px;">Leçon vue</span>
                    <?php endif; ?>

                    <!-- Navigation -->
                    <div style="display:flex;gap:8px;">
                        <?php if($idx_active>0): $prev=$lecons[$idx_active-1]; ?>
                        <a href="?id=<?=$id_cours?>&lecon=<?=$prev['id']?>" class="btn btn-secondary btn-sm">Précédente</a>
                        <?php endif; ?>
                        <?php if($idx_active<count($lecons)-1): $next=$lecons[$idx_active+1]; ?>
                        <a href="?id=<?=$id_cours?>&lecon=<?=$next['id']?>" class="btn btn-primary btn-sm">Suivante</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ÉVALUATION -->
            <?php if($eval): ?>
            <div class="lecon-content-box" style="margin-top:20px;" id="evalSection">
                <h3 style="font-size:1.1rem;margin-bottom:4px;"><?=sanitize($eval['titre'])?></h3>
                <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:16px;">
                    <?=$eval['duree_minutes']?> min · Seuil de réussite : <?=$eval['note_passage']?>%
                    <?php if($res_exist): ?> · <strong>Déjà passé : <?=$res_exist['pourcentage']?>%</strong><?php endif; ?>
                </p>

                <?php if($res_exist): ?>
                <div class="result-card" style="background:var(--bg); border:1px solid var(--border); border-radius:24px; padding:48px;">
                    <div class="result-emoji" style="font-size:5rem; line-height:1;"></div>
                    <h3 style="font-size:1.5rem; font-weight:900; margin-top:24px; color:var(--dark);"><?=$res_exist['reussi']?'Réussite':'Échec'?></h3>
                    <p style="color:var(--text-muted); margin-bottom:30px;">Résultat de votre dernière tentative</p>
                    
                    <div style="display:flex; justify-content:center; gap:40px; margin-bottom:32px;">
                        <div style="text-align:center;">
                            <div class="result-score <?=$res_exist['reussi']?'pass':'fail'?>" style="font-size:4.5rem; margin:0;"><?=$res_exist['pourcentage']?>%</div>
                            <div style="font-size:.8rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Score Obtenu</div>
                        </div>
                        <div style="text-align:center;">
                            <div style="font-size:4.5rem; font-weight:900; color:var(--dark); margin:0; opacity:.3;"><?=$eval['note_passage']?>%</div>
                            <div style="font-size:.8rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Seuil Requis</div>
                        </div>
                    </div>

                    <div style="background:white; padding:16px 24px; border-radius:16px; border:1px solid var(--border); display:inline-flex; align-items:center; gap:12px; margin-bottom:32px;">
                        <span style="font-size:1.2rem;"></span>
                        <span style="font-weight:600; color:var(--text);">Points : <?=$res_exist['score']?> / <?=$res_exist['score_max']?></span>
                    </div>

                    <div style="display:flex; justify-content:center; gap:12px;">
                        <button class="btn btn-primary" onclick="document.getElementById('evalForm').style.display='block';this.parentElement.parentElement.style.display='none'">Repasser le quiz</button>
                    </div>
                </div>
                <div id="evalForm" style="display:none;">
                <?php else: ?>
                <div id="evalForm">
                <?php endif; ?>
                    <form method="POST" action="passer_evaluation.php" id="eval-form">
                        <input type="hidden" name="id_evaluation" value="<?=$eval['id']?>">
                        <input type="hidden" name="id_cours" value="<?=$id_cours?>">
                        <?php if($eval['duree_minutes']): ?>
                        <div style="background:var(--primary);color:white;border-radius:8px;padding:10px 16px;display:inline-flex;align-items:center;gap:8px;margin-bottom:16px;">
                            Temps restant : <strong id="timerDisplay"><?=$eval['duree_minutes']?>:00</strong>
                        </div>
                        <?php endif; ?>
                        <?php foreach($questions as $k=>$q): ?>
                        <div class="question-card">
                            <div class="question-num">Question <?=$k+1?>/<?=count($questions)?> · <?=$q['points']?> pt(s) · <?=$q['type']==='qcm'?'QCM':'Ouverte'?></div>
                            <div class="question-text"><?=sanitize($q['question'])?></div>
                            <?php if($q['type']==='qcm'): ?>
                            <?php foreach(['a'=>$q['reponse_a'],'b'=>$q['reponse_b'],'c'=>$q['reponse_c'],'d'=>$q['reponse_d']] as $k2=>$v): if(!$v)continue; ?>
                            <label class="option-label" onclick="this.querySelectorAll('*').forEach(()=>{});this.closest('.question-card').querySelectorAll('.option-label').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">
                                <input type="radio" name="q<?=$q['id']?>" value="<?=$k2?>" required>
                                <div class="option-letter"><?=strtoupper($k2)?></div>
                                <span><?=sanitize($v)?></span>
                            </label>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <textarea class="form-control" name="q<?=$q['id']?>_open" placeholder="Rédigez votre réponse ici..." rows="4"></textarea>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($questions)): ?><p style="color:var(--text-muted);">Aucune question pour le moment.</p><?php else: ?>
                        <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">Soumettre mes réponses</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="lecon-content-box"><div class="empty-state"><div class="icon"></div><h3>Sélectionnez une leçon</h3><p>Cliquez sur une leçon dans la liste pour commencer.</p></div></div>
            <?php endif; ?>
        </div>
    </div>
</main>
</div>
<?php require_once '../includes/footer.php'; pageFooter(); ?>
<script src="../js/app.js"></script>
<script>
function markVue(){
    fetch('?id=<?=$id_cours?>&lecon=<?=$lecon['id']??0?>&mark_vue=1')
    .then(()=>{
        const btn=document.getElementById('btnVue');
        if(btn){btn.outerHTML='<span class="badge badge-success" style="padding:8px 14px;">Leçon vue</span>';}
    });
}
// Marquer auto pour PDF après 3s et vidéo dès play
setTimeout(()=>{<?php if($lecon&&$lecon['type_contenu']==='pdf'):?>markVue();<?php endif;?>},3000);
<?php if($eval&&$eval['duree_minutes']): ?>startTimer(<?=$eval['duree_minutes']*60?>,'timerDisplay');<?php endif; ?>
</script>
</body></html>
