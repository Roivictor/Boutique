<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'boissons_db');

// Clé secrète pour les sessions (changez-la en production)
define('SESSION_KEY', 'BoissonsDelight_Admin_Secret_Key_2024!');

// Initialisation de la session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    if (!isset($_SESSION['ip'])) {
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['initiated'] = true;
    }
}

// Fonction de vérification de l'authentification
if (!function_exists('isAdminLoggedIn')) {
    function isAdminLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
}

// Connexion à la base de données
if (!function_exists('getDBConnection')) {
    function getDBConnection() {
        try {
            $conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
            return $conn;
        } catch(PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
}

// Fonction pour vérifier le mot de passe
if (!function_exists('verifyPassword')) {
    function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

// Fonction pour hacher un mot de passe
if (!function_exists('hashPassword')) {
    function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

// Fonction pour générer un token CSRF
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Fonction pour vérifier le token CSRF
if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Redirection si non authentifié
if (!function_exists('requireAdminLogin')) {
    function requireAdminLogin() {
        if (!isAdminLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: login.php');
            exit();
        }
    }
}

// Déconnexion
if (!function_exists('adminLogout')) {
    function adminLogout() {
        session_destroy();
        header('Location: login.php');
        exit();
    }
}

// Fonction de journalisation
if (!function_exists('logAction')) {
    function logAction($action) {
        $log = date('Y-m-d H:i:s') . ' - ' . ($_SESSION['admin_username'] ?? 'Unknown') . ' - ' . $action . PHP_EOL;
        @file_put_contents('admin_logs.txt', $log, FILE_APPEND);
    }
}

// Fonction pour nettoyer les entrées
if (!function_exists('cleanInput')) {
    function cleanInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

// Fonction pour valider une image uploadée
if (!function_exists('validateUploadedImage')) {
    function validateUploadedImage($file) {
        $errors = [];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Erreur lors du téléchargement du fichier.';
            return $errors;
        }
        
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Type de fichier non autorisé. Formats acceptés: JPG, PNG, GIF, WebP.';
        }
        
        if ($file['size'] > $max_size) {
            $errors[] = 'Fichier trop volumineux. Taille max: 2MB.';
        }
        
        return $errors;
    }
}

// Fonction pour uploader une image
if (!function_exists('uploadImage')) {
    function uploadImage($file, $product_name, $existing_filename = null) {
        $upload_dir = 'assets/images/';
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Supprimer l'ancienne image si elle existe
        if ($existing_filename && $existing_filename !== 'default-drink.jpg') {
            $old_image = $upload_dir . $existing_filename;
            if (file_exists($old_image)) {
                unlink($old_image);
            }
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safe_name = preg_replace('/[^a-zA-Z0-9]/', '_', $product_name);
        $filename = uniqid() . '_' . $safe_name . '.' . $extension;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            return $filename;
        }
        
        return false;
    }
}

// Fonction pour supprimer une image
if (!function_exists('deleteProductImage')) {
    function deleteProductImage($filename) {
        if ($filename && $filename !== 'default-drink.jpg') {
            $image_path = 'assets/images/' . $filename;
            if (file_exists($image_path)) {
                return unlink($image_path);
            }
        }
        return false;
    }
}

// Fonction pour obtenir les statistiques des produits
if (!function_exists('getProductStats')) {
    function getProductStats($conn) {
        $stats = [
            'total' => 0,
            'total_stock' => 0,
            'low_stock' => 0,
            'categories' => []
        ];
        
        try {
            // Nombre total de produits
            $stmt = $conn->query("SELECT COUNT(*) as total FROM boissons");
            $result = $stmt->fetch();
            $stats['total'] = $result['total'] ?? 0;
            
            // Stock total
            $stmt = $conn->query("SELECT SUM(stock) as total_stock FROM boissons");
            $result = $stmt->fetch();
            $stats['total_stock'] = $result['total_stock'] ?? 0;
            
            // Produits avec stock faible
            $stmt = $conn->query("SELECT COUNT(*) as low_stock FROM boissons WHERE stock <= 5");
            $result = $stmt->fetch();
            $stats['low_stock'] = $result['low_stock'] ?? 0;
            
            // Nombre de produits par catégorie
            $stmt = $conn->query("SELECT categorie, COUNT(*) as count FROM boissons GROUP BY categorie");
            $stats['categories'] = $stmt->fetchAll();
            
        } catch(PDOException $e) {
            logAction('Erreur statistiques: ' . $e->getMessage());
        }
        
        return $stats;
    }
}

// Fonction pour formater le prix
if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        return number_format($price, 0, ',', ' ') . ' FCA';
    }
}

// Fonction pour obtenir les catégories disponibles
if (!function_exists('getCategories')) {
    function getCategories() {
        return ['Sodas', 'Eaux', 'Jus', 'Cafés', 'Thés', 'Énergisantes', 'Alcoolisées', 'Smoothies'];
    }
}

// Fonction pour valider un produit
if (!function_exists('validateProduct')) {
    function validateProduct($data) {
        $errors = [];
        
        if (empty($data['nom'])) {
            $errors[] = 'Le nom est obligatoire.';
        } elseif (strlen($data['nom']) > 100) {
            $errors[] = 'Le nom ne doit pas dépasser 100 caractères.';
        }
        
        if (empty($data['prix'])) {
            $errors[] = 'Le prix est obligatoire.';
        } elseif (!is_numeric($data['prix']) || $data['prix'] <= 0) {
            $errors[] = 'Le prix doit être un nombre positif.';
        }
        
        if (empty($data['categorie'])) {
            $errors[] = 'La catégorie est obligatoire.';
        }
        
        if (!isset($data['stock']) || $data['stock'] === '') {
            $errors[] = 'Le stock est obligatoire.';
        } elseif (!is_numeric($data['stock']) || $data['stock'] < 0) {
            $errors[] = 'Le stock doit être un nombre positif ou zéro.';
        }
        
        if (!empty($data['description']) && strlen($data['description']) > 1000) {
            $errors[] = 'La description ne doit pas dépasser 1000 caractères.';
        }
        
        return $errors;
    }
}

// Fonction pour changer le mot de passe administrateur
if (!function_exists('changeAdminPassword')) {
    function changeAdminPassword($conn, $admin_id, $current_password, $new_password) {
        try {
            // Vérifier le mot de passe actuel
            $stmt = $conn->prepare("SELECT password_hash FROM administrateurs WHERE id = :id");
            $stmt->bindParam(':id', $admin_id);
            $stmt->execute();
            $admin = $stmt->fetch();
            
            if (!$admin || !verifyPassword($current_password, $admin['password_hash'])) {
                return ['success' => false, 'message' => 'Mot de passe actuel incorrect.'];
            }
            
            // Vérifier la force du nouveau mot de passe
            if (strlen($new_password) < 6) {
                return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.'];
            }
            
            // Hasher le nouveau mot de passe
            $new_hash = hashPassword($new_password);
            
            // Mettre à jour dans la base de données
            $update_stmt = $conn->prepare("UPDATE administrateurs SET password_hash = :hash WHERE id = :id");
            $update_stmt->bindParam(':hash', $new_hash);
            $update_stmt->bindParam(':id', $admin_id);
            
            if ($update_stmt->execute()) {
                logAction('Changement de mot de passe');
                return ['success' => true, 'message' => 'Mot de passe changé avec succès.'];
            } else {
                return ['success' => false, 'message' => 'Erreur lors de la mise à jour.'];
            }
            
        } catch(PDOException $e) {
            logAction('Erreur changement mot de passe: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur de base de données.'];
        }
    }
}

// Fonction pour vérifier si un nom de produit existe déjà
if (!function_exists('productNameExists')) {
    function productNameExists($conn, $name, $exclude_id = null) {
        try {
            if ($exclude_id) {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM boissons WHERE nom = :nom AND id != :id");
                $stmt->bindParam(':nom', $name);
                $stmt->bindParam(':id', $exclude_id);
            } else {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM boissons WHERE nom = :nom");
                $stmt->bindParam(':nom', $name);
            }
            
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['count'] > 0;
            
        } catch(PDOException $e) {
            logAction('Erreur vérification nom produit: ' . $e->getMessage());
            return false;
        }
    }
}

// Fonction pour exporter les produits en CSV
if (!function_exists('exportProductsToCSV')) {
    function exportProductsToCSV($conn) {
        try {
            $stmt = $conn->query("SELECT * FROM boissons ORDER BY categorie, nom");
            $products = $stmt->fetchAll();
            
            $csv = "ID;Nom;Description;Prix;Catégorie;Stock;Date création\n";
            
            foreach ($products as $product) {
                $csv .= $product['id'] . ';' .
                       str_replace(';', ',', $product['nom']) . ';' .
                       str_replace(';', ',', $product['description']) . ';' .
                       $product['prix'] . ';' .
                       $product['categorie'] . ';' .
                       $product['stock'] . ';' .
                       $product['created_at'] . "\n";
            }
            
            return $csv;
            
        } catch(PDOException $e) {
            logAction('Erreur export CSV: ' . $e->getMessage());
            return false;
        }
    }
}

// Fonction pour créer une pagination
if (!function_exists('paginateResults')) {
    function paginateResults($conn, $table, $page = 1, $per_page = 10, $conditions = '') {
        $offset = ($page - 1) * $per_page;
        
        try {
            // Compter le nombre total d'éléments
            $count_sql = "SELECT COUNT(*) as total FROM $table";
            if (!empty($conditions)) {
                $count_sql .= " WHERE $conditions";
            }
            
            $stmt = $conn->query($count_sql);
            $total_result = $stmt->fetch();
            $total = $total_result['total'];
            
            // Calculer le nombre total de pages
            $total_pages = ceil($total / $per_page);
            
            // Récupérer les données pour la page actuelle
            $sql = "SELECT * FROM $table";
            if (!empty($conditions)) {
                $sql .= " WHERE $conditions";
            }
            $sql .= " LIMIT $per_page OFFSET $offset";
            
            $stmt = $conn->query($sql);
            $results = $stmt->fetchAll();
            
            return [
                'data' => $results,
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => $total_pages,
                'has_previous' => $page > 1,
                'has_next' => $page < $total_pages
            ];
            
        } catch(PDOException $e) {
            logAction('Erreur pagination: ' . $e->getMessage());
            return false;
        }
    }
}

// Fonction pour générer un slug URL-friendly
if (!function_exists('generateSlug')) {
    function generateSlug($string) {
        $slug = strtolower($string);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
}

// Fonction pour envoyer une notification par email (optionnel)
if (!function_exists('sendNotification')) {
    function sendNotification($to, $subject, $message) {
        $headers = "From: admin@boissonsdelight.fr\r\n";
        $headers .= "Reply-To: no-reply@boissonsdelight.fr\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
}

// Fonction pour générer un mot de passe aléatoire
if (!function_exists('generateRandomPassword')) {
    function generateRandomPassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
}

// Fonction pour vérifier les permissions (pour extensions futures)
if (!function_exists('checkPermission')) {
    function checkPermission($permission) {
        // Pour l'instant, tous les admins ont tous les droits
        // À étendre si besoin de plusieurs niveaux d'administration
        return isAdminLoggedIn();
    }
}

// Fonction pour créer une miniature d'image
if (!function_exists('createThumbnail')) {
    function createThumbnail($source_path, $dest_path, $max_width = 200, $max_height = 200) {
        if (!file_exists($source_path)) {
            return false;
        }
        
        $info = getimagesize($source_path);
        if (!$info) {
            return false;
        }
        
        $type = $info[2];
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($source_path);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($source_path);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($source_path);
                break;
            default:
                return false;
        }
        
        $src_width = imagesx($source);
        $src_height = imagesy($source);
        
        // Calculer les nouvelles dimensions
        $ratio = min($max_width / $src_width, $max_height / $src_height);
        $new_width = round($src_width * $ratio);
        $new_height = round($src_height * $ratio);
        
        // Créer la nouvelle image
        $thumbnail = imagecreatetruecolor($new_width, $new_height);
        
        // Préserver la transparence pour PNG et GIF
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagecolortransparent($thumbnail, imagecolorallocatealpha($thumbnail, 0, 0, 0, 127));
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }
        
        // Redimensionner
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);
        
        // Sauvegarder
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumbnail, $dest_path, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumbnail, $dest_path, 9);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumbnail, $dest_path);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($thumbnail);
        
        return true;
    }
}

// Fonction de sécurité pour vérifier la session
if (!function_exists('checkSessionSecurity')) {
    function checkSessionSecurity() {
        // Vérifier l'IP et le user agent pour plus de sécurité
        if (isset($_SESSION['ip']) && $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
            session_destroy();
            header('Location: login.php?error=session_security');
            exit();
        }
        
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            session_destroy();
            header('Location: login.php?error=session_security');
            exit();
        }
    }
}

// Vérifier la sécurité de la session si admin est connecté
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    checkSessionSecurity();
}
?>