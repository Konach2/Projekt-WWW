</div>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>TV Time Clone</h3>
                    <p>Śledź swoje ulubione seriale i dziel się opiniami z społecznością fanów telewizji.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Nawigacja</h4>
                    <ul>
                        <li><a href="index.php">Strona główna</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="my-shows.php">Moje seriale</a></li>
                            <li><a href="add-show.php">Dodaj serial</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Zaloguj się</a></li>
                            <li><a href="register.php">Zarejestruj się</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Kategorie</h4>
                    <ul>
                        <li><a href="index.php?genre=Drama">Dramat</a></li>
                        <li><a href="index.php?genre=Comedy">Komedia</a></li>
                        <li><a href="index.php?genre=Action">Akcja</a></li>
                        <li><a href="index.php?genre=Fantasy">Fantasy</a></li>
                        <li><a href="index.php?genre=Sci-Fi">Sci-Fi</a></li>
                        <li><a href="index.php?genre=Horror">Horror</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Informacje</h4>
                    <ul>
                        <li><a href="#about">O nas</a></li>
                        <li><a href="#contact">Kontakt</a></li>
                        <li><a href="#privacy">Polityka prywatności</a></li>
                        <li><a href="#terms">Regulamin</a></li>
                        <li><a href="#help">Pomoc</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-copyright">
                    <p>&copy; 2025 TV Time Clone. Wszystkie prawa zastrzeżone.</p>
                    <p>Projekt wykonany w ramach kursu "Podstawy Technologii WWW"</p>
                </div>
                
                <div class="footer-stats">
                    <?php
                    try {
                        // Pobierz statystyki tylko jeśli funkcje są dostępne
                        if (function_exists('fetchCount')) {
                            $total_shows = fetchCount("SELECT COUNT(*) FROM shows");
                            $total_users = fetchCount("SELECT COUNT(*) FROM users");
                            $total_reviews = fetchCount("SELECT COUNT(*) FROM reviews");
                        } else {
                            $total_shows = 0;
                            $total_users = 0;
                            $total_reviews = 0;
                        }
                    ?>
                        <div class="stats">
                            <span class="stat-item">
                                <i class="fas fa-tv"></i>
                                <?php echo number_format($total_shows); ?> seriali
                            </span>
                            <span class="stat-item">
                                <i class="fas fa-users"></i>
                                <?php echo number_format($total_users); ?> użytkowników
                            </span>
                            <span class="stat-item">
                                <i class="fas fa-star"></i>
                                <?php echo number_format($total_reviews); ?> recenzji
                            </span>
                        </div>
                    <?php } catch (Exception $e) { ?>
                        <div class="stats">
                            <span class="stat-item">
                                <i class="fas fa-tv"></i>
                                Tysiące seriali
                            </span>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="shows.php"><span class="nav-icon"><i class="fas fa-tv"></i></span><span>Seriale</span></a>
        <a href="movies.php"><span class="nav-icon"><i class="fas fa-film"></i></span><span>Filmy</span></a>
        <a href="explore.php"><span class="nav-icon"><i class="fas fa-search"></i></span><span>Eksploruj</span></a>
        <a href="profile.php"><span class="nav-icon"><i class="fas fa-user"></i></span><span>Profil</span></a>
    </nav>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Ładowanie...</p>
        </div>
    </div>
    
    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop" onclick="scrollToTop()">
        <i class="fas fa-chevron-up"></i>
    </button>
    
    <!-- Main JavaScript -->
    <script src="assets/js/script.js"></script>
    
    <!-- Google Analytics (opcjonalnie) -->
    <script>
        // Tutaj można dodać kod Google Analytics
    </script>
    
    <script>
        // Funkcje dla nawigacji mobilnej
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            mobileMenu.classList.toggle('active');
        }
        
        function toggleUserMenu() {
            const userMenu = document.getElementById('userMenu');
            userMenu.classList.toggle('active');
        }
        
        // Zamknij menu po kliknięciu poza nim
        document.addEventListener('click', function(event) {
            const userDropdown = document.querySelector('.user-dropdown');
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const mobileMenu = document.getElementById('mobileMenu');
            
            // Zamknij menu użytkownika
            if (userDropdown && !userDropdown.contains(event.target)) {
                document.getElementById('userMenu').classList.remove('active');
            }
            
            // Zamknij menu mobilne
            if (mobileMenuToggle && !mobileMenuToggle.contains(event.target) && !mobileMenu.contains(event.target)) {
                mobileMenu.classList.remove('active');
            }
        });
        
        // Pokaż/ukryj przycisk "Powrót do góry"
        window.addEventListener('scroll', function() {
            const backToTop = document.getElementById('backToTop');
            if (window.pageYOffset > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
        
        // Funkcja przewijania do góry
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Auto-hide flash messages
        setTimeout(function() {
            const flashMessages = document.querySelectorAll('.flash-message');
            flashMessages.forEach(function(message) {
                message.style.animation = 'slideOut 0.5s ease-in-out forwards';
                setTimeout(function() {
                    message.remove();
                }, 500);
            });
        }, 5000);
    </script>
    
    <style>
        .bottom-nav {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            height: 60px;
            background: #222;
            display: flex;
            justify-content: space-around;
            align-items: center;
            border-top: 1px solid #333;
            z-index: 1000;
        }
        .bottom-nav a {
            color: #ccc;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.9rem;
            flex: 1;
            padding: 0.5rem 0;
            transition: color 0.2s;
        }
        .bottom-nav a:hover, .bottom-nav a.active {
            color: #ff9500;
        }
        .nav-icon {
            font-size: 1.5rem;
            margin-bottom: 0.2rem;
        }
        @media (max-width: 768px) {
            .bottom-nav { font-size: 0.8rem; }
            .main-content { padding-bottom: 70px; }
        }
    </style>
</body>
</html>
