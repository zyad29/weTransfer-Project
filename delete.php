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

// Vérifier si l'utilisateur est le propriétaire du fichier
if (!isFileOwner($fileId, $userEmail)) {
    header("Location: dashboard.php?error=" . urlencode("Vous n'avez pas la permission de supprimer ce fichier."));
    exit;
}

// Supprimer le fichier
if (deleteFile($fileId)) {
    header("Location: dashboard.php?success=" . urlencode("Fichier supprimé avec succès."));
} else {
    header("Location: dashboard.php?error=" . urlencode("Erreur lors de la suppression du fichier."));
}
exit;