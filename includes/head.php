<?php
function htmlHead($title, $base = '../') {
    echo '<!DOCTYPE html><html lang="fr"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title) . ' — EduLearn</title>';
    echo '<link rel="icon" type="image/svg+xml" href="' . $base . 'img/logo.svg">';
    echo '<link rel="stylesheet" href="' . $base . 'css/style.css">';
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">';
    echo '</head><body>';
}
function htmlFoot($base = '../') {
    require_once __DIR__ . '/footer.php';
    pageFooter();
    echo '<script src="' . $base . 'js/app.js"></script></body></html>';
}
?>
