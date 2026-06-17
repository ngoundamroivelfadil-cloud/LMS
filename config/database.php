<?php
// Variables d'environnement pour Render, sinon valeurs locales XAMPP
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$name = getenv('DB_NAME') ?: 'edulearn_db';

$conn = new mysqli($host, $user, $pass, $name);
if ($conn->connect_error) {
    die("Connexion BDD échouée : " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

define('UPLOAD_PDF',    dirname(__DIR__) . '/uploads/pdfs/');
define('UPLOAD_VIDEO',  dirname(__DIR__) . '/uploads/videos/');
define('UPLOAD_AVATAR', dirname(__DIR__) . '/uploads/avatars/');
$base = getenv('RENDER_EXTERNAL_URL') ?: 'http://localhost/lms_deploy';
define('BASE_URL', rtrim($base, '/'));

// Configuration Cloudinary (stockage permanent des fichiers PDF/video/avatar)
define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME') ?: 'des02kdkd');
define('CLOUDINARY_API_KEY',    getenv('CLOUDINARY_API_KEY') ?: '374455952845732');
define('CLOUDINARY_API_SECRET', getenv('CLOUDINARY_API_SECRET') ?: 'eL9trDmaTG_8q6KfHf6dPF9JuX0');
