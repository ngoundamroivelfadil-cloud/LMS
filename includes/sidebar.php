<?php
function sidebar($role, $active = '', $base = '../') {
    global $conn;
    $nom      = $_SESSION['nom'];
    $initiale = initiale($nom);
    $nb_msg   = nbMessagesNonLus($conn, $_SESSION['user_id']);
    $avatar   = $_SESSION['avatar'] ?? null;

    $menus = [
        'promoteur' => [
            ['icon'=>'fa-house',           'label'=>'Tableau de bord','href'=>$base.'promoteur/dashboard.php',    'key'=>'dashboard'],
            ['icon'=>'fa-layer-group',     'label'=>'Modules',        'href'=>$base.'promoteur/modules.php',       'key'=>'modules'],
            ['icon'=>'fa-users',           'label'=>'Utilisateurs',   'href'=>$base.'promoteur/utilisateurs.php',  'key'=>'utilisateurs'],
            ['icon'=>'fa-chart-bar',       'label'=>'Statistiques',   'href'=>$base.'promoteur/stats.php',         'key'=>'stats'],
            ['icon'=>'fa-certificate',     'label'=>'Certificats',    'href'=>$base.'promoteur/certificats.php',   'key'=>'certificats'],
            ['icon'=>'fa-ranking-star',    'label'=>'Classement',     'href'=>$base.'promoteur/classement.php',    'key'=>'classement'],
            ['icon'=>'fa-envelope',        'label'=>'Messages',       'href'=>$base.'messagerie.php', 'key'=>'messages', 'badge'=>$nb_msg],
            ['icon'=>'fa-clock-rotate-left','label'=>'Connexions',    'href'=>$base.'promoteur/connexions.php',    'key'=>'connexions'],
        ],
        'enseignant' => [
            ['icon'=>'fa-house',          'label'=>'Tableau de bord','href'=>$base.'enseignant/dashboard.php',   'key'=>'dashboard'],
            ['icon'=>'fa-book-open',      'label'=>'Mes cours',      'href'=>$base.'enseignant/mes_cours.php',   'key'=>'cours'],
            ['icon'=>'fa-circle-plus',    'label'=>'Créer un cours', 'href'=>$base.'enseignant/creer_cours.php', 'key'=>'creer'],
            ['icon'=>'fa-envelope',       'label'=>'Messages',       'href'=>$base.'messagerie.php','key'=>'messages','badge'=>$nb_msg],
        ],
        'etudiant' => [
            ['icon'=>'fa-house',          'label'=>'Tableau de bord','href'=>$base.'etudiant/dashboard.php',   'key'=>'dashboard'],
            ['icon'=>'fa-book-open',      'label'=>'Mes cours',      'href'=>$base.'etudiant/mes_cours.php',   'key'=>'cours'],
            ['icon'=>'fa-magnifying-glass','label'=>'Catalogue',     'href'=>$base.'etudiant/catalogue.php',   'key'=>'catalogue'],
            ['icon'=>'fa-certificate',    'label'=>'Mes certificats','href'=>$base.'etudiant/certificats.php', 'key'=>'certificats'],
            ['icon'=>'fa-ranking-star',   'label'=>'Classement',    'href'=>$base.'etudiant/classement.php',  'key'=>'classement'],
            ['icon'=>'fa-envelope',       'label'=>'Messages',       'href'=>$base.'messagerie.php','key'=>'messages','badge'=>$nb_msg],
        ],
    ];
    $labels = ['promoteur'=>'Promoteur','enseignant'=>'Enseignant','etudiant'=>'Etudiant'];
    ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-box"><img src="<?= $base ?>img/logo.svg" alt="Logo" style="width:100%;height:100%;object-fit:contain;"></div>
            <div><h2>EduLearn</h2><span>Plateforme LMS</span></div>
        </div>
        <div class="sidebar-user">
            <?php if ($avatar): ?>
            <img src="<?= sanitize($avatar) ?>" class="user-avatar-img" alt="">
            <?php else: ?>
            <div class="user-avatar"><?= $initiale ?></div>
            <?php endif; ?>
            <div class="user-info">
                <span class="name"><?= sanitize($nom) ?></span>
                <span class="role-badge"><?= $labels[$role] ?></span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($menus[$role] as $item): ?>
            <a href="<?= $item['href'] ?>" class="nav-item <?= $active===$item['key']?'active':'' ?>">
                <i class="fa-solid <?= $item['icon'] ?> nav-icon"></i>
                <span><?= $item['label'] ?></span>
                <?php if(!empty($item['badge']) && $item['badge']>0): ?>
                <span class="nav-badge"><?= $item['badge'] ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= $base ?>profil.php" class="nav-item <?= $active==='profil'?'active':'' ?>">
                <i class="fa-solid fa-user nav-icon"></i><span>Mon profil</span>
            </a>
            <a href="<?= $base ?>logout.php" class="nav-item logout">
                <i class="fa-solid fa-right-from-bracket nav-icon"></i><span>Déconnexion</span>
            </a>
        </div>
    </aside>
    <?php
}
?>
