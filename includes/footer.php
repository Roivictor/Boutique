</main>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>BoissonsDelight</h3>
                    <p>Votre destination pour les meilleures boissons. Fraîches, délicieuses et toujours à portée de main.</p>
                </div>
                <div class="footer-section">
                    <h3>Catégories</h3>
                    <ul>
                        <li><a href="index.php#sodas">Sodas</a></li>
                        <li><a href="index.php#eaux">Eaux</a></li>
                        <li><a href="index.php#jus">Jus</a></li>
                        <li><a href="index.php#cafes">Cafés</a></li>
                        <li><a href="index.php#thes">Thés</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Rue des Boissons, Paris</p>
                    <p><i class="fas fa-phone"></i> +33 1 23 45 67 89</p>
                    <p><i class="fas fa-envelope"></i> contact@boissonsdelight.fr</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> BoissonsDelight. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Menu hamburger pour mobile
        const hamburger = document.querySelector('.hamburger');
        const navMenu = document.querySelector('.nav-menu');
        
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
        
        // Fermer le menu en cliquant sur un lien
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
            });
        });
    </script>
</body>
</html>