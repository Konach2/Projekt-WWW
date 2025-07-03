<?php
/**
 * Profil użytkownika z aktywnością i statystykami
 */

require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'includes/notifications.php';

// Wymagaj zalogowania
requireLogin();

$user_id = getCurrentUserId();
$username = getCurrentUsername();

$user_id = getCurrentUserId();
$username = getCurrentUsername();
$email = getCurrentUserEmail();

// Pobierz statystyki użytkownika
try {
    $stats = [
        'total_shows' => fetchCount("SELECT COUNT(*) FROM user_shows WHERE user_id = ?", [$user_id]),
        'watching' => fetchCount("SELECT COUNT(*) FROM user_shows WHERE user_id = ? AND status = 'watching'", [$user_id]),
        'completed' => fetchCount("SELECT COUNT(*) FROM user_shows WHERE user_id = ? AND status = 'completed'", [$user_id]),
        'plan_to_watch' => fetchCount("SELECT COUNT(*) FROM user_shows WHERE user_id = ? AND status = 'plan_to_watch'", [$user_id]),
        'dropped' => fetchCount("SELECT COUNT(*) FROM user_shows WHERE user_id = ? AND status = 'dropped'", [$user_id]),
        'total_ratings' => fetchCount("SELECT COUNT(*) FROM user_shows WHERE user_id = ? AND rating > 0", [$user_id]),
        'avg_rating' => fetchOne("SELECT AVG(rating) as avg FROM user_shows WHERE user_id = ? AND rating > 0", [$user_id])['avg'] ?? 0
    ];
    
    // Ostatnio dodane seriale
    $recent_shows = fetchAll("
        SELECT s.id, s.title, s.year, s.poster_url, us.status, us.created_at
        FROM user_shows us
        JOIN shows s ON us.show_id = s.id
        WHERE us.user_id = ?
        ORDER BY us.created_at DESC
        LIMIT 6
    ", [$user_id]);
    
    // Najwyżej ocenione seriale użytkownika
    $top_rated = fetchAll("
        SELECT s.id, s.title, s.year, s.poster_url, us.rating
        FROM user_shows us
        JOIN shows s ON us.show_id = s.id
        WHERE us.user_id = ? AND us.rating >= 8
        ORDER BY us.rating DESC, s.title ASC
        LIMIT 6
    ", [$user_id]);
    
    // Ulubione gatunki
    $favorite_genres = fetchAll("
        SELECT s.genre, COUNT(*) as count, AVG(us.rating) as avg_rating
        FROM user_shows us
        JOIN shows s ON us.show_id = s.id
        WHERE us.user_id = ?
        GROUP BY s.genre
        ORDER BY count DESC, avg_rating DESC
        LIMIT 5
    ", [$user_id]);
    
    // Ostatnia aktywność
    $recent_activity = getUserActivity($user_id, 10);
    
} catch (Exception $e) {
    $stats = ['total_shows' => 0, 'watching' => 0, 'completed' => 0, 'plan_to_watch' => 0, 'dropped' => 0, 'total_ratings' => 0, 'avg_rating' => 0];
    $recent_shows = [];
    $top_rated = [];
    $favorite_genres = [];
    $recent_activity = [];
}

$page_title = "Profil: $username - TV Time Clone";
$page_description = "Profil użytkownika $username w TV Time Clone";

include 'includes/header.php';

// Dodaj funkcję timeAgo jeśli nie istnieje
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'przed chwilą';
        if ($time < 3600) return floor($time/60) . ' min temu';
        if ($time < 86400) return floor($time/3600) . ' godz. temu';
        if ($time < 2592000) return floor($time/86400) . ' dni temu';
        if ($time < 31536000) return floor($time/2592000) . ' mies. temu';
        
        return floor($time/31536000) . ' lat temu';
    }
}
?>

<div class="profile-page">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <i class="fas fa-user"></i>
        </div>
        
        <div class="profile-info">
            <h1><?php echo htmlspecialchars($username); ?></h1>
            <div class="profile-stats-summary">
                <span><strong><?php echo number_format($stats['total_shows']); ?></strong> seriali</span>
                <span><strong><?php echo number_format($stats['completed']); ?></strong> ukończonych</span>
                <?php if ($stats['avg_rating'] > 0): ?>
                    <span><strong><?php echo number_format($stats['avg_rating'], 1); ?></strong> średnia ocena</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="profile-actions">
            <a href="notifications.php" class="btn btn-outline">
                <i class="fas fa-bell"></i>
                Powiadomienia
            </a>
            <a href="movies.php" class="btn btn-primary">
                <i class="fas fa-film"></i>
                Moje filmy
            </a>
            <a href="shows.php" class="btn btn-primary">
                <i class="fas fa-tv"></i>
                Moje seriale
            </a>
        </div>
    </div>
    
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card watching">
            <div class="stat-icon">
                <i class="fas fa-play"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['watching']); ?></h3>
                <p>Oglądane</p>
            </div>
        </div>
        
        <div class="stat-card completed">
            <div class="stat-icon">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['completed']); ?></h3>
                <p>Ukończone</p>
            </div>
        </div>
        
        <div class="stat-card planned">
            <div class="stat-icon">
                <i class="fas fa-bookmark"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['plan_to_watch']); ?></h3>
                <p>Chcę obejrzeć</p>
            </div>
        </div>
        
        <div class="stat-card ratings">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['total_ratings']); ?></h3>
                <p>Ocen wystawionych</p>
            </div>
        </div>
    </div>
    
    <!-- Content Grid -->
    <div class="profile-content">
        <!-- Moje Filmy -->
        <div class="profile-section">
            <h2><i class="fas fa-film"></i> Moje filmy</h2>
            <?php
            // Pobierz ostatnio dodane filmy użytkownika
            $recent_movies = fetchAll("
                SELECT m.id, m.title, m.year, m.poster_url, um.status, um.added_at
                FROM user_movies um
                JOIN movies m ON um.movie_id = m.id
                WHERE um.user_id = ?
                ORDER BY um.added_at DESC
                LIMIT 6
            ", [$user_id]);
            ?>
            <?php if (empty($recent_movies)): ?>
                <div class="empty-state">
                    <p>Nie dodałeś jeszcze żadnych filmów.</p>
                    <a href="explore.php" class="btn btn-primary">Przeglądaj filmy</a>
                </div>
            <?php else: ?>
                <div class="shows-grid">
                    <?php foreach ($recent_movies as $movie): ?>
                        <div class="show-card-mini">
                            <a href="movie-details.php?id=<?php echo $movie['id']; ?>">
                                <img src="<?php echo $movie['poster_url'] ? htmlspecialchars($movie['poster_url']) : 'assets/images/placeholder-show.jpg'; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                            </a>
                            <div class="show-info">
                                <h4><?php echo htmlspecialchars($movie['title']); ?></h4>
                                <span class="show-year"><?php echo $movie['year']; ?></span>
                                <span class="show-status status-<?php echo $movie['status']; ?>">
                                    <?php
                                    $statuses = [
                                        'watched' => 'Obejrzane',
                                        'want_to_watch' => 'Do obejrzenia',
                                        'watchlist' => 'Do obejrzenia',
                                    ];
                                    echo $statuses[$movie['status']] ?? $movie['status'];
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Shows -->
        <div class="profile-section">
            <h2>
                <i class="fas fa-clock"></i>
                Ostatnio dodane
            </h2>
            
            <?php if (empty($recent_shows)): ?>
                <div class="empty-state">
                    <p>Nie dodałeś jeszcze żadnych seriali.</p>
                    <a href="index.php" class="btn btn-primary">Przeglądaj seriale</a>
                </div>
            <?php else: ?>
                <div class="shows-grid">
                    <?php foreach ($recent_shows as $show): ?>
                        <div class="show-card-mini">
                            <a href="show-details.php?id=<?php echo $show['id']; ?>">
                                <img src="<?php echo getImageUrl($show['poster_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($show['title']); ?>">
                            </a>
                            <div class="show-info">
                                <h4><?php echo htmlspecialchars($show['title']); ?></h4>
                                <span class="show-year"><?php echo $show['year']; ?></span>
                                <span class="show-status status-<?php echo $show['status']; ?>">
                                    <?php
                                    $statuses = [
                                        'watching' => 'Oglądane',
                                        'completed' => 'Ukończone',
                                        'plan_to_watch' => 'Chcę obejrzeć',
                                        'dropped' => 'Porzucone'
                                    ];
                                    echo $statuses[$show['status']] ?? $show['status'];
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Top Rated Shows -->
        <?php if (!empty($top_rated)): ?>
            <div class="profile-section">
                <h2>
                    <i class="fas fa-trophy"></i>
                    Najwyżej ocenione
                </h2>
                
                <div class="shows-grid">
                    <?php foreach ($top_rated as $show): ?>
                        <div class="show-card-mini">
                            <a href="show-details.php?id=<?php echo $show['id']; ?>">
                                <img src="<?php echo getImageUrl($show['poster_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($show['title']); ?>">
                            </a>
                            <div class="show-info">
                                <h4><?php echo htmlspecialchars($show['title']); ?></h4>
                                <span class="show-year"><?php echo $show['year']; ?></span>
                                <div class="show-rating">
                                    <i class="fas fa-star"></i>
                                    <?php echo number_format($show['rating'], 1); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Favorite Genres -->
        <?php if (!empty($favorite_genres)): ?>
            <div class="profile-section">
                <h2>
                    <i class="fas fa-heart"></i>
                    Ulubione gatunki
                </h2>
                
                <div class="genres-list">
                    <?php foreach ($favorite_genres as $genre): ?>
                        <div class="genre-item">
                            <div class="genre-info">
                                <h4><?php echo htmlspecialchars($genre['genre']); ?></h4>
                                <span class="genre-count"><?php echo $genre['count']; ?> seriali</span>
                            </div>
                            <?php if ($genre['avg_rating'] > 0): ?>
                                <div class="genre-rating">
                                    <i class="fas fa-star"></i>
                                    <?php echo number_format($genre['avg_rating'], 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.profile-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 3rem;
    padding: 2rem;
    background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
    border-radius: 12px;
    border: 1px solid #333;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff9500, #ff7b00);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #fff;
    flex-shrink: 0;
}

.profile-info {
    flex: 1;
}

.profile-info h1 {
    margin: 0 0 0.5rem 0;
    color: #fff;
    font-size: 2rem;
}

.profile-stats-summary {
    display: flex;
    gap: 2rem;
    color: #aaa;
}

.profile-actions {
    display: flex;
    gap: 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: #2a2a2a;
    border-radius: 8px;
    padding: 1.5rem;
    border-left: 4px solid;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-card.watching { border-left-color: #00c851; }
.stat-card.completed { border-left-color: #007bff; }
.stat-card.planned { border-left-color: #ff9500; }
.stat-card.ratings { border-left-color: #ffc107; }

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #fff;
}

.stat-card.watching .stat-icon { background: #00c851; }
.stat-card.completed .stat-icon { background: #007bff; }
.stat-card.planned .stat-icon { background: #ff9500; }
.stat-card.ratings .stat-icon { background: #ffc107; }

.stat-info h3 {
    margin: 0;
    font-size: 2rem;
    color: #fff;
}

.stat-info p {
    margin: 0;
    color: #aaa;
    font-size: 0.9rem;
}

.profile-content {
    display: grid;
    gap: 3rem;
}

.profile-section {
    background: #2a2a2a;
    border-radius: 8px;
    padding: 2rem;
    border: 1px solid #333;
}

.profile-section h2 {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin: 0 0 1.5rem 0;
    color: #fff;
    font-size: 1.5rem;
}

.shows-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1.5rem;
}

.show-card-mini {
    text-align: center;
}

.show-card-mini img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 0.8rem;
    transition: transform 0.2s ease;
}

.show-card-mini:hover img {
    transform: scale(1.05);
}

.show-card-mini h4 {
    margin: 0 0 0.3rem 0;
    color: #fff;
    font-size: 0.9rem;
    line-height: 1.2;
}

.show-year {
    color: #aaa;
    font-size: 0.8rem;
}

.show-status {
    display: block;
    margin-top: 0.3rem;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    text-transform: uppercase;
    font-weight: 600;
}

.show-status.status-watching { background: #00c851; color: #fff; }
.show-status.status-completed { background: #007bff; color: #fff; }
.show-status.status-plan_to_watch { background: #ff9500; color: #fff; }
.show-status.status-dropped { background: #6c757d; color: #fff; }

.show-rating {
    color: #ff9500;
    font-weight: 600;
    margin-top: 0.3rem;
}

.genres-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.genre-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: #333;
    border-radius: 8px;
}

.genre-info h4 {
    margin: 0;
    color: #fff;
}

.genre-count {
    color: #aaa;
    font-size: 0.9rem;
}

.genre-rating {
    color: #ff9500;
    font-weight: 600;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #aaa;
}

.empty-state p {
    margin-bottom: 1rem;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .profile-stats-summary {
        justify-content: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .shows-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 1rem;
    }
    
    .profile-section {
        padding: 1.5rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
