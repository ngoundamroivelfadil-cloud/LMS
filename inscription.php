<?php
session_start();
require_once 'config/database.php';
require_once 'includes/session.php';
if (isset($_SESSION['user_id'])) { header("Location: {$_SESSION['role']}/dashboard.php"); exit(); }

$erreur = ''; $succes = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nom    = trim($_POST['nom'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $mdp    = trim($_POST['mot_de_passe'] ?? '');
    $mdp2   = trim($_POST['mot_de_passe2'] ?? '');
    $role   = $_POST['role'] ?? '';

    if (!$nom || !$email || !$mdp || !$role) {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif (!in_array($role, ['promoteur','enseignant','etudiant'])) {
        $erreur = "Role invalide.";
    } elseif (strlen($mdp) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caracteres.";
    } elseif ($mdp !== $mdp2) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Adresse email invalide.";
    } else {
        $check = $conn->prepare("SELECT id FROM utilisateurs WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) {
            $erreur = "Cette adresse email est deja utilisee.";
        } else {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $s = $conn->prepare("INSERT INTO utilisateurs(nom,email,mot_de_passe,role) VALUES(?,?,?,?)");
            $s->bind_param("ssss", $nom, $email, $hash, $role);
            if ($s->execute()) {
                $id_user = $conn->insert_id;
                $_SESSION['user_id'] = $id_user;
                $_SESSION['nom']     = $nom;
                $_SESSION['email']   = $email;
                $_SESSION['role']    = $role;
                $_SESSION['avatar']  = null;
                logConnexion($conn, $id_user);
                header("Location: $role/dashboard.php");
                exit();
            } else {
                $erreur = "Erreur lors de la creation du compte.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Inscription — EduLearn</title>
<link rel="icon" type="image/svg+xml" href="img/logo.svg">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="login-page">
    <div class="login-left">
        <div class="login-brand">
            <div class="brand-icon"><img src="img/logo.svg" alt="Logo" style="width:100%;height:100%;object-fit:contain;border-radius:14px;"></div>
            <h1>EduLearn</h1>
            <p>Rejoignez la plateforme d'apprentissage en ligne et commencez votre parcours.</p>
        </div>
        <div class="login-features">
            <div class="login-feature">
                <div class="login-feature-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                <span>Enseignants : creez vos propres cours</span>
            </div>
            <div class="login-feature">
                <div class="login-feature-icon"><i class="fa-solid fa-user-graduate"></i></div>
                <span>Etudiants : suivez des cours et obtenez des certificats</span>
            </div>
            <div class="login-feature">
                <div class="login-feature-icon"><i class="fa-solid fa-star"></i></div>
                <span>Promoteurs : organisez la plateforme en modules</span>
            </div>
        </div>
    </div>
    <div class="login-right">
        <div class="login-form-box">
            <h2>Creer un compte</h2>
            <p class="subtitle">Renseignez vos informations personnelles pour commencer</p>
            <?php if($erreur): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><?= sanitize($erreur) ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Nom complet</label>
                    <div class="input-icon"><i class="fa-solid fa-user"></i>
                    <input class="form-control" type="text" name="nom" placeholder="Jean Dupont" value="<?= sanitize($_POST['nom']??'') ?>" required></div>
                </div>
                <div class="form-group">
                    <label>Adresse email</label>
                    <div class="input-icon"><i class="fa-solid fa-envelope"></i>
                    <input class="form-control" type="email" name="email" placeholder="votre@email.com" value="<?= sanitize($_POST['email']??'') ?>" required></div>
                </div>
                <div class="form-group">
                    <label>Je suis</label>
                    <select class="form-control" name="role" required>
                        <option value="">-- Choisir votre role --</option>
                        <option value="etudiant" <?= ($_POST['role']??'')==='etudiant'?'selected':'' ?>>Etudiant</option>
                        <option value="enseignant" <?= ($_POST['role']??'')==='enseignant'?'selected':'' ?>>Enseignant</option>
                        <option value="promoteur" <?= ($_POST['role']??'')==='promoteur'?'selected':'' ?>>Promoteur</option>
                    </select>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Mot de passe</label>
                        <div class="input-icon"><i class="fa-solid fa-lock"></i>
                        <input class="form-control" type="password" name="mot_de_passe" placeholder="6 caracteres min." required></div>
                    </div>
                    <div class="form-group">
                        <label>Confirmer</label>
                        <div class="input-icon"><i class="fa-solid fa-lock"></i>
                        <input class="form-control" type="password" name="mot_de_passe2" placeholder="••••••••" required></div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-user-plus"></i> Creer mon compte
                </button>
            </form>
            <p style="text-align:center;margin-top:20px;font-size:.85rem;color:#64748b;">
                Deja un compte ? <a href="index.php" style="color:#4f46e5;font-weight:600;">Se connecter</a>
            </p>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; pageFooter(); ?>
<script src="js/app.js"></script>
</body>
</html>
