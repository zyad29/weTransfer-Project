<?php
require_once 'functions.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$userEmail = getCurrentUserEmail();
$message = '';

// Vérifier si l'utilisateur a téléchargé au moins un fichier
if (!hasUploadedFiles($userEmail)) {
    $message = '<div style="color: red;">Vous devez avoir téléchargé au moins un fichier pour gérer les permissions.</div>';
}
else {
    // Traitement de l'ajout d'un accès
    if (isset($_POST['grant_access']) && isset($_POST['granted_user_email'])) {

        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $message = '<div style="color: red;">Erreur de sécurité, veuillez réessayer.</div>';
        } else {

        $grantedUserEmail = trim($_POST['granted_user_email']);
        
        if ($grantedUserEmail === $userEmail) {
            $message = '<div style="color: red;">Vous ne pouvez pas vous donner accès à vous-même.</div>';
        }
        elseif (!emailExists($grantedUserEmail)) {
            $message = '<div style="color: red;">Cet utilisateur n\'existe pas.</div>';
        }
        else {
            if (grantAccess($userEmail, $grantedUserEmail)) {
                $message = '<div style="color: green;">Accès accordé avec succès.</div>';
            } else {
                $message = '<div style="color: orange;">Cet utilisateur a déjà accès à vos fichiers.</div>';
            }
        }
        
      }     
        
    }
    
    // Traitement de la révocation d'un accès
    if (isset($_GET['revoke']) && !empty($_GET['revoke'])) {
        $revokedUserEmail = $_GET['revoke'];
        
        if (revokeAccess($userEmail, $revokedUserEmail)) {
            $message = '<div style="color: green;">Accès révoqué avec succès.</div>';
        } else {
            $message = '<div style="color: red;">Erreur lors de la révocation de l\'accès.</div>';
        }
    }
}

// Obtenir la liste des utilisateurs ayant accès
$grantedUsers = getGrantedUsers($userEmail);

// Obtenir la liste des utilisateurs nous ayant donné accès
$accessGrantors = getAccessGrantors($userEmail);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Gestion des permissions</title>
</head>
<body>
<h1>Gestion des Permissions</h1>
    
    <p><a href="dashboard.php">Retour au tableau de bord</a></p>
    
    <?php echo $message; ?>
    
    <?php if (hasUploadedFiles($userEmail)): ?>
        <h2>Accorder l'accès à un utilisateur</h2>
        <form method="post" action="">
            <div>
                <label for="granted_user_email">Email de l'utilisateur :</label>
                <input type="email" id="granted_user_email" name="granted_user_email" required>
            </div>
            <button type="submit" name="grant_access">Accorder l'accès</button>

            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        </form>
        
        <h2>Utilisateurs ayant accès à vos fichiers</h2>
        <?php if (empty($grantedUsers)): ?>
            <p>Vous n'avez accordé l'accès à aucun utilisateur.</p>
        <?php else: ?>
            <table style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Email Utilisateur</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grantedUsers as $grantedUser): ?>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($grantedUser); ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">
                            <a href="permissions.php?revoke=<?php echo urlencode($grantedUser); ?>" 
                               onclick="return confirm('Êtes-vous sûr de vouloir révoquer l\'accès à cet utilisateur ?');">Révoquer l'accès</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
    
    <h2>Utilisateurs vous ayant accordé l'accès</h2>
    <?php if (empty($accessGrantors)): ?>
        <p>Aucun utilisateur ne vous a accordé l'accès à ses fichiers.</p>
    <?php else: ?>
        <table style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Email Utilisateur</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accessGrantors as $grantorEmail): ?>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($grantorEmail); ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px;">
                        <a href="dashboard.php?view_user=<?php echo urlencode($grantorEmail); ?>">Voir ses fichiers</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>