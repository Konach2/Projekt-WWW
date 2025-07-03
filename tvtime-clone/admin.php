<?php
/**
 * Panel administracyjny TV Time Clone
 * Autor: System
 * Data: 2025-07-02
 */

require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Sprawdź uprawnienia admina
if (!isAdmin()) {
    setFlashMessage('error', 'Nie masz uprawnień do tej strony.');
    header('Location: index.php');
    exit();
}

// Ustawienia strony
$page_title = 'Panel Administracyjny - TV Time Clone';
$page_description = 'Zarządzaj serialami w systemie TV Time Clone';

// Obsługa akcji
$action = $_GET['action'] ?? 'dashboard';
$show_id = $_GET['id'] ?? null;
$movie_id = $_GET['id'] ?? null;

// Statystyki dla dashboard
try {
    $stats = [
        'shows' => fetchCount("SELECT COUNT(*) FROM shows"),
        'movies' => fetchCount("SELECT COUNT(*) FROM movies"),
        'users' => fetchCount("SELECT COUNT(*) FROM users"),
        'reviews' => fetchCount("SELECT COUNT(*) FROM reviews"),
        'user_shows' => fetchCount("SELECT COUNT(*) FROM user_shows"),
        'user_movies' => fetchCount("SELECT COUNT(*) FROM user_movies")
    ];
} catch (Exception $e) {
    $stats = ['shows' => 0, 'movies' => 0, 'users' => 0, 'reviews' => 0, 'user_shows' => 0, 'user_movies' => 0];
}

include 'includes/header.php';
?>

<div class="admin-container">
    <div class="admin-sidebar">
        <h3><i class="fas fa-cog"></i> Panel Admina</h3>
        <nav class="admin-nav">
            <a href="admin.php?action=dashboard" class="<?php echo $action === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            
            <!-- Sekcja Seriali -->
            <div class="nav-section">
                <div class="nav-section-title">Seriale</div>
                <a href="admin.php?action=list" class="<?php echo $action === 'list' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> Lista seriali
                </a>
                <a href="admin.php?action=add" class="<?php echo $action === 'add' ? 'active' : ''; ?>">
                    <i class="fas fa-plus"></i> Dodaj serial
                </a>
            </div>
            
            <!-- Sekcja Filmów -->
            <div class="nav-section">
                <div class="nav-section-title">Filmy</div>
                <a href="admin.php?action=movies-list" class="<?php echo $action === 'movies-list' ? 'active' : ''; ?>">
                    <i class="fas fa-film"></i> Lista filmów
                </a>
                <a href="admin.php?action=movies-add" class="<?php echo $action === 'movies-add' ? 'active' : ''; ?>">
                    <i class="fas fa-plus"></i> Dodaj film
                </a>
            </div>
            
            <!-- Pozostałe -->
            <a href="admin.php?action=users" class="<?php echo $action === 'users' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Użytkownicy
            </a>
            <a href="index.php">
                <i class="fas fa-home"></i> Wróć do strony
            </a>
        </nav>
    </div>

    <div class="admin-content">
        <?php if ($action === 'dashboard' || $action === ''): ?>
            <!-- Dashboard -->
            <div class="admin-header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <p>Przegląd systemu TV Time Clone</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tv"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['shows']); ?></h3>
                        <p>Seriale</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-film"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['movies']); ?></h3>
                        <p>Filmy</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['users']); ?></h3>
                        <p>Użytkownicy</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['reviews']); ?></h3>
                        <p>Recenzje</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bookmark"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['user_shows']); ?></h3>
                        <p>Zapisane seriale</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['user_movies']); ?></h3>
                        <p>Zapisane filmy</p>
                    </div>
                </div>
            </div>

            <div class="recent-activity">
                <h2>Ostatnie seriale</h2>
                <?php
                try {
                    $recent_shows = fetchAll("
                        SELECT id, title, genre, year, created_at 
                        FROM shows 
                        ORDER BY created_at DESC 
                        LIMIT 5
                    ");
                    
                    if (!empty($recent_shows)): ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Tytuł</th>
                                        <th>Gatunek</th>
                                        <th>Rok</th>
                                        <th>Dodano</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_shows as $show): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($show['title']); ?></td>
                                            <td><?php echo htmlspecialchars($show['genre']); ?></td>
                                            <td><?php echo $show['year']; ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($show['created_at'])); ?></td>
                                            <td>
                                                <a href="admin.php?action=edit&id=<?php echo $show['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="show-details.php?id=<?php echo $show['id']; ?>" class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">Brak seriali w systemie.</p>
                    <?php endif;
                } catch (Exception $e) {
                    echo '<p class="error">Błąd podczas ładowania danych.</p>';
                }
                ?>
            </div>

            <div class="recent-activity">
                <h2>Ostatnie filmy</h2>
                <?php
                try {
                    $recent_movies = fetchAll("
                        SELECT id, title, genre, year, created_at 
                        FROM movies 
                        ORDER BY created_at DESC 
                        LIMIT 5
                    ");
                    
                    if (!empty($recent_movies)): ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Tytuł</th>
                                        <th>Gatunek</th>
                                        <th>Rok</th>
                                        <th>Dodano</th>
                                        <th>Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_movies as $movie): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($movie['title']); ?></td>
                                            <td><?php echo htmlspecialchars($movie['genre']); ?></td>
                                            <td><?php echo $movie['year']; ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($movie['created_at'])); ?></td>
                                            <td>
                                                <a href="admin.php?action=movies-edit&id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="movie-details.php?id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">Brak filmów w systemie.</p>
                    <?php endif;
                } catch (Exception $e) {
                    echo '<p class="error">Błąd podczas ładowania danych.</p>';
                }
                ?>
            </div>

        <?php elseif ($action === 'list'): ?>
            <?php include 'admin/shows-list.php'; ?>

        <?php elseif ($action === 'add'): ?>
            <?php include 'admin/shows-add.php'; ?>

        <?php elseif ($action === 'edit' && $show_id): ?>
            <?php include 'admin/shows-edit.php'; ?>

        <?php elseif ($action === 'movies-list'): ?>
            <?php include 'admin/movies-list.php'; ?>

        <?php elseif ($action === 'movies-add'): ?>
            <?php include 'admin/movies-add.php'; ?>

        <?php elseif ($action === 'movies-edit' && $movie_id): ?>
            <?php include 'admin/movies-edit.php'; ?>

        <?php elseif ($action === 'users'): ?>
            <?php include 'admin/users-list.php'; ?>

        <?php else: ?>
            <div class="admin-header">
                <h1><i class="fas fa-exclamation-triangle"></i> Strona nie znaleziona</h1>
                <p>Wybierz opcję z menu po lewej stronie.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Style dla panelu admina */
.admin-container {
    display: flex;
    min-height: calc(100vh - 64px);
    gap: 0;
    position: relative;
}

.admin-sidebar {
    width: 250px;
    min-width: 250px;
    max-width: 250px;
    flex-shrink: 0;
    background: #2a2a2a;
    padding: 1rem 0;
    border-right: 1px solid #3a3a3a;
    overflow-y: auto;
    position: fixed;
    top: 64px;
    left: 0;
    height: calc(100vh - 64px);
    box-sizing: border-box;
    z-index: 100;
}

.admin-sidebar h3 {
    color: #ff9500;
    padding: 0 1.5rem;
    margin-bottom: 1.5rem;
    font-size: 1.2rem;
}

.admin-nav a {
    display: block;
    padding: 0.75rem 1.5rem;
    color: #ccc;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: all 0.3s ease;
}

.admin-nav a:hover,
.admin-nav a.active {
    background: #3a3a3a;
    border-left-color: #ff9500;
    color: #fff;
}

.admin-nav i {
    margin-right: 0.5rem;
    width: 1.2rem;
}

.nav-section {
    margin-bottom: 0.75rem;
}

.nav-section-title {
    color: #888;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 0.5rem 1.5rem;
    margin-bottom: 0.25rem;
    border-bottom: 1px solid #3a3a3a;
}

.admin-content {
    flex: 1;
    min-width: 0;
    margin-left: 250px;
    padding: 2rem;
    background: #1a1a1a;
    overflow-x: auto;
}

.admin-header {
    margin-bottom: 2rem;
}

.admin-header h1 {
    color: #ff9500;
    margin-bottom: 0.5rem;
}

.admin-header p {
    color: #ccc;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: #2a2a2a;
    border-radius: 8px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid #3a3a3a;
}

.stat-icon {
    font-size: 2rem;
    color: #ff9500;
}

.stat-info h3 {
    margin: 0;
    font-size: 2rem;
    color: #fff;
}

.stat-info p {
    margin: 0;
    color: #ccc;
}

.recent-activity {
    background: #2a2a2a;
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid #3a3a3a;
}

.recent-activity h2 {
    color: #ff9500;
    margin-bottom: 1rem;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #3a3a3a;
}

.admin-table th {
    background: #1a1a1a;
    color: #ff9500;
    font-weight: 600;
}

.admin-table td {
    color: #ccc;
}

.admin-table tr:hover td {
    background: #3a3a3a;
}

.no-data {
    text-align: center;
    color: #888;
    padding: 2rem;
}

.error {
    color: #e74c3c;
    text-align: center;
    padding: 1rem;
}

@media (max-width: 768px) {
    .admin-container {
        flex-direction: column;
    }
    
    .admin-sidebar {
        position: relative;
        width: 100%;
        min-width: 100%;
        max-width: 100%;
        height: auto;
        top: 0;
        left: 0;
    }
    
    .admin-content {
        margin-left: 0;
        overflow-x: visible;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
