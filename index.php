<?php
session_start();
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    
    $sql = "SELECT * FROM boissons ORDER BY categorie, nom";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $boissons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $boissonsParCategorie = [];
    foreach ($boissons as $boisson) {
        $categorie = $boisson['categorie'];
        if (!isset($boissonsParCategorie[$categorie])) {
            $boissonsParCategorie[$categorie] = [];
        }
        $boissonsParCategorie[$categorie][] = $boisson;
    }
} catch(PDOException $e) {
    $error = "Erreur de connexion à la base de données";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boissons Delight - Votre boutique de boissons premium</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="index.php" class="logo">
                    <i class="fas fa-wine-glass-alt"></i>
                    <span>Boissons<span class="logo-highlight">Delight</span></span>
                </a>
            </div>
            
            <div class="nav-menu">
                <a href="index.php" class="nav-link active">Accueil</a>
                <a href="#categories" class="nav-link">Catégories</a>
                <a href="#featured" class="nav-link">Produits phares</a>
                <a href="#video" class="nav-link">Notre univers</a>
                <a href="admin.php" class="nav-link admin-btn">Admin</a>
            </div>
            
            <div class="nav-actions">
                <button class="nav-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="container hero-content animate__animated animate__fadeInUp">
            <h1 class="hero-title">Découvrez l'Excellence du Goût</h1>
            <p class="hero-subtitle">Des boissons premium soigneusement sélectionnées pour vos moments de plaisir</p>
            <div class="hero-cta">
                <a href="#categories" class="btn btn-primary btn-large">
                    Explorer la collection
                </a>
                <a href="#featured" class="btn btn-outline">
                    Voir les exclusivités
                </a>
            </div>
        </div>
        
        <div class="hero-stats">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div>
                            <h3>50+</h3>
                            <p>Boissons uniques</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div>
                            <h3>100%</h3>
                            <p>Naturel & Bio</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div>
                            <h3>24h</h3>
                            <p>Livraison express</p>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div>
                            <h3>1000+</h3>
                            <p>Clients satisfaits</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section id="featured" class="section featured-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Nos <span class="highlight">Exclusivités</span></h2>
                <p class="section-subtitle">Découvrez nos produits les plus prisés</p>
            </div>
            
            <div class="featured-grid">
                <?php if (!empty($boissons)): 
                    $featuredProducts = array_slice($boissons, 0, 3); ?>
                    <?php foreach ($featuredProducts as $boisson): ?>
                    <div class="featured-card animate__animated animate__fadeIn">
                        <div class="featured-badge">
                            Populaire
                        </div>
                        <div class="featured-img">
                            <?php if ($boisson['image_url']): ?>
                                <img src="assets/images/<?php echo htmlspecialchars($boisson['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($boisson['nom']); ?>"
                                     onerror="this.src='assets/images/default-drink.jpg'">
                            <?php else: ?>
                                <img src="assets/images/default-drink.jpg" alt="<?php echo htmlspecialchars($boisson['nom']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="featured-content">
                            <span class="product-category"><?php echo htmlspecialchars($boisson['categorie']); ?></span>
                            <h3><?php echo htmlspecialchars($boisson['nom']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($boisson['description']); ?></p>
                            <div class="product-meta">
                                <div class="price-tag">
                                    <span class="price"><?php echo number_format($boisson['prix'], 0, ',', ' '); ?> FCFA</span>
                                    <?php if ($boisson['stock'] <= 5): ?>
                                    <span class="stock-warning">
                                        Stock limité
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="stock-info">
                                    <span class="stock <?php echo $boisson['stock'] > 10 ? 'in-stock' : 'low-stock'; ?>">
                                        <?php echo $boisson['stock']; ?> en stock
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Aucune boisson disponible</h3>
                        <p>Notre collection sera bientôt disponible</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section id="categories" class="section categories-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Nos <span class="highlight">Catégories</span></h2>
                <p class="section-subtitle">Explorez notre large sélection par type</p>
            </div>
            
            <div class="categories-grid">
                <div class="category-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h3>Sodas</h3>
                    <p>Boissons gazeuses rafraîchissantes</p>
                </div>
                <div class="category-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <h3>Eaux</h3>
                    <p>Pures et minérales</p>
                </div>
                <div class="category-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <h3>Jus</h3>
                    <p>100% fruits pressés</p>
                </div>
                <div class="category-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <h3>Cafés</h3>
                    <p>Arabica & Robusta</p>
                </div>
                <div class="category-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <h3>Thés</h3>
                    <p>Infusions naturelles</p>
                </div>
                <div class="category-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                    <h3>Énergisantes</h3>
                    <p>Boost d'énergie</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Grid -->
    <section class="section products-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Toute notre <span class="highlight">Collection</span></h2>
                <p class="section-subtitle">Découvrez l'intégralité de notre sélection premium</p>
            </div>
            
            <?php if (!empty($boissonsParCategorie)): ?>
                <?php foreach ($boissonsParCategorie as $categorie => $boissonsCategorie): ?>
                <div class="category-block">
                    <div class="category-header">
                        <h3 class="category-title">
                            <?php echo htmlspecialchars($categorie); ?>
                            <span class="product-count">(<?php echo count($boissonsCategorie); ?> produits)</span>
                        </h3>
                    </div>
                    
                    <div class="products-grid">
                        <?php foreach ($boissonsCategorie as $boisson): ?>
                        <div class="product-card animate__animated animate__fadeIn">
                            <div class="product-image">
                                <?php if ($boisson['image_url']): ?>
                                    <img src="assets/images/<?php echo htmlspecialchars($boisson['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($boisson['nom']); ?>"
                                         onerror="this.src='assets/images/default-drink.jpg'">
                                <?php else: ?>
                                    <img src="assets/images/default-drink.jpg" alt="<?php echo htmlspecialchars($boisson['nom']); ?>">
                                <?php endif; ?>
                                
                                <?php if ($boisson['stock'] <= 5): ?>
                                <div class="product-badge badge-danger">
                                    Bientôt épuisé
                                </div>
                                <?php elseif ($boisson['prix'] < 1000): ?>
                                <div class="product-badge badge-success">
                                    Bonne affaire
                                </div>
                                <?php endif; ?>
                                
                                <div class="product-actions">
                                    <button class="action-btn wishlist-btn">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="product-info">
                                <span class="product-category"><?php echo htmlspecialchars($boisson['categorie']); ?></span>
                                <h4 class="product-name"><?php echo htmlspecialchars($boisson['nom']); ?></h4>
                                <p class="product-description"><?php echo htmlspecialchars($boisson['description']); ?></p>
                                
                                <div class="product-footer">
                                    <div class="product-price">
                                        <span class="current-price"><?php echo number_format($boisson['prix'], 0, ',', ' '); ?> FCFA</span>
                                    </div>
                                    
                                    <div class="product-stock">
                                        <div class="stock-indicator <?php echo $boisson['stock'] > 20 ? 'high' : ($boisson['stock'] > 5 ? 'medium' : 'low'); ?>">
                                            <span class="stock-dot"></span>
                                            <span class="stock-text">
                                                <?php echo $boisson['stock'] > 20 ? 'Disponible' : ($boisson['stock'] > 5 ? 'Stock limité' : 'Bientôt épuisé'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state large">
                    <h3>Notre collection est vide</h3>
                    <p>Revenez bientôt pour découvrir nos nouvelles boissons</p>
                    <a href="admin.php" class="btn btn-primary">
                        Ajouter des produits
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA Section - FOND VIOLET CLAIR -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Prêt à découvrir l'excellence ?</h2>
                <p class="cta-text">Rejoignez nos milliers de clients satisfaits et découvrez la différence BoissonsDelight</p>
                <div class="cta-buttons">
                    <a href="#categories" class="btn btn-light btn-large">
                        Commander maintenant
                    </a>
                    <a href="#" class="btn btn-outline-light">
                        En savoir plus
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Bannière Publicitaire (mini-vidéo gauche + description droite) -->
    <section id="video" class="section ad-banner">
        <div class="container">
            <div class="ad-banner-wrapper">
                <!-- Mini-vidéo à gauche -->
                <div class="ad-video video-lazy" onclick="loadVideo(this)">
                    <img src="https://img.youtube.com/vi/dWJVmWb8Hes/maxresdefault.jpg" 
                         alt="Aperçu vidéo - Squeezie présente Ciao Kombucha"
                         class="video-poster">
                    <button class="video-play-btn-mini" aria-label="Lire la vidéo">
                        <i class="fas fa-play"></i>
                    </button>
                </div>
                
                <!-- Description à droite -->
                <div class="ad-content">
                    <span class="ad-badge">Sponsorisé</span>
                    <h3 class="ad-title">Découvrez Ciao Kombucha</h3>
                    <p class="ad-description">
                        Squeezie vous présente Ciao Kombucha, la nouvelle alternative saine et rafraîchissante aux sodas. 
                        Naturellement pétillante, faible en sucre et riche en probiotiques.
                    </p>
                    <div class="ad-actions">
                        <a href="#" class="btn-primary">
                            En savoir plus
                        </a>
                        <a href="#" class="ad-link">
                            Voir la vidéo complète
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <i class="fas fa-wine-glass-alt"></i>
                        <span>Boissons<span class="logo-highlight">Delight</span></span>
                    </div>
                    <p class="footer-description">
                        Votre destination premium pour des boissons d'exception. Qualité, fraîcheur et plaisir garantis.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h4 class="footer-title">Catégories</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Sodas</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Eaux</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Jus de fruits</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Cafés</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Thés & Infusions</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4 class="footer-title">Liens rapides</h4>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-chevron-right"></i> Accueil</a></li>
                        <li><a href="#featured"><i class="fas fa-chevron-right"></i> Produits phares</a></li>
                        <li><a href="#categories"><i class="fas fa-chevron-right"></i> Catégories</a></li>
                        <li><a href="#video"><i class="fas fa-chevron-right"></i> Notre univers</a></li>
                        <li><a href="admin.php"><i class="fas fa-chevron-right"></i> Administration</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4 class="footer-title">Contact</h4>
                    <div class="contact-info">
                        <p><i class="fas fa-map-marker-alt"></i> 123 Avenue des Boissons, Lomé, Togo</p>
                        <p><i class="fas fa-phone"></i> +228 70512027</p>
                        <p><i class="fas fa-envelope"></i> contact@boissonsdelight.ci</p>
                        <p><i class="fas fa-clock"></i> Lun-Sam: 9h-20h</p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> BoissonsDelight. Tous droits réservés.</p>
                <div class="footer-legal">
                    <a href="#">Mentions légales</a>
                    <a href="#">Politique de confidentialité</a>
                    <a href="#">Conditions générales</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top -->
    <button class="back-to-top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script>
        // Navigation mobile
        document.querySelector('.nav-toggle').addEventListener('click', function() {
            document.querySelector('.nav-menu').classList.toggle('active');
            this.querySelector('i').classList.toggle('fa-bars');
            this.querySelector('i').classList.toggle('fa-times');
        });

        // Back to top
        const backToTop = document.querySelector('.back-to-top');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });

        backToTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Wishlist button animation
        document.querySelectorAll('.wishlist-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const icon = this.querySelector('i');
                icon.classList.toggle('far');
                icon.classList.toggle('fas');
                icon.classList.toggle('heart-animation');
                
                setTimeout(() => {
                    icon.classList.remove('heart-animation');
                }, 300);
            });
        });

        // Product card animation on scroll
        const observerOptions = { threshold: 0.1 };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__fadeInUp');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.product-card').forEach(card => {
            observer.observe(card);
        });

        // ===== CHARGEMENT VIDÉO YOUTUBE (LAZY LOAD) =====
        function loadVideo(container) {
            const videoId = 'dWJVmWb8Hes';
            container.innerHTML = `
                <iframe 
                    src="https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1" 
                    title="Squeezie présente Ciao Kombucha - Boissons Delight"
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    allowfullscreen>
                </iframe>
            `;
            container.classList.remove('video-lazy');
            container.style.cursor = 'default';
            container.onclick = null;
        }
    </script>
</body>
</html>