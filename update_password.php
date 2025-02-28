<?php
require_once('functions.php');

// Vérifier si l'utilisateur est connecté
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Erreur de sécurité, veuillez réessayer.";
    } else {

    $email = getCurrentUserEmail();
    $password = $_POST['password'];
    $confirmPassword = $_POST['password_confirm'];
    list($isValid, $passwordError) = validatePasswordStrength($password);
    
    // Vérification basique
    if ($password !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } else if (!$isValid) {
        $error = $passwordError;
    } else {
        $users = loadUsers();
        
        // Mettre à jour le mot de passe
        $users[$email] = password_hash($password, PASSWORD_DEFAULT);
        
        // Sauvegarder
        saveUsers($users);
        
        $success = "Mot de passe mis à jour avec succès.";
        
        // Redirection
        header("Location: dashboard.php?success=" . urlencode($success));
        exit();
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
    <title>Modifier - mot de passe</title>
</head>
<body>
<h1>Modifier mon mot de passe</h1>
    
    <?php if (!empty($error)): ?>
        <div style="color: red;"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div>
            <label for="password">Nouveau mot de passe :</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="password_confirm">Confirmer le mot de passe :</label>
            <input type="password" id="password_confirm" name="password_confirm" required>
        </div>
        <div>
            <button type="submit">Mettre à jour</button>
        </div>

        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    </form>
    
    <div>
        <a href="dashboard.php">Retour au tableau de bord</a>
    </div>
</body>
</html>