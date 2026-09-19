<?php
session_start();
require_once 'config/database.php';
requireAdminLogin();

// Token CSRF pour les formulaires
$csrf_token = generateCSRFToken();

// Connexion à la base de données
$conn = getDBConnection();

// Variables
$error = $success = '';
$editing = false;
$id_to_edit = 0;
$nom = $description = $prix = $categorie = $image_url = $stock = '';

// Catégories
$categories = ['Sodas', 'Eaux', 'Jus', 'Cafés', 'Thés', 'Énergisantes', 'Alcoolisées', 'Smoothies'];

// Dossier pour les images
$upload_dir = 'assets/images/';

// Traitement du formulaire de produit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Token de sécurité invalide.';
    } else {
        $nom = trim($_POST['nom']);
        $description = trim($_POST['description']);
        $prix = trim($_POST['prix']);
        $categorie = $_POST['categorie'];
        $stock = trim($_POST['stock']);
        
        // Gestion de l'upload d'image
        $image_url = $_POST['image_url_current'] ?? '';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 2 * 1024 * 1024;
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = 'Type de fichier non autorisé. Formats acceptés: JPG, PNG, GIF, WebP.';
            } elseif ($file['size'] > $max_size) {
                $error = 'Fichier trop volumineux. Taille max: 2MB.';
            } else {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $nom) . '.' . $extension;
                $target_path = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $image_url = $filename;
                    
                    if (isset($_POST['image_url_current']) && !empty($_POST['image_url_current']) && 
                        $_POST['image_url_current'] !== 'default-drink.jpg') {
                        $old_image = $upload_dir . $_POST['image_url_current'];
                        if (file_exists($old_image)) {
                            unlink($old_image);
                        }
                    }
                } else {
                    $error = 'Erreur lors du téléchargement de l\'image.';
                }
            }
        } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
            if (!empty($_POST['image_url_current']) && $_POST['image_url_current'] !== 'default-drink.jpg') {
                $old_image = $upload_dir . $_POST['image_url_current'];
                if (file_exists($old_image)) {
                    unlink($old_image);
                }
                $image_url = 'default-drink.jpg';
            }
        } elseif (isset($_POST['image_url_current'])) {
            $image_url = $_POST['image_url_current'];
        }
        
        // Validation
        if (empty($nom) || empty($prix) || empty($categorie)) {
            $error = 'Veuillez remplir tous les champs obligatoires.';
        } elseif (!is_numeric($prix) || $prix <= 0) {
            $error = 'Le prix doit être un nombre positif en FCFA.';
        } elseif (empty($image_url)) {
            $image_url = 'default-drink.jpg';
        } else {
            if (isset($_POST['ajouter'])) {
                $sql = "INSERT INTO boissons (nom, description, prix, categorie, image_url, stock) 
                        VALUES (:nom, :description, :prix, :categorie, :image_url, :stock)";
                $stmt = $conn->prepare($sql);
                $params = [
                    ':nom' => $nom,
                    ':description' => $description,
                    ':prix' => $prix,
                    ':categorie' => $categorie,
                    ':image_url' => $image_url,
                    ':stock' => $stock
                ];
                
                if ($stmt->execute($params)) {
                    $success = 'Boisson ajoutée avec succès!';
                    logAction('Ajout produit: ' . $nom);
                    
                    if (!isset($_POST['image_url_current'])) {
                        $nom = $description = $prix = $categorie = $stock = '';
                        $image_url = '';
                    }
                } else {
                    $error = 'Erreur lors de l\'ajout.';
                }
            } elseif (isset($_POST['modifier'])) {
                $id_to_edit = $_POST['id'];
                $sql = "UPDATE boissons SET nom = :nom, description = :description, prix = :prix, 
                        categorie = :categorie, image_url = :image_url, stock = :stock 
                        WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $params = [
                    ':id' => $id_to_edit,
                    ':nom' => $nom,
                    ':description' => $description,
                    ':prix' => $prix,
                    ':categorie' => $categorie,
                    ':image_url' => $image_url,
                    ':stock' => $stock
                ];
                
                if ($stmt->execute($params)) {
                    $success = 'Boisson modifiée avec succès!';
                    $editing = false;
                    logAction('Modification produit ID: ' . $id_to_edit);
                } else {
                    $error = 'Erreur lors de la modification.';
                }
            }
        }
    }
}

// Suppression avec vérification CSRF
if (isset($_GET['supprimer']) && isset($_GET['token'])) {
    if (verifyCSRFToken($_GET['token'])) {
        $id = $_GET['supprimer'];
        
        $stmt = $conn->prepare("SELECT nom, image_url FROM boissons WHERE id = ?");
        $stmt->execute([$id]);
        $produit = $stmt->fetch();
        
        if ($produit && $produit['image_url'] && $produit['image_url'] !== 'default-drink.jpg') {
            $image_path = $upload_dir . $produit['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM boissons WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = 'Boisson supprimée!';
            logAction('Suppression produit: ' . ($produit['nom'] ?? 'ID: ' . $id));
        } else {
            $error = 'Erreur lors de la suppression.';
        }
    } else {
        $error = 'Token de sécurité invalide.';
    }
}

// Édition
if (isset($_GET['editer'])) {
    $id = $_GET['editer'];
    $stmt = $conn->prepare("SELECT * FROM boissons WHERE id = ?");
    $stmt->execute([$id]);
    $boisson = $stmt->fetch();
    
    if ($boisson) {
        $editing = true;
        $id_to_edit = $boisson['id'];
        $nom = $boisson['nom'];
        $description = $boisson['description'];
        $prix = $boisson['prix'];
        $categorie = $boisson['categorie'];
        $image_url = $boisson['image_url'];
        $stock = $boisson['stock'];
    }
}

// Récupérer les boissons
$stmt = $conn->query("SELECT * FROM boissons ORDER BY id DESC");
$boissons = $stmt->fetchAll();

// Récupérer les statistiques
$stats = [];
$stats_stmt = $conn->query("SELECT COUNT(*) as total, SUM(stock) as total_stock FROM boissons");
$stats = $stats_stmt->fetch();

// Fonction de journalisation
function logAction($action) {
    $log = date('Y-m-d H:i:s') . ' - ' . $_SESSION['admin_username'] . ' - ' . $action . PHP_EOL;
    file_put_contents('admin_logs.txt', $log, FILE_APPEND);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Boissons Delight</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .image-preview-container {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            margin-top: 10px;
        }
        
        .current-image {
            max-width: 150px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .upload-controls {
            flex: 1;
        }
        
        .file-input-wrapper {
            position: relative;
            margin-bottom: 10px;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-input-label {
            display: block;
            padding: 10px 15px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-input-label:hover {
            background: #e9ecef;
            border-color: #667eea;
        }
        
        .file-input-label i {
            margin-right: 8px;
            color: #667eea;
        }
        
        .remove-image-btn {
            background: #fee;
            color: #c53030;
            border: 1px solid #fed7d7;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .remove-image-btn:hover {
            background: #fed7d7;
        }
        
        .image-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
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
                <a href="admin.php" class="nav-item active">
                    <i class="fas fa-wine-bottle"></i> Gestion des produits
                </a>
                <a href="manage_admins.php" class="nav-item">
                    <i class="fas fa-users"></i> Gestion des admins
                </a>
                <a href="change_password.php" class="nav-item">
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

        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1>Gestion des Boissons</h1>
                    <p>Bienvenue, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
                </div>
                <div class="header-right">
                    <div class="header-stats">
                        <div class="stat-card">
                            <i class="fas fa-box"></i>
                            <div>
                                <h3><?php echo $stats['total'] ?? 0; ?></h3>
                                <p>Produits</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-cubes"></i>
                            <div>
                                <h3><?php echo $stats['total_stock'] ?? 0; ?></h3>
                                <p>Stock total</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

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
                    <h2><?php echo $editing ? 'Modifier une boisson' : 'Ajouter une nouvelle boisson'; ?></h2>
                    <a href="admin.php" class="btn btn-outline <?php echo $editing ? '' : 'hidden'; ?>">
                        <i class="fas fa-plus"></i> Nouveau produit
                    </a>
                </div>
                
                <form method="POST" class="admin-form" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <?php if ($editing): ?>
                    <input type="hidden" name="id" value="<?php echo $id_to_edit; ?>">
                    <input type="hidden" name="image_url_current" value="<?php echo htmlspecialchars($image_url ?? ''); ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nom">
                                <i class="fas fa-tag"></i> Nom *
                            </label>
                            <input type="text" id="nom" name="nom" 
                                   value="<?php echo htmlspecialchars($nom ?? ''); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="prix">
                                <i class="fas fa-coins"></i> Prix (FCFA) *
                            </label>
                            <input type="number" id="prix" name="prix" step="1" min="0" 
                                   value="<?php echo htmlspecialchars($prix ?? '0'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="categorie">
                                <i class="fas fa-list"></i> Catégorie *
                            </label>
                            <select id="categorie" name="categorie" required>
                                <option value="">Choisir...</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" 
                                        <?php echo (isset($categorie) && $categorie === $cat) ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock">
                                <i class="fas fa-boxes"></i> Stock *
                            </label>
                            <input type="number" id="stock" name="stock" min="0" 
                                   value="<?php echo htmlspecialchars($stock ?? '0'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">
                            <i class="fas fa-align-left"></i> Description
                        </label>
                        <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-image"></i> Image du produit
                        </label>
                        
                        <div class="image-preview-container">
                            <?php if (isset($image_url) && $image_url && file_exists($upload_dir . $image_url)): ?>
                            <div>
                                <img src="assets/images/<?php echo htmlspecialchars($image_url); ?>" 
                                     alt="Image actuelle" 
                                     class="current-image"
                                     onerror="this.src='assets/images/default-drink.jpg'">
                                <?php if ($editing && $image_url !== 'default-drink.jpg'): ?>
                                <button type="button" class="remove-image-btn" onclick="removeImage()">
                                    <i class="fas fa-trash"></i> Supprimer cette image
                                </button>
                                <input type="hidden" name="remove_image" id="remove_image" value="0">
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="upload-controls">
                                <div class="file-input-wrapper">
                                    <input type="file" id="image" name="image" accept="image/*" 
                                           onchange="previewImage(this)">
                                    <label for="image" class="file-input-label">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <?php echo $editing ? 'Changer l\'image' : 'Choisir une image'; ?>
                                    </label>
                                </div>
                                
                                <div class="image-preview" id="imagePreview" style="display: none;">
                                    <img id="previewImg" src="" alt="Aperçu" style="max-width: 150px; border-radius: 8px;">
                                </div>
                                
                                <div class="image-info">
                                    <i class="fas fa-info-circle"></i>
                                    Formats acceptés: JPG, PNG, GIF, WebP | Max: 2MB
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <?php if ($editing): ?>
                        <button type="submit" name="modifier" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                        <?php else: ?>
                        <button type="submit" name="ajouter" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Ajouter le produit
                        </button>
                        <?php endif; ?>
                        <button type="reset" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-redo"></i> Réinitialiser
                        </button>
                    </div>
                </form>
            </section>

            <section class="admin-section">
                <div class="section-header">
                    <h2>Liste des produits (<?php echo count($boissons); ?>)</h2>
                    <div class="section-actions">
                        <button class="btn btn-outline" onclick="exportProducts()">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                        <button class="btn btn-outline" onclick="printProducts()">
                            <i class="fas fa-print"></i> Imprimer
                        </button>
                    </div>
                </div>
                
                <?php if (empty($boissons)): ?>
                <div class="empty-state">
                    <i class="fas fa-wine-bottle fa-3x"></i>
                    <h3>Aucun produit trouvé</h3>
                    <p>Commencez par ajouter votre première boisson</p>
                </div>
                <?php else: ?>
                <div class="products-table-container">
                    <div class="table-actions">
                        <input type="text" id="searchInput" placeholder="Rechercher un produit..." class="search-input">
                        <select id="categoryFilter" class="filter-select">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Produit</th>
                                    <th>Catégorie</th>
                                    <th>Prix</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($boissons as $boisson): ?>
                                <tr>
                                    <td>#<?php echo $boisson['id']; ?></td>
                                    <td>
                                        <div class="product-info-table">
                                            <img src="assets/images/<?php echo htmlspecialchars($boisson['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($boisson['nom']); ?>"
                                                 onerror="this.src='assets/images/default-drink.jpg'">
                                            <div>
                                                <strong><?php echo htmlspecialchars($boisson['nom']); ?></strong>
                                                <small><?php echo htmlspecialchars(substr($boisson['description'], 0, 50)); ?>...</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-tag"><?php echo htmlspecialchars($boisson['categorie']); ?></span>
                                    </td>
                                    <td>
                                        <span class="price-tag"><?php echo number_format($boisson['prix'], 0, ',', ' '); ?> FCFA</span>
                                    </td>
                                    <td>
                                        <div class="stock-indicator-bar">
                                            <div class="stock-bar <?php echo $boisson['stock'] > 20 ? 'high' : ($boisson['stock'] > 5 ? 'medium' : 'low'); ?>">
                                            </div>
                                            <span class="stock-count"><?php echo $boisson['stock']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="admin.php?editer=<?php echo $boisson['id']; ?>" 
                                               class="btn-action edit" 
                                               title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="admin.php?supprimer=<?php echo $boisson['id']; ?>&token=<?php echo $csrf_token; ?>" 
                                               class="btn-action delete" 
                                               title="Supprimer"
                                               onclick="return confirmDelete()">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="table-footer">
                        <div class="pagination">
                            <button class="page-btn" disabled>Précédent</button>
                            <span class="page-info">Page 1 sur 1</span>
                            <button class="page-btn" disabled>Suivant</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function removeImage() {
            if (confirm('Voulez-vous vraiment supprimer cette image ?')) {
                document.getElementById('remove_image').value = '1';
                document.querySelector('.current-image').style.display = 'none';
                document.querySelector('.remove-image-btn').style.display = 'none';
                document.getElementById('imagePreview').style.display = 'block';
                document.getElementById('previewImg').src = '';
            }
        }
        
        function resetForm() {
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('previewImg').src = '';
            if (document.getElementById('remove_image')) {
                document.getElementById('remove_image').value = '0';
            }
        }
        
        function confirmDelete() {
            return confirm('Êtes-vous sûr de vouloir supprimer cette boisson ? Cette action est irréversible.');
        }
        
        document.getElementById('searchInput').addEventListener('input', function() {
            filterProducts();
        });
        
        document.getElementById('categoryFilter').addEventListener('change', function() {
            filterProducts();
        });
        
        function filterProducts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value.toLowerCase();
            const rows = document.querySelectorAll('.products-table tbody tr');
            
            rows.forEach(row => {
                const productName = row.querySelector('.product-info-table strong').textContent.toLowerCase();
                const productCategory = row.querySelector('.category-tag').textContent.toLowerCase();
                
                const matchesSearch = productName.includes(searchTerm);
                const matchesCategory = !category || productCategory === category;
                
                row.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
            });
        }
        
        function exportProducts() {
            const table = document.querySelector('.products-table');
            const rows = table.querySelectorAll('tr');
            let csv = [];
            
            rows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('th, td');
                
                cells.forEach(cell => {
                    if (!cell.querySelector('img') && !cell.querySelector('.action-buttons')) {
                        rowData.push(cell.innerText.replace(/,/g, ''));
                    }
                });
                
                if (rowData.length > 0) {
                    csv.push(rowData.join(','));
                }
            });
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'produits_boissons.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        function printProducts() {
            window.print();
        }
        
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>