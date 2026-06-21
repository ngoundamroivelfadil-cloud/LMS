<?php
session_start();
require_once 'config/database.php';
require_once 'includes/session.php';
if (isset($_SESSION['user_id'])) { header("Location: {$_SESSION['role']}/dashboard.php"); exit(); }

$erreur = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifier_csrf();
    $email = trim($_POST['email']??'');
    $mdp   = trim($_POST['mot_de_passe']??'');
    if (!$email||!$mdp) { $erreur="Veuillez remplir tous les champs."; }
    else {
        $s=$conn->prepare("SELECT * FROM utilisateurs WHERE email=?");
        $s->bind_param("s",$email); $s->execute();
        $user=$s->get_result()->fetch_assoc();
        if ($user && password_verify($mdp,$user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['avatar']  = $user['avatar'];
            logConnexion($conn, $user['id']);
            header("Location: {$user['role']}/dashboard.php");
            exit();
        } else { $erreur="Email ou mot de passe incorrect."; }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>EduLearn — Connexion</title>
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
            <p>La plateforme d'apprentissage en ligne moderne. Progressez a votre rythme.</p>
        </div>
        <div class="login-features">
            <div class="login-feature">
                <div class="login-feature-icon"><i class="fa-solid fa-file-pdf"></i></div>
                <span>Cours en PDF et video interactifs</span>
            </div>
            <div class="login-feature">
                <div class="login-feature-icon"><i class="fa-solid fa-pen-to-square"></i></div>
                <span>Evaluations apres chaque lecon</span>
            </div>
            <div class="login-feature">
                <div class="login-feature-icon"><i class="fa-solid fa-chart-line"></i></div>
                <span>Suivi de progression en temps reel</span>
            </div>
            <div class="login-feature">
                <div class="login-feature-icon"><i class="fa-solid fa-certificate"></i></div>
                <span>Certificats de validation de modules</span>
            </div>
        </div>
        <p style="margin-top:36px;font-size:.7rem;letter-spacing:1.5px;text-transform:uppercase;opacity:.55;text-align:center;">
            Realise par : Mounbeket Ngoundam V. Abdel Fadil
        </p>
    </div>
    <div class="login-right">
        <div class="login-form-box">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <h2>Bon retour !</h2>
                <div class="theme-toggle" onclick="toggleTheme()" title="Changer de thème">
                    <i class="fa-solid fa-moon"></i>
                </div>
            </div>
            <p class="subtitle">Connectez-vous a votre espace d'apprentissage</p>
            <?php if($erreur): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><?= sanitize($erreur) ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="form-group">
                    <label>Adresse email</label>
                    <div class="input-icon"><i class="fa-solid fa-envelope"></i>
                    <input class="form-control" type="email" name="email" placeholder="votre@email.com" value="<?= sanitize($_POST['email']??'') ?>" required></div>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <div class="input-icon"><i class="fa-solid fa-lock"></i>
                    <input class="form-control" type="password" name="mot_de_passe" placeholder="••••••••" required></div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-right-to-bracket"></i> Se connecter
                </button>
            </form>
            <div style="margin-top:24px;padding-top:20px;border-top:1px solid #e2e8f0;text-align:center;">
                <p style="font-size:.85rem;color:#64748b;">
                    Pas encore de compte ? <a href="inscription.php" style="color:#4f46e5;font-weight:600;">Inscrivez-vous gratuitement</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; pageFooter(); ?>
<script src="js/app.js"></script>
</body>
</html>
