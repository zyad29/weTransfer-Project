<?php
require_once 'functions.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

// Vérifier si l'ID du fichier est fourni
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$fileId = $_GET['id'];
$userEmail = getCurrentUserEmail();

// Vérifier si l'utilisateur est le propriétaire du fichier ou a accès
if (!isFileOwner($fileId, $userEmail)) {
    header("Location: dashboard.php?error=" . urlencode("Vous n'avez pas la permission de télécharger ce fichier."));
    exit;
}

// Charger les données du fichier
$filesData = loadFilesData();

if (!isset($filesData[$fileId])) {
    header("Location: dashboard.php?error=" . urlencode("Fichier introuvable."));
    exit;
}

$fileInfo = $filesData[$fileId];
$filePath = UPLOADS_DIR . $fileInfo['hash_dir'] . '/' . $fileInfo['original_name'];

// Vérifier si le fichier existe physiquement
if (!file_exists($filePath)) {
    header("Location: dashboard.php?error=" . urlencode("Le fichier n'existe pas sur le serveur."));
    exit;
}

// Incrémenter le compteur de téléchargements
incrementDownloadCount($fileId);

// Préparer le téléchargement du fichier
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($fileInfo['original_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;