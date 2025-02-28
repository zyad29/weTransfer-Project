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
        $currentEmail = getCurrentUserEmail();
    $newEmail = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $confirmEmail = filter_var($_POST['email_confirm'], FILTER_SANITIZE_EMAIL);
    
    // Vérification basique
    if ($newEmail !== $confirmEmail) {
        $error = "Les adresses email ne correspondent pas.";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } else {
        $users = loadUsers();
        
        // Vérifier si le nouvel email n'est pas déjà utilisé
        if ($newEmail !== $currentEmail && isset($users[$newEmail])) {
            $error = "Cette adresse email est déjà utilisée.";
        } else {
            // Récupérer le mot de passe actuel
            $currentPassword = $users[$currentEmail];
            
            // Supprimer l'ancienne entrée
            unset($users[$currentEmail]);
            
            // Ajouter la nouvelle entrée
            $users[$newEmail] = $currentPassword;
            
            // Mettre à jour les références d'email dans le système de fichiers
            updateEmailReferences($currentEmail, $newEmail);
            
            // Sauvegarder et mettre à jour la session
            saveUsers($users);
            $_SESSION['user'] = $newEmail;
            
            $success = "Adresse email mise à jour avec succès.";
            
            // Redirection
            header("Location: dashboard.php?success=" . urlencode($success));
            exit();
        }
      }
    }
    
}

/**
 * Met à jour toutes les références d'email dans le système
 */
function updateEmailReferences($oldEmail, $newEmail) {
    // Mettre à jour les références dans les fichiers
    $filesData = loadFilesData();
    foreach ($filesData as $fileId => $fileInfo) {
        if ($fileInfo['user_email'] === $oldEmail) {
            $filesData[$fileId]['user_email'] = $newEmail;
        }
    }
    saveFilesData($filesData);
    
    // Mettre à jour les références dans les permissions
    $permissionsData = loadPermissionsData();
    
    // Si l'ancien email avait des permissions définies
    if (isset($permissionsData[$oldEmail])) {
        $permissionsData[$newEmail] = $permissionsData[$oldEmail];
        unset($permissionsData[$oldEmail]);
    }
    
    // Mettre à jour les permissions accordées aux autres utilisateurs
    foreach ($permissionsData as $ownerEmail => $data) {
        if (in_array($oldEmail, $data['granted_users'])) {
            $key = array_search($oldEmail, $data['granted_users']);
            $permissionsData[$ownerEmail]['granted_users'][$key] = $newEmail;
        }
    }
    
    savePermissionsData($permissionsData);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Modifier - email</title>
</head>
<body>
<h1>Modifier mon adresse email</h1>
    
    <?php if (!empty($error)): ?>
        <div style="color: red;"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div>
            <label for="email">Nouvelle adresse email :</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <label for="email_confirm">Confirmer l'adresse email :</label>
            <input type="email" id="email_confirm" name="email_confirm" required>
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