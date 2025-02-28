<?php
require_once('functions.php');

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Erreur de sécurité, veuillez réessayer.";
    } else {

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    list($isValid, $passwordError) = validatePasswordStrength($password);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse e-mail invalide.";
    } elseif ($password !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (!$isValid) {
        $error = $passwordError;
    } else {
        // Vérifier si l'email existe déjà
        $users = loadUsers();
        if (isset($users[$email])) {
            $error = "Cette adresse e-mail est déjà utilisée.";
        } else {
            // Hacher le mot de passe et enregistrer l'utilisateur
            $users[$email] = password_hash($password, PASSWORD_DEFAULT);
            saveUsers($users);
            
            // Créer la session et rediriger
            $_SESSION['user'] = $email;
            header("Location: dashboard.php");
            exit();
        }
    }
    }
    
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Inscription</title>
</head>
<body>
<h1>Créer un compte</h1>
    
    <?php if (!empty($error)): ?>
        <div style="color: red;"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div style="color: green;"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div>
            <label for="email">Adresse e-mail :</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="confirm_password">Confirmer le mot de passe :</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <div>
            <button type="submit">S'inscrire</button>
        </div>
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    </form>
    <p>Déjà inscrit ? <a href="connexion.php">Connectez-vous</a></p>
</body>
</html>