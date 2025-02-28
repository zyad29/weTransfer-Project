<?php
require_once('functions.php');

// Vérifier si l'utilisateur est connecté
requireLogin();

// Récupérer l'email de l'utilisateur
$userEmail = getCurrentUserEmail();

// Traitement de la déconnexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header("Location: connexion.php");
    exit();
}

// Récupérer les messages de statut
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Charger les fichiers de l'utilisateur
$filesData = loadFilesData();
$userFiles = [];
foreach ($filesData as $fileId => $fileInfo) {
    if ($fileInfo['user_email'] === $userEmail) {
        $userFiles[$fileId] = $fileInfo;
    }
}

// Mode par défaut : afficher les fichiers de l'utilisateur courant
$viewUserEmail = $userEmail;
$isViewingOtherUser = false;

// Si on demande à voir les fichiers d'un autre utilisateur auquel on a accès
if (isset($_GET['view_user']) && !empty($_GET['view_user'])) {
    $requestedUserEmail = $_GET['view_user'];
    
    // Vérifier si l'utilisateur existe
    if (emailExists($requestedUserEmail)) {
        // Vérifier si nous avons accès aux fichiers de cet utilisateur
        if (hasAccess($requestedUserEmail, $userEmail)) {
            $viewUserEmail = $requestedUserEmail;
            $isViewingOtherUser = true;
            
            // Charger les fichiers de l'utilisateur demandé
            $userFiles = [];
            foreach ($filesData as $fileId => $fileInfo) {
                if ($fileInfo['user_email'] === $viewUserEmail) {
                    $userFiles[$fileId] = $fileInfo;
                }
            }
        } else {
            $error = "Vous n'avez pas accès aux fichiers de cet utilisateur.";
        }
    } else {
        $error = "Cet utilisateur n'existe pas.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Tableau de bord</title>
</head>
<body>
<h1>Tableau de bord</h1>
    
    <div>
        <h2>Bienvenue, <?php echo htmlspecialchars($userEmail); ?> !</h2>
        
        <?php if (!empty($success)): ?>
            <div style="color: green;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div style="color: red;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div>
            <a href="update_email.php">Modifier mon email</a> | 
            <a href="update_password.php">Modifier mon mot de passe</a> | 
            <a href="permissions.php">Gérer les permissions d'accès</a>
        </div>
        
        <form method="POST">

            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <button type="submit" name="logout" style="background-color: #e74c3c;">Se déconnecter</button>
 
        </form>
    </div>
    
    <div>
        <?php if (!$isViewingOtherUser): ?>
            <h2>Uploader un fichier</h2>
            <form action="upload.php" method="post" enctype="multipart/form-data">
                <input type="file" name="file" required>
                <button type="submit">Uploader</button>
                <p>Contraintes: Pas de fichiers PHP, maximum 200Mo</p>

                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            </form>
        <?php else: ?>
            <h2>Fichiers de l'utilisateur <?php echo htmlspecialchars($viewUserEmail); ?></h2>
            <p><a href="dashboard.php">Retour à mes fichiers</a></p>
        <?php endif; ?>
    </div>
    
    <div>
        <h2><?php echo $isViewingOtherUser ? 'Fichiers partagés' : 'Vos fichiers'; ?></h2>
        <?php if(empty($userFiles)): ?>
            <p><?php echo $isViewingOtherUser ? 'Cet utilisateur n\'a pas de fichiers.' : 'Vous n\'avez pas encore uploadé de fichiers.'; ?></p>
        <?php else: ?>
            <table style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Nom</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Taille</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Date d'upload</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Téléchargements</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($userFiles as $fileId => $fileInfo): ?> 
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($fileInfo['hash_dir']); ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo formatFileSize($fileInfo['size']); ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo $fileInfo['upload_date']; ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo $fileInfo['download_count']; ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">
                            <a href="download.php?id=<?php echo $fileId; ?>">Télécharger</a> 
                            <?php if($fileInfo['user_email'] === $userEmail): ?>
                                | <a href="delete.php?id=<?php echo $fileId; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?');">Supprimer</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?> 
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>