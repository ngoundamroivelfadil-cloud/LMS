<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function estConnecte() { return isset($_SESSION['user_id']); }

function verifierRole($role) {
    if (!estConnecte()) { header("Location: ../index.php"); exit(); }
    if ($_SESSION['role'] !== $role) { header("Location: ../index.php"); exit(); }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifier_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        $session_token = $_SESSION['csrf_token'] ?? '';
        
        if (empty($token) || $token !== $session_token) {
            // Si le jeton est invalide, on régénère pour la suite mais on bloque l'action actuelle
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            die("Erreur de sécurité : Session expirée ou jeton invalide. Veuillez actualiser la page et réessayer.");
        }
    }
}

function initiale($nom) {
    $parts = explode(' ', trim($nom));
    $i = strtoupper(substr($parts[0], 0, 1));
    if (isset($parts[1])) $i .= strtoupper(substr($parts[1], 0, 1));
    return $i;
}

function sanitize($str) {
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

function uploadFichier($fichier, $dossier, $types_autorises, $taille_max = 209715200) {
    if ($fichier['error'] !== UPLOAD_ERR_OK) return ['ok'=>false,'msg'=>'Erreur upload'];
    if ($fichier['size'] > $taille_max) return ['ok'=>false,'msg'=>'Fichier trop volumineux'];
    $ext = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $types_autorises)) return ['ok'=>false,'msg'=>'Type non autorise'];
    return uploadVersCloudinary($fichier['tmp_name'], $ext);
}

// Upload un fichier vers Cloudinary (stockage permanent) et retourne son URL
function uploadVersCloudinary($chemin_tmp, $ext) {
    $types_video = ['mp4','webm','ogg','mov'];
    $resource_type = in_array($ext, $types_video) ? 'video' : 'auto';

    $timestamp = time();
    $public_id = 'lms_' . uniqid();
    $folder = 'edulearn';

    $params_a_signer = [
        'folder'    => $folder,
        'public_id' => $public_id,
        'timestamp' => $timestamp,
    ];
    ksort($params_a_signer);
    $str_a_signer = '';
    foreach ($params_a_signer as $k => $v) { $str_a_signer .= $k . '=' . $v . '&'; }
    $str_a_signer = rtrim($str_a_signer, '&') . CLOUDINARY_API_SECRET;
    $signature = sha1($str_a_signer);

    $url = "https://api.cloudinary.com/v1_1/" . CLOUDINARY_CLOUD_NAME . "/$resource_type/upload";

    $post_fields = [
        'file'      => new CURLFile($chemin_tmp),
        'api_key'   => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder'    => $folder,
        'public_id' => $public_id,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($curl_err) return ['ok'=>false,'msg'=>'Erreur reseau: ' . $curl_err];

    $data = json_decode($response, true);
    if ($http_code !== 200 || !isset($data['secure_url'])) {
        $err_msg = $data['error']['message'] ?? 'Erreur Cloudinary inconnue';
        return ['ok'=>false,'msg'=>'Echec upload: ' . $err_msg];
    }

    return ['ok'=>true, 'nom'=>$data['secure_url'], 'url'=>$data['secure_url']];
}

function logConnexion($conn, $id_user) {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? 'inconnue';
    $nav = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $s   = $conn->prepare("INSERT INTO connexions(id_utilisateur,ip,navigateur) VALUES(?,?,?)");
    $s->bind_param("iss", $id_user, $ip, $nav);
    $s->execute();
}

function nbMessagesNonLus($conn, $id_user) {
    return $conn->query("SELECT COUNT(*) n FROM messages WHERE id_destinataire=$id_user AND lu=0")->fetch_assoc()['n'];
}

function envoyerNotification($conn, $id_user, $type, $titre, $message, $lien = null) {
    $s = $conn->prepare("INSERT INTO notifications (id_utilisateur, type, titre, message, lien) VALUES (?,?,?,?,?)");
    $s->bind_param("issss", $id_user, $type, $titre, $message, $lien);
    return $s->execute();
}

