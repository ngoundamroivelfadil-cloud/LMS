<?php
require_once '../includes/session.php';
if (!estConnecte()) { header('Content-Type: application/json'); echo json_encode([]); exit(); }
require_once '../config/database.php';

$id_user = $_SESSION['user_id'];
$action  = $_GET['action'] ?? 'list';

if ($action === 'count') {
    $n = $conn->query("SELECT COUNT(*) n FROM notifications WHERE id_utilisateur=$id_user AND lu=0")->fetch_assoc()['n'];
    echo json_encode(['count' => (int)$n]);
    exit();
}

if ($action === 'mark_read') {
    $id = (int)($_POST['id'] ?? 0);
    $conn->query("UPDATE notifications SET lu=1 WHERE id=$id AND id_utilisateur=$id_user");
    echo json_encode(['ok' => true]);
    exit();
}

// Default list
$res = $conn->query("SELECT * FROM notifications WHERE id_utilisateur=$id_user ORDER BY date_notification DESC LIMIT 10");
$notifs = [];
while ($row = $res->fetch_assoc()) {
    $notifs[] = $row;
}

header('Content-Type: application/json');
echo json_encode($notifs);
?>
