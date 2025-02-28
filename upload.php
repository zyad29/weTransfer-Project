<?php 
require_once 'functions.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    header("Location: dashboard.php?error=" . urlencode("Erreur de sécurité, veuillez réessayer."));
    exit;
}

// Vérifier si un fichier a été uploadé
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = "Erreur lors de l'upload du fichier.";
    
    // Gérer les différents codes d'erreur
    if (isset($_FILES['file']['error'])) {
        switch($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg = "Le fichier est trop volumineux.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg = "Le fichier n'a été que partiellement uploadé.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg = "Aucun fichier n'a été uploadé.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMsg = "Dossier temporaire manquant.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMsg = "Échec de l'écriture du fichier sur le disque.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMsg = "Une extension PHP a arrêté l'upload du fichier.";
                break;
        }
    }
    
    header("Location: dashboard.php?error=" . urlencode($errorMsg));
    exit;
}

// Récupérer les informations sur le fichier
$file = $_FILES['file'];
$originalName = $file['name'];
$fileSize = $file['size'];
$fileTmp = $file['tmp_name'];

// Vérifier si le fichier est autorisé (type et taille)
list($isAllowed, $errorMessage) = isFileAllowed($originalName, $fileSize);

if (!$isAllowed) {
    header("Location: dashboard.php?error=" . urlencode($errorMessage));
    exit;
}

// Générer un hash unique pour le dossier de destination
$hashDir = generateFileHash();
$uploadDir = UPLOADS_DIR . $hashDir;

// Créer le dossier s'il n'existe pas
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Déplacer le fichier vers son emplacement final
$destination = $uploadDir . '/' . $originalName;
if (move_uploaded_file($fileTmp, $destination)) {
    // Récupérer l'email de l'utilisateur connecté
    $userEmail = getCurrentUserEmail();
    
    // Ajouter les métadonnées du fichier dans la base de données
    addFile($originalName, $hashDir, $fileSize, $userEmail);

    // Rediriger avec un message de succès
    header("Location: dashboard.php?success=" . urlencode("Fichier uploadé avec succès."));
} else {
    // En cas d'erreur, rediriger avec un message d'erreur
    header("Location: dashboard.php?error=" . urlencode("Impossible de déplacer le fichier uploadé."));
}
exit;