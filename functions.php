<?php
// Initialisation de la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Définition des chemins des dossiers
define('UPLOADS_DIR', __DIR__ . '/uploads/');
define('DATA_DIR', __DIR__ . '/data/');
define('FILES_JSON', DATA_DIR . 'files.json');
define('USERS_JSON', DATA_DIR . 'users.json');
define('PERMISSIONS_JSON', DATA_DIR . 'permissions.json');

// Crée les dossiers s'ils n'existent pas
if (!file_exists(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}


// =================== FONCTIONS DE GESTION DES UTILISATEURS ===================


//////////////////////// Charge les utilisateurs depuis le fichier JSON ///////////////////////
 
function loadUsers() {
    if (file_exists(USERS_JSON)) {
        $jsonData = file_get_contents(USERS_JSON);
        return json_decode($jsonData, true) ?: [];
    }
    return [];
}

//////////////////////// Sauvegarde les utilisateurs dans le fichier JSON ///////////////////////
 
function saveUsers($users) {
    file_put_contents(USERS_JSON, json_encode($users, JSON_PRETTY_PRINT));
}

//////////////////////// Vérifie si l'email existe déjà ///////////////////////
 
function emailExists($email) {
    $users = loadUsers();
    return isset($users[$email]);
}

//////////////////////// Obtient l'email de l'utilisateur actuellement connecté ///////////////////////
 
function getCurrentUserEmail() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

//////////////////////// Vérifie si l'utilisateur est connecté ///////////////////////
 
function isLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

//////////////////////// Redirige vers la page de connexion si non connecté ///////////////////////
 
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: connexion.php");
        exit();
    }
}

//////////////////////// Fonction pour nettoyer les adresses email ///////////////////////

 
function sanitizeInput($input) {
    return filter_var($input, FILTER_SANITIZE_EMAIL);
}


// =================== FONCTIONS DE GESTION DES FICHIERS ===================


//////////////////////// Fonction pour charger les données des fichiers depuis le JSON ///////////////////////
 
function loadFilesData() {
    if (file_exists(FILES_JSON)) {
        $jsonData = file_get_contents(FILES_JSON);
        return json_decode($jsonData, true) ?: [];
    }
    return [];
}

//////////////////////// Fonction pour sauvegarder les données des fichiers dans le JSON ///////////////////////
 
function saveFilesData($filesData) {
    file_put_contents(FILES_JSON, json_encode($filesData, JSON_PRETTY_PRINT));
}

//////////////////////// Fonction pour générer un hash unique pour un dossier de fichier ///////////////////////
 
function generateFileHash() {
    return md5(uniqid() . time() . rand());
}

//////////////////////// Fonction pour obtenir l'extension d'un fichier ///////////////////////

 
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

//////////////////////// Fonction pour vérifier si un fichier est autorisé ///////////////////////
 
function isFileAllowed($filename, $filesize) {
    // Vérifier l'extension (interdiction de .php)
    $extension = getFileExtension($filename);
    if ($extension === 'php') {
        return [false, "Les fichiers PHP ne sont pas autorisés."];
    }
    
    // Vérifier la taille (max 200 Mo = 200 * 1024 * 1024 octets)
    $maxSize = 200 * 1024 * 1024; // 200 Mo en octets
    if ($filesize > $maxSize) {
        return [false, "La taille du fichier dépasse la limite de 200 Mo."];
    }
    
    return [true, ""];
}

//////////////////////// Fonction pour ajouter un nouveau fichier dans le système ///////////////////////
 
function addFile($originalName, $hashDir, $fileSize, $userEmail = null) {
    if ($userEmail === null) {
        $userEmail = getCurrentUserEmail();
    }
    
    $fileId = uniqid();
    $filesData = loadFilesData();
    
    $filesData[$fileId] = [
        'id' => $fileId,
        'original_name' => $originalName,
        'hash_dir' => $hashDir,
        'size' => $fileSize,
        'upload_date' => date('Y-m-d H:i:s'),
        'download_count' => 0,
        'user_email' => $userEmail
    ];
    
    saveFilesData($filesData);
    
    return $fileId;
}

//////////////////////// Fonction pour vérifier si un utilisateur est propriétaire d'un fichier ///////////////////////
 
function isFileOwner($fileId, $userEmail = null) {
    if ($userEmail === null) {
        $userEmail = getCurrentUserEmail();
    }
    
    $filesData = loadFilesData();
    
    if (!isset($filesData[$fileId])) {
        return false;
    }
    
    $fileOwnerEmail = $filesData[$fileId]['user_email'];
    
    // C'est le propriétaire direct
    if ($fileOwnerEmail === $userEmail) {
        return true;
    }
    
    // Vérifier si l'utilisateur a accès via les permissions
    return hasAccess($fileOwnerEmail, $userEmail);
}

//////////////////////// Fonction pour incrémenter le compteur de téléchargements ///////////////////////
 
function incrementDownloadCount($fileId) {
    $filesData = loadFilesData();
    
    if (isset($filesData[$fileId])) {
        $filesData[$fileId]['download_count']++;
        saveFilesData($filesData);
        return true;
    }
    
    return false;
}

//////////////////////// Fonction pour supprimer un fichier ///////////////////////
 
function deleteFile($fileId) {
    $filesData = loadFilesData();
    
    if (isset($filesData[$fileId])) {
        $hashDir = $filesData[$fileId]['hash_dir'];
        $originalName = $filesData[$fileId]['original_name'];
        
        // Supprimer le fichier physique
        $filePath = UPLOADS_DIR . $hashDir . '/' . $originalName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Supprimer le dossier s'il est vide
        $dirPath = UPLOADS_DIR . $hashDir;
        if (file_exists($dirPath) && count(scandir($dirPath)) <= 2) { // . et ..
            rmdir($dirPath);
        }
        
        // Supprimer l'entrée du fichier JSON
        unset($filesData[$fileId]);
        saveFilesData($filesData);
        
        return true;
    }
    
    return false;
}

//////////////////////// Fonction pour formater la taille du fichier pour l'affichage ///////////////////////

function formatFileSize($bytes) {
    $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

//////////////////////// Fonction pour vérifier si un utilisateur a téléchargé au moins un fichier ///////////////////////

 
function hasUploadedFiles($userEmail) {
    $filesData = loadFilesData();
    foreach ($filesData as $fileInfo) {
        if ($fileInfo['user_email'] === $userEmail) {
            return true;
        }
    }
    return false;
}



// =================== FONCTIONS DE GESTION DES PERMISSIONS ===================



////////////////////////Fonction pour charger les données de permissions ///////////////////////
 
function loadPermissionsData() {
    if (file_exists(PERMISSIONS_JSON)) {
        $jsonData = file_get_contents(PERMISSIONS_JSON);
        return json_decode($jsonData, true) ?: [];
    }
    return [];
}

//////////////////////// Fonction pour sauvegarder les données de permissions ///////////////////////
 
function savePermissionsData($permissionsData) {
    file_put_contents(PERMISSIONS_JSON, json_encode($permissionsData, JSON_PRETTY_PRINT));
}

//////////////////////// Fonction pour accorder l'accès à un autre utilisateur ///////////////////////
 
function grantAccess($ownerEmail, $grantedEmail) {
    $permissionsData = loadPermissionsData();
    
    // Initialiser l'entrée pour l'utilisateur propriétaire si elle n'existe pas
    if (!isset($permissionsData[$ownerEmail])) {
        $permissionsData[$ownerEmail] = ['granted_users' => []];
    }
    
    // Ajouter l'utilisateur à la liste des accès s'il n'y est pas déjà
    if (!in_array($grantedEmail, $permissionsData[$ownerEmail]['granted_users'])) {
        $permissionsData[$ownerEmail]['granted_users'][] = $grantedEmail;
        savePermissionsData($permissionsData);
        return true;
    }
    
    return false; // L'utilisateur avait déjà accès
}

////////////////////////Fonction pour révoquer l'accès à un utilisateur ///////////////////////

 
function revokeAccess($ownerEmail, $revokedEmail) {
    $permissionsData = loadPermissionsData();
    
    if (isset($permissionsData[$ownerEmail]) && 
        in_array($revokedEmail, $permissionsData[$ownerEmail]['granted_users'])) {
        
        $key = array_search($revokedEmail, $permissionsData[$ownerEmail]['granted_users']);
        unset($permissionsData[$ownerEmail]['granted_users'][$key]);
        $permissionsData[$ownerEmail]['granted_users'] = array_values($permissionsData[$ownerEmail]['granted_users']);
        
        savePermissionsData($permissionsData);
        return true;
    }
    
    return false; // L'utilisateur n'avait pas accès ou le propriétaire n'existe pas
}

//////////////////////// Fonction pour vérifier si un utilisateur a accès aux fichiers d'un autre ///////////////////////

 
function hasAccess($fileOwnerEmail, $accessEmail) {
    $permissionsData = loadPermissionsData();
    
    return isset($permissionsData[$fileOwnerEmail]) && 
           in_array($accessEmail, $permissionsData[$fileOwnerEmail]['granted_users']);
}


////////////////////////Fonction pour obtenir la liste des utilisateurs ayant accès à nos fichiers ///////////////////////
 
function getGrantedUsers($userEmail) {
    $permissionsData = loadPermissionsData();
    
    if (isset($permissionsData[$userEmail])) {
        return $permissionsData[$userEmail]['granted_users'];
    }
    
    return [];
}

////////////////////////Fonction pour obtenir la liste des utilisateurs nous ayant donné accès ///////////////////////
 
function getAccessGrantors($userEmail) {
    $permissionsData = loadPermissionsData();
    $grantors = [];
    
    foreach ($permissionsData as $ownerEmail => $data) {
        if (in_array($userEmail, $data['granted_users'])) {
            $grantors[] = $ownerEmail;
        }
    }
    
    return $grantors;
}

///////////////////// Vérifie si un mot de passe respecte les règles de complexité ///////////////////////
 
function validatePasswordStrength($password) {
    // Longueur minimale
    if (strlen($password) < 8) {
        return [false, "Le mot de passe doit contenir au moins 8 caractères."];
    }
    
    // Vérifier la présence d'une lettre majuscule
    if (!preg_match('/[A-Z]/', $password)) {
        return [false, "Le mot de passe doit contenir au moins une lettre majuscule."];
    }
    
    // Vérifier la présence d'une lettre minuscule
    if (!preg_match('/[a-z]/', $password)) {
        return [false, "Le mot de passe doit contenir au moins une lettre minuscule."];
    }
    
    // Vérifier la présence d'un chiffre
    if (!preg_match('/[0-9]/', $password)) {
        return [false, "Le mot de passe doit contenir au moins un chiffre."];
    }
    
    // Vérifier la présence d'un caractère spécial
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return [false, "Le mot de passe doit contenir au moins un caractère spécial."];
    }
    
    return [true, ""];
}

//////////////////////// Fonction pour se prémunir des attaques CSRF ///////////////////////

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && $token === $_SESSION['csrf_token'];
}