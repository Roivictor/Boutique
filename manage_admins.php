<?php
session_start();
require_once 'config/database.php';
requireAdminLogin();

// Vérifier si l'admin a les droits
if ($_SESSION['admin_username'] !== 'admin') {
    header('Location: admin.php');
    exit();
}

$conn = getDBConnection();
$error = $success = '';

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_admin'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($password !== $confirm_password) {
            $error = 'Les mots de passe ne correspondent pas.';
        } elseif (empty($username) || empty($email) || empty($password)) {
            $error = 'Veuillez remplir tous les champs.';
        } else {
            // Vérifier si l'utilisateur existe déjà
            $check = $conn->prepare("SELECT id FROM administrateurs WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);
            
            if ($check->fetch()) {
                $error = 'Ce nom d\'utilisateur ou cet email est déjà utilisé.';
            } else {
                $hash = hashPassword($password);
                $stmt = $conn->prepare("INSERT INTO administrateurs (username, password_hash, email) VALUES (?, ?, ?)");
                if ($stmt->execute([$username, $hash, $email])) {
                    $success = 'Administrateur ajouté avec succès.';
                } else {
                    $error = 'Erreur lors de l\'ajout.';
                }
            }
        }
    }
}

// Récupérer les administrateurs
try {
    $stmt = $conn->query("SELECT id, username, email, created_at, last_login FROM administrateurs");
    $admins = $stmt->fetchAll();
} catch (PDOException $e) {
    $stmt = $conn->query("SELECT id, username, email, created_at, last_login FROM administrateurs");
    $admins = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Administrateurs - Boissons Delight</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-top: 2rem;
        }
        
        .admin-table thead {
            background: #f8fafc;
        }
        
        .admin-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        
        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        
        .admin-table tbody tr:hover {
            background: #f8fafc;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-badge.active {
            background: #c6f6d5;
            color: #276749;
        }
        
        .status-badge.inactive {
            background: #fed7d7;
            color: #c53030;
        }
        
        .admin-form {
            background: #f8fafc;
            padding: 2rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
        }
        
        .admin-form h3 {
            margin-bottom: 1.5rem;
            color: var(--dark);
        }
        
        .admin-form input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            margin-bottom: 1rem;
            transition: var(--transition);
        }
        
        .admin-form input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <div class="admin-info">
                    <div>
                        <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
                        <small>Administrateur</small>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    Retour au site
                </a>
                <a href="admin.php" class="nav-item">
                    Gestion des produits
                </a>
                <a href="manage_admins.php" class="nav-item active">
                    Gestion des admins
                </a>
                <a href="change_password.php" class="nav-item">
                    Changer mot de passe
                </a>
                <a href="logout.php" class="nav-item logout">
                    Déconnexion
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

        <main class="admin-main">
            <h1>Gestion des Administrateurs</h1>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
                <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
            <?php endif; ?>
            
            <!-- Formulaire d'ajout -->
            <section class="admin-section">
                <form method="POST" class="admin-form">
                    <h3>Ajouter un administrateur</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Nom d'utilisateur</label>
                            <input type="text" name="username" id="username" placeholder="Nom d'utilisateur" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" placeholder="Email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <input type="password" name="password" id="password" placeholder="Mot de passe" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmer</label>
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirmer le mot de passe" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_admin" class="btn btn-primary">
                        Ajouter l'administrateur
                    </button>
                </form>
            </section>
            
            <!-- Liste des admins -->
            <section class="admin-section">
                <h2>Liste des administrateurs (<?php echo count($admins); ?>)</h2>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Dernière connexion</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($admin['id']); ?></td>
                            <td><?php echo htmlspecialchars($admin['username']); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td>
                                <?php 
                                echo $admin['last_login'] 
                                    ? date('d/m/Y H:i', strtotime($admin['last_login'])) 
                                    : 'Jamais'; 
                                ?>
                            </td>
                            <td>
                                <span class="status-badge active">Actif</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>