<?php
/**
 * Wspólny nagłówek dla TV Time Clone
 * Autor: System
 * Data: 2025-06-29
 */

// Jeśli nie ustawiono tytułu strony, ustaw domyślny
if (!isset($page_title)) {
    $page_title = 'TV Time Clone';
}

// Jeśli nie ustawiono opisu strony, ustaw domyślny
if (!isset($page_description)) {
    $page_description = 'Śledź swoje ulubione seriale i dziel się opiniami z innymi fanami';
}

// Jeśli nie ustawiono słów kluczowych, ustaw domyślne
if (!isset($page_keywords)) {
    $page_keywords = 'seriale, tv, śledzenie, recenzje, oceny, episodes, shows';
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords); ?>">
    <meta name="author" content="TV Time Clone">
    
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Security -->
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <!-- Logo -->
            <div class="nav-brand">
                <a href="index.php">
                    <i class="fas fa-tv"></i>
                    <span class="brand-full">TV Time Clone</span>
                    <span class="brand-short">TV Time</span>
                </a>
            </div>
            
            <!-- Navigation Links -->
            <div class="nav-menu">
                <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Strona główna</span>
                </a>
                
                <!-- Live Search -->
                <div class="nav-search">
                    <div class="search-box">
                        <input type="text" id="navSearchInput" placeholder="Szukaj seriali..." class="search-input">
                        <i class="fas fa-search search-icon"></i>
                        <div class="search-results" id="navSearchResults"></div>
                    </div>
                </div>
                
                <?php if (isLoggedIn()): ?>
                    <a href="shows.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'shows.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tv"></i>
                        <span>Seriale</span>
                    </a>
                    
                    <a href="movies.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'movies.php' ? 'active' : ''; ?>">
                        <i class="fas fa-film"></i>
                        <span>Filmy</span>
                    </a>
                    
                    <?php if (isAdmin()): ?>
                        <a href="admin.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            <span>Panel Admina</span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- User Menu -->
            <div class="nav-user">
                <?php if (isLoggedIn()): ?>
                    <!-- Notifications -->
                    <div class="notifications-widget">
                        <?php
                        if (function_exists('fetchCount')) {
                            require_once 'includes/notifications.php';
                            $unread_count = countUnreadNotifications(getCurrentUserId());
                        } else {
                            $unread_count = 0;
                        }
                        ?>
                        <a href="notifications.php" class="notifications-btn" title="Powiadomienia">
                            <i class="fas fa-bell"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="notifications-badge"><?php echo min($unread_count, 99); ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <div class="user-dropdown">
                        <button class="user-btn" onclick="toggleUserMenu()">
                            <i class="fas fa-user"></i>
                            <span><?php echo htmlspecialchars(getCurrentUsername()); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        
                        <div class="user-menu" id="userMenu">
                            <a href="shows.php">
                                <i class="fas fa-list"></i>
                                Moje seriale
                            </a>
                            <a href="movies.php">
                                <i class="fas fa-film"></i>
                                Moje filmy
                            </a>
                            <div class="menu-divider"></div>
                            <a href="logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                Wyloguj
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i>
                        Zaloguj
                    </a>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Zarejestruj
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobileMenu">
            <a href="index.php">
                <i class="fas fa-home"></i>
                Strona główna
            </a>
            <?php if (isLoggedIn()): ?>
                <a href="shows.php">
                    <i class="fas fa-list"></i>
                    Moje seriale
                </a>
                <a href="movies.php">
                    <i class="fas fa-film"></i>
                    Moje filmy
                </a>
                <div class="menu-divider"></div>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Wyloguj (<?php echo htmlspecialchars(getCurrentUsername()); ?>)
                </a>
            <?php else: ?>
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i>
                    Zaloguj
                </a>
                <a href="register.php">
                    <i class="fas fa-user-plus"></i>
                    Zarejestruj
                </a>
            <?php endif; ?>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <div class="flash-messages" id="flashMessages">
        <?php if (hasFlashMessages()): ?>
            <?php foreach (getFlashMessages() as $message): ?>
                <div class="flash-message flash-<?php echo $message['type']; ?>">
                    <i class="fas fa-<?php 
                        echo $message['type'] == 'success' ? 'check-circle' : 
                             ($message['type'] == 'error' ? 'exclamation-circle' : 
                              ($message['type'] == 'warning' ? 'exclamation-triangle' : 'info-circle')); 
                    ?>"></i>
                    <span><?php echo htmlspecialchars($message['message']); ?></span>
                    <button class="flash-close" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- CSRF Token for AJAX requests -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
