<?php
function topbar($title = '', $subtitle = '', $base = '../') {
    global $conn;
    $id_user = $_SESSION['user_id'];
    $nb_msg = nbMessagesNonLus($conn, $id_user);
    ?>
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title"><?= sanitize($title) ?></div>
            <?php if($subtitle): ?><div class="topbar-sub"><?= sanitize($subtitle) ?></div><?php endif; ?>
        </div>
        <div class="topbar-right">
            <div class="topbar-actions">
                <div class="topbar-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Rechercher...">
                </div>
                
                <div class="theme-toggle" onclick="toggleTheme()" title="Changer de thème">
                    <i class="fa-solid fa-moon dark-only"></i>
                    <i class="fa-solid fa-sun light-only"></i>
                </div>

                <div class="notif-bell" onclick="window.location.href='<?= $base ?>messagerie.php'" title="Messages">
                    <i class="fa-solid fa-envelope" style="font-size: 1.1rem; color: var(--text-muted);"></i>
                    <?php if($nb_msg > 0): ?>
                    <span class="notif-dot"></span>
                    <?php endif; ?>
                </div>

                <div class="notif-bell" title="Notifications">
                    <i class="fa-solid fa-bell" style="font-size: 1.1rem; color: var(--text-muted);"></i>
                    <!-- Notification dot will be added via JS -->
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
