<?php
session_start();
require_once 'config/database.php';

$error = '';
$username = '';

// Si déjà connecté, rediriger vers admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit();
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $conn = getDBConnection();
        
        // Rechercher l'administrateur
        $stmt = $conn->prepare("SELECT * FROM administrateurs WHERE username = :username AND is_active = 1");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin && verifyPassword($password, $admin['password_hash'])) {
            // Connexion réussie
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            
            // Mettre à jour la dernière connexion
            $updateStmt = $conn->prepare("UPDATE administrateurs SET last_login = NOW() WHERE id = :id");
            $updateStmt->bindParam(':id', $admin['id']);
            $updateStmt->execute();
            
            // Redirection
            $redirect_url = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'admin.php';
            unset($_SESSION['redirect_url']);
            header('Location: ' . $redirect_url);
            exit();
        } else {
            $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - Boissons Delight</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card animate__animated animate__fadeIn">
            <div class="login-header">
                <a href="index.php" class="login-logo">
                    <i class="fas fa-wine-glass-alt"></i>
                    <span>Boissons<span class="logo-highlight">Delight</span></span>
                </a>
                <h2>Connexion Administration</h2>
                <p>Accès sécurisé au panneau d'administration</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Nom d'utilisateur
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?php echo htmlspecialchars($username); ?>"
                           placeholder="Entrez votre nom d'utilisateur"
                           required
                           autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Mot de passe
                    </label>
                    <div class="password-input">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder="Entrez votre mot de passe"
                               required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox">
                        <input type="checkbox" name="remember">
                        <span>Se souvenir de moi</span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>
            
            <div class="login-footer">
                <p>
                    <a href="index.php">
                        <i class="fas fa-home"></i> Retour au site
                    </a>
                </p>
                <p class="login-info">
                    <i class="fas fa-info-circle"></i> 
                    Identifiants par défaut : admin / admin123
                </p>
                <div class="security-info">
                    <i class="fas fa-shield-alt"></i> 
                    Connexion sécurisée avec cryptage
                </div>
            </div>
        </div>
        
        <!-- Security Badges -->
        <div class="security-badges">
            <div class="badge">
                <i class="fas fa-lock"></i>
                <span>HTTPS Sécurisé</span>
            </div>
            <div class="badge">
                <i class="fas fa-database"></i>
                <span>Données cryptées</span>
            </div>
            <div class="badge">
                <i class="fas fa-user-shield"></i>
                <span>Accès restreint</span>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
            }
        }
        
        // Auto-hide error message after 5 seconds
        setTimeout(() => {
            const errorAlert = document.querySelector('.alert-error');
            if (errorAlert) {
                errorAlert.style.opacity = '0';
                setTimeout(() => errorAlert.remove(), 300);
            }
        }, 5000);
        
        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>