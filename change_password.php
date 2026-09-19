<?php
session_start();
require_once 'config/database.php';
requireAdminLogin();

$error = $success = '';
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Token de sécurité invalide.';
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Veuillez remplir tous les champs.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Les nouveaux mots de passe ne correspondent pas.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
        } else {
            $conn = getDBConnection();
            
            // Vérifier le mot de passe actuel
            $stmt = $conn->prepare("SELECT password_hash FROM administrateurs WHERE id = :id");
            $stmt->bindParam(':id', $_SESSION['admin_id']);
            $stmt->execute();
            $admin = $stmt->fetch();
            
            if ($admin && verifyPassword($current_password, $admin['password_hash'])) {
                // Mettre à jour le mot de passe
                $new_hash = hashPassword($new_password);
                $update_stmt = $conn->prepare("UPDATE administrateurs SET password_hash = :hash WHERE id = :id");
                $update_stmt->bindParam(':hash', $new_hash);
                $update_stmt->bindParam(':id', $_SESSION['admin_id']);
                
                if ($update_stmt->execute()) {
                    $success = 'Mot de passe changé avec succès!';
                    logAction('Changement de mot de passe');
                    
                    // Déconnecter et rediriger vers la connexion
                    session_destroy();
                    header('Location: login.php?password_changed=1');
                    exit();
                } else {
                    $error = 'Erreur lors de la mise à jour du mot de passe.';
                }
            } else {
                $error = 'Mot de passe actuel incorrect.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer mot de passe - Admin Boissons Delight</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .password-strength {
            margin-top: 5px;
            height: 5px;
            border-radius: 3px;
            background: #e0e0e0;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        
        .strength-weak { background: #f56565; }
        .strength-medium { background: #ed8936; }
        .strength-strong { background: #48bb78; }
        
        .password-requirements {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        
        .requirement {
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .requirement.valid {
            color: #48bb78;
        }
        
        .requirement.invalid {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
                <div class="admin-info">
                    <i class="fas fa-user-circle"></i>
                    <div>
                        <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
                        <small>Administrateur</small>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-home"></i> Retour au site
                </a>
                <a href="admin.php" class="nav-item">
                    <i class="fas fa-wine-bottle"></i> Gestion des produits
                </a>
                <a href="change_password.php" class="nav-item active">
                    <i class="fas fa-key"></i> Changer mot de passe
                </a>
                <a href="logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="connection-status">
                    <i class="fas fa-circle connected"></i>
                    <span>Connecté</span>
                </div>
                <small>Dernière connexion: <?php echo date('H:i'); ?></small>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1>Changer le mot de passe</h1>
                    <p>Mettez à jour votre mot de passe pour plus de sécurité</p>
                </div>
            </header>

            <!-- Messages -->
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
            <?php endif; ?>

            <section class="admin-section">
                <div class="section-header">
                    <h2>Modification du mot de passe</h2>
                    <p>Pour des raisons de sécurité, vous serez déconnecté après avoir changé votre mot de passe.</p>
                </div>
                
                <form method="POST" class="admin-form" id="passwordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="current_password">
                            <i class="fas fa-lock"></i> Mot de passe actuel *
                        </label>
                        <div class="password-input">
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   placeholder="Entrez votre mot de passe actuel"
                                   required>
                            <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">
                            <i class="fas fa-key"></i> Nouveau mot de passe *
                        </label>
                        <div class="password-input">
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   placeholder="Entrez le nouveau mot de passe"
                                   required
                                   onkeyup="checkPasswordStrength()">
                            <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <div class="password-strength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        
                        <div class="password-requirements" id="passwordRequirements">
                            <div class="requirement invalid" id="reqLength">
                                <i class="fas fa-circle"></i> Au moins 6 caractères
                            </div>
                            <div class="requirement invalid" id="reqUppercase">
                                <i class="fas fa-circle"></i> Au moins une majuscule
                            </div>
                            <div class="requirement invalid" id="reqLowercase">
                                <i class="fas fa-circle"></i> Au moins une minuscule
                            </div>
                            <div class="requirement invalid" id="reqNumber">
                                <i class="fas fa-circle"></i> Au moins un chiffre
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-check-double"></i> Confirmer le nouveau mot de passe *
                        </label>
                        <div class="password-input">
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   placeholder="Confirmez le nouveau mot de passe"
                                   required
                                   onkeyup="checkPasswordMatch()">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="passwordMatch" style="font-size: 12px; margin-top: 5px;"></div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Changer le mot de passe
                        </button>
                        <a href="admin.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </form>
                
                <div class="security-tips" style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <h3><i class="fas fa-lightbulb"></i> Conseils de sécurité :</h3>
                    <ul style="margin-left: 20px; color: #666;">
                        <li>Utilisez au moins 8 caractères</li>
                        <li>Mélangez lettres, chiffres et symboles</li>
                        <li>N'utilisez pas de mots courants ou d'informations personnelles</li>
                        <li>Changez régulièrement votre mot de passe</li>
                        <li>N'utilisez pas le même mot de passe sur plusieurs sites</li>
                    </ul>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Afficher/masquer le mot de passe
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleBtn = passwordInput.nextElementSibling.querySelector('i');
            
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
        
        // Vérifier la force du mot de passe
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthBar = document.getElementById('strengthBar');
            const requirements = document.getElementById('passwordRequirements');
            
            let strength = 0;
            let messages = [];
            
            // Longueur
            const hasLength = password.length >= 6;
            document.getElementById('reqLength').className = hasLength ? 'requirement valid' : 'requirement invalid';
            if (hasLength) strength += 25;
            
            // Majuscule
            const hasUppercase = /[A-Z]/.test(password);
            document.getElementById('reqUppercase').className = hasUppercase ? 'requirement valid' : 'requirement invalid';
            if (hasUppercase) strength += 25;
            
            // Minuscule
            const hasLowercase = /[a-z]/.test(password);
            document.getElementById('reqLowercase').className = hasLowercase ? 'requirement valid' : 'requirement invalid';
            if (hasLowercase) strength += 25;
            
            // Chiffre
            const hasNumber = /[0-9]/.test(password);
            document.getElementById('reqNumber').className = hasNumber ? 'requirement valid' : 'requirement invalid';
            if (hasNumber) strength += 25;
            
            // Mettre à jour la barre de force
            strengthBar.style.width = strength + '%';
            
            if (strength < 50) {
                strengthBar.className = 'strength-bar strength-weak';
            } else if (strength < 75) {
                strengthBar.className = 'strength-bar strength-medium';
            } else {
                strengthBar.className = 'strength-bar strength-strong';
            }
        }
        
        // Vérifier la correspondance des mots de passe
        function checkPasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword === '') {
                matchDiv.innerHTML = '';
                matchDiv.style.color = '';
            } else if (password === confirmPassword) {
                matchDiv.innerHTML = '<i class="fas fa-check-circle" style="color: #48bb78;"></i> Les mots de passe correspondent';
                matchDiv.style.color = '#48bb78';
            } else {
                matchDiv.innerHTML = '<i class="fas fa-times-circle" style="color: #f56565;"></i> Les mots de passe ne correspondent pas';
                matchDiv.style.color = '#f56565';
            }
        }
        
        // Validation du formulaire
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                return false;
            }
            
            return true;
        });
        
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>