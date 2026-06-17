<?php
require_once '../includes/session.php'; verifierRole('promoteur');
require_once '../config/database.php';
$id_cours=intval($_POST['id_cours']??0); $id_module=intval($_POST['id_module']??0);
if($id_cours&&$id_module){ $conn->query("UPDATE cours SET id_module=$id_module WHERE id=$id_cours"); }
header("Location: modules.php"); exit();
