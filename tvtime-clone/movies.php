<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    echo '<div class="main-content"><div class="hero-section">
        <h1>Moje Filmy</h1>
        <p>Aby zobaczyć swoje filmy, <a href="login.php">zaloguj się</a>.</p>
    </div></div>';
    require_once 'includes/footer.php';
    exit();
}

$user_id = $_SESSION['user_id'];

// DEBUG: Dodaj logowanie dla debugowania
error_log("movies.php: User ID = " . $user_id);

// DEBUG: Sprawdź ile filmów ma użytkownik (tylko dla debugowania)
$debug_count = fetchCount("SELECT COUNT(*) FROM user_movies WHERE user_id = ?", [$user_id]);
error_log("movies.php: User has $debug_count movies in database");

// Pobierz filmy użytkownika z różnymi statusami
try {
    // Uproszczone zapytania dla debugowania
    $watched_movies = fetchAll("
        SELECT m.id, m.title, m.description, m.genre, m.year, m.poster_url, 
               um.status as user_status, um.rating as user_rating, um.watched_at, um.favorite
        FROM movies m
        INNER JOIN user_movies um ON m.id = um.movie_id
        WHERE um.user_id = ? AND um.status = 'watched'
        ORDER BY um.watched_at DESC
    ", [$user_id]);

    $watchlist_movies = fetchAll("
        SELECT m.id, m.title, m.description, m.genre, m.year, m.poster_url,
               um.status as user_status, um.rating as user_rating, um.added_at, um.favorite
        FROM movies m
        INNER JOIN user_movies um ON m.id = um.movie_id
        WHERE um.user_id = ? AND um.status IN ('want_to_watch', 'watchlist')
        ORDER BY um.added_at DESC
    ", [$user_id]);

    $favorite_movies = fetchAll("
        SELECT m.id, m.title, m.description, m.genre, m.year, m.poster_url,
               um.status as user_status, um.rating as user_rating, um.watched_at, um.favorite
        FROM movies m
        INNER JOIN user_movies um ON m.id = um.movie_id
        WHERE um.user_id = ? AND um.favorite = 1
        ORDER BY um.rating DESC, um.watched_at DESC
    ", [$user_id]);

    // DEBUG: Loguj ilość znalezionych filmów
    error_log("movies.php: Found " . count($watched_movies) . " watched movies");
    error_log("movies.php: Found " . count($watchlist_movies) . " watchlist movies"); 
    error_log("movies.php: Found " . count($favorite_movies) . " favorite movies");

    // Statystyki użytkownika (bez runtime - kolumna może nie istnieć)
    $stats = fetchOne("
        SELECT 
            COUNT(*) as total_movies,
            COUNT(CASE WHEN um.status = 'watched' THEN 1 END) as watched_count,
            COUNT(CASE WHEN um.status IN ('want_to_watch', 'watchlist') THEN 1 END) as watchlist_count,
            AVG(CASE WHEN um.rating > 0 THEN um.rating END) as avg_rating
        FROM user_movies um
        LEFT JOIN movies m ON um.movie_id = m.id
        WHERE um.user_id = ?
    ", [$user_id]);
    
    // Dodaj total_runtime jako 0 (można dodać później gdy kolumna runtime zostanie dodana)
    $stats['total_runtime'] = 0;

    // Aktywność użytkownika - tymczasowo wyłączona (tabela user_activity nie istnieje)
    $recent_activity = [];
    /*
    $recent_activity = fetchAll("
        SELECT ua.*, m.title as movie_title, m.poster_url
        FROM user_activity ua
        LEFT JOIN movies m ON ua.movie_id = m.id
        WHERE ua.user_id = ? AND ua.activity_type LIKE '%movie%'
        ORDER BY ua.created_at DESC
        LIMIT 10
    ", [$user_id]);
    */

} catch (Exception $e) {
    error_log("Movies.php error: " . $e->getMessage());
    error_log("Movies.php stack trace: " . $e->getTraceAsString());
    
    // DEBUG: Wyświetl błąd na stronie (tymczasowo)
    echo "<div style='background: red; color: white; padding: 10px; margin: 10px;'>";
    echo "<h3>DEBUG - Błąd w movies.php:</h3>";
    echo "<p>Błąd: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Plik: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Linia: " . $e->getLine() . "</p>";
    echo "</div>";
    
    $watched_movies = [];
    $watchlist_movies = [];
    $favorite_movies = [];
    $stats = ['total_movies' => 0, 'watched_count' => 0, 'watchlist_count' => 0, 'avg_rating' => 0, 'total_runtime' => 0];
    $recent_activity = [];
}

?>

<div class="main-content">
    <!-- Hero Section -->
    <div class="movies-hero">
        <div class="hero-content">
            <h1>
                <i class="fas fa-film"></i>
                Moje Filmy
            </h1>
            <p>Twoja kolekcja filmów i lista życzeń</p>
        </div>
        
        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $stats['watched_count'] ?? 0; ?></div>
                    <div class="stat-label">Obejrzane</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-bookmark"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $stats['watchlist_count'] ?? 0; ?></div>
                    <div class="stat-label">Do obejrzenia</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : '0'; ?></div>
                    <div class="stat-label">Średnia ocena</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $stats['total_runtime'] ? round($stats['total_runtime'] / 60) : '0'; ?>h</div>
                    <div class="stat-label">Czas oglądania</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="movies-nav">
        <button class="tab-btn active" onclick="showTab('watched', event)">
            <i class="fas fa-eye"></i>
            Obejrzane
        </button>
        <button class="tab-btn" onclick="showTab('watchlist', event)">
            <i class="fas fa-bookmark"></i>
            Do obejrzenia
        </button>
        <button class="tab-btn" onclick="showTab('activity', event)">
            <i class="fas fa-history"></i>
            Aktywność
        </button>
    </div>

    <!-- Watched Movies Tab -->
    <div class="tab-content active" id="watched-tab">
        <div class="section-header">
            <h2>Obejrzane filmy</h2>
            <div class="view-options">
                <button class="view-btn active" onclick="toggleView('grid', event)" title="Widok siatki">
                    <i class="fas fa-th"></i>
                </button>
                <button class="view-btn" onclick="toggleView('list', event)" title="Widok listy">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
        
        <?php if (empty($watched_movies)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-film"></i>
                </div>
                <h3>Brak obejrzanych filmów (DEBUG: <?php echo count($watched_movies); ?>)</h3>
                <p>Zacznij dodawać filmy, które obejrzałeś!</p>
                <a href="explore.php" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Przeglądaj filmy
                </a>
            </div>
        <?php else: ?>
            <div class="movies-grid" id="movies-container-watched">
                <?php foreach ($watched_movies as $movie): ?>
                    <div class="movie-card" data-movie-id="<?php echo $movie['id']; ?>">
                        <div class="movie-poster">
                            <img src="<?php echo $movie['poster_url'] ? htmlspecialchars($movie['poster_url']) : 'assets/images/placeholder-show.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                 loading="lazy">
                            <div class="movie-overlay">
                                <div class="overlay-content">
                                    <button class="quick-btn" title="Oceń" data-action="rate"><i class="fas fa-star"></i></button>
                                    <button class="quick-btn" title="Ulubione" data-action="favorite"><i class="fas fa-heart<?php echo $movie['favorite'] ? ' active' : ''; ?>"></i></button>
                                    <button class="quick-btn" title="Usuń z listy" data-action="remove"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <?php if (isset($movie['user_rating']) && $movie['user_rating']): ?>
                                <div class="movie-rating">
                                    <i class="fas fa-star"></i>
                                    <?php echo number_format($movie['user_rating'], 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="movie-info">
                            <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <p class="movie-meta">
                                <?php echo htmlspecialchars($movie['genre']); ?> • <?php echo $movie['year']; ?>
                            </p>
                            <?php if (!empty($movie['watched_at'])): ?>
                                <p class="watched-date">
                                    <i class="fas fa-eye"></i>
                                    Obejrzano: <?php echo date('d.m.Y', strtotime($movie['watched_at'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Watchlist Tab -->
    <div class="tab-content" id="watchlist-tab">
        <div class="section-header">
            <h2>Do obejrzenia</h2>
            <div class="view-options">
                <button class="view-btn active" onclick="toggleView('grid', event, 'watchlist')" title="Widok siatki">
                    <i class="fas fa-th"></i>
                </button>
                <button class="view-btn" onclick="toggleView('list', event, 'watchlist')" title="Widok listy">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
        
        <?php if (empty($watchlist_movies)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-bookmark"></i>
                </div>
                <h3>Twoja lista życzeń jest pusta (DEBUG: <?php echo count($watchlist_movies); ?>)</h3>
                <p>Dodaj filmy, które chcesz obejrzeć!</p>
                <a href="explore.php" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Znajdź filmy
                </a>
            </div>
        <?php else: ?>
            <div class="movies-grid" id="movies-container-watchlist">
                <?php foreach ($watchlist_movies as $movie): ?>
                    <div class="movie-card" data-movie-id="<?php echo $movie['id']; ?>">
                        <div class="movie-poster">
                            <img src="<?php echo $movie['poster_url'] ? htmlspecialchars($movie['poster_url']) : 'assets/images/placeholder-show.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                 loading="lazy">
                            <div class="movie-overlay">
                                <div class="overlay-content">
                                    <button class="quick-btn" title="Oznacz jako obejrzany" data-action="watched"><i class="fas fa-eye"></i></button>
                                    <button class="quick-btn" title="Ulubione" data-action="favorite"><i class="fas fa-heart<?php echo $movie['favorite'] ? ' active' : ''; ?>"></i></button>
                                    <button class="quick-btn" title="Usuń" data-action="remove"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="movie-info">
                            <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <p class="movie-meta">
                                <?php echo htmlspecialchars($movie['genre']); ?> • <?php echo $movie['year']; ?>
                            </p>
                            <p class="added-date">
                                <i class="fas fa-plus"></i>
                                Dodano: <?php echo date('d.m.Y', strtotime($movie['added_at'])); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Activity Tab -->
    <div class="tab-content" id="activity-tab">
        <div class="section-header">
            <h2>Ostatnia aktywność</h2>
        </div>
        
        <?php if (empty($recent_activity)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3>Brak aktywności</h3>
                <p>Twoja aktywność związana z filmami pojawi się tutaj.</p>
            </div>
        <?php else: ?>
            <div class="activity-list">
                <?php foreach ($recent_activity as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <?php
                            switch($activity['activity_type']) {
                                case 'watched_movie':
                                    echo '<i class="fas fa-eye"></i>';
                                    break;
                                case 'rated_movie':
                                    echo '<i class="fas fa-star"></i>';
                                    break;
                                case 'added_movie':
                                    echo '<i class="fas fa-plus"></i>';
                                    break;
                                default:
                                    echo '<i class="fas fa-film"></i>';
                            }
                            ?>
                        </div>
                        
                        <div class="activity-content">
                            <div class="activity-text">
                                <?php
                                switch($activity['activity_type']) {
                                    case 'watched_movie':
                                        echo 'Obejrzał(a) film';
                                        break;
                                    case 'rated_movie':
                                        echo 'Ocenił(a) film';
                                        break;
                                    case 'added_movie':
                                        echo 'Dodał(a) do listy film';
                                        break;
                                }
                                ?>
                                <strong><?php echo htmlspecialchars($activity['movie_title']); ?></strong>
                            </div>
                            <div class="activity-time">
                                <?php echo timeAgo($activity['created_at']); ?>
                            </div>
                        </div>
                        
                        <?php if ($activity['poster_url']): ?>
                            <div class="activity-poster">
                                <img src="<?php echo htmlspecialchars($activity['poster_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($activity['movie_title']); ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.movies-hero {
    background: linear-gradient(135deg, #2a2a2a 0%, #1a1a1a 100%);
    padding: 3rem 0;
    margin-bottom: 3rem;
    border-radius: 16px;
}

.hero-content {
    text-align: center;
    margin-bottom: 3rem;
}

.hero-content h1 {
    font-size: 2.5rem;
    color: #ff9500;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.hero-content p {
    font-size: 1.2rem;
    color: #cccccc;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    max-width: 800px;
    margin: 0 auto;
}

.stat-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: #ff9500;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #ffffff;
}

.stat-label {
    color: #cccccc;
    font-size: 0.9rem;
}

.movies-nav {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    background: #2a2a2a;
    padding: 0.5rem;
    border-radius: 12px;
    overflow-x: auto;
}

.tab-btn {
    background: transparent;
    border: none;
    color: #cccccc;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.tab-btn:hover {
    background: rgba(255, 149, 0, 0.1);
    color: #ff9500;
}

.tab-btn.active {
    background: #ff9500;
    color: #ffffff;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.section-header h2 {
    color: #ff9500;
    font-size: 1.8rem;
}

.view-options {
    display: flex;
    gap: 0.5rem;
}

.view-btn {
    background: #2a2a2a;
    border: 1px solid #3a3a3a;
    color: #cccccc;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.view-btn:hover,
.view-btn.active {
    background: #ff9500;
    color: #ffffff;
    border-color: #ff9500;
}

.movies-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
}

.movies-grid.list-view {
    grid-template-columns: 1fr;
}

.movies-grid.list-view .movie-card {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.movies-grid.list-view .movie-poster {
    width: 120px;
    flex-shrink: 0;
}

.movies-grid.list-view .movie-info {
    flex: 1;
    padding: 0;
}

.movie-card {
    background: #2a2a2a;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.3s ease;
    border: 1px solid #333;
}

.movie-card:hover {
    transform: translateY(-5px);
}

.movie-poster {
    position: relative;
    aspect-ratio: 2/3;
    overflow: hidden;
}

.movie-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.movie-card:hover .movie-poster img {
    transform: scale(1.05);
}

.favorite-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: rgba(255, 68, 68, 0.9);
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
}

.movie-rating {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    background: rgba(0, 0, 0, 0.8);
    color: #ff9500;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.movie-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.movie-card:hover .movie-overlay {
    opacity: 1;
}

.overlay-content {
    display: flex;
    gap: 0.5rem;
}

.quick-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.quick-btn:hover {
    background: #ff9500;
    transform: scale(1.1);
}

.quick-btn .fa-heart.active {
    color: #ff4444;
}

.movie-info {
    padding: 1rem;
}

.movie-info h3 {
    color: #fff;
    margin-bottom: 0.5rem;
    font-size: 1rem;
    line-height: 1.3;
}

.movie-meta {
    color: #aaa;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.watched-date,
.added-date {
    color: #888;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #888;
}

.empty-icon {
    font-size: 4rem;
    color: #ff9500;
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: #fff;
    margin-bottom: 1rem;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.activity-item {
    background: #2a2a2a;
    border: 1px solid #3a3a3a;
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.activity-icon {
    width: 40px;
    height: 40px;
    background: #ff9500;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-text {
    color: #fff;
    margin-bottom: 0.25rem;
}

.activity-time {
    color: #888;
    font-size: 0.8rem;
}

.activity-poster {
    width: 60px;
    height: 90px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.activity-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .hero-content h1 {
        font-size: 2rem;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .movies-nav {
        flex-wrap: wrap;
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .movies-grid {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    }
    
    .activity-item {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
// Tab functionality
function showTab(tabName, event) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabName + '-tab').classList.add('active');
    if (event) {
        let btn = event.target.closest('.tab-btn');
        if (btn) btn.classList.add('active');
    } else {
        let btn = document.querySelector('.tab-btn[onclick*="' + tabName + '"]');
        if (btn) btn.classList.add('active');
    }
}

// View toggle dla różnych zakładek
function toggleView(viewType, event, tab) {
    tab = tab || 'watched';
    const container = document.getElementById('movies-container-' + tab);
    if (!container) return;
    const viewBtns = container.parentElement.querySelectorAll('.view-btn');
    viewBtns.forEach(btn => btn.classList.remove('active'));
    if (event) {
        let btn = event.target.closest('.view-btn');
        if (btn) btn.classList.add('active');
    }
    if (viewType === 'list') {
        container.classList.add('list-view');
    } else {
        container.classList.remove('list-view');
    }
}

// Funkcje pomocnicze dla powiadomień
function showSuccess(message) {
    alert('✓ ' + message);
}

function showError(message) {
    alert('✗ ' + message);
}

// Funkcja do odświeżania statystyk w górnym panelu
function refreshStats() {
    fetch('ajax/get-movie-stats.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const stats = data.stats;
            
            // Znajdź i zaktualizuj każdy stat-number
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                const statNumber = card.querySelector('.stat-number');
                if (statNumber) {
                    switch(index) {
                        case 0: // Obejrzane
                            statNumber.textContent = stats.watched_count;
                            break;
                        case 1: // Do obejrzenia
                            statNumber.textContent = stats.watchlist_count;
                            break;
                        case 2: // Średnia ocena
                            statNumber.textContent = stats.avg_rating || '0';
                            break;
                        case 3: // Czas oglądania
                            statNumber.textContent = Math.round(stats.total_runtime / 60) + 'h';
                            break;
                    }
                }
            });
        }
    })
    .catch(error => {
        console.error('Error refreshing stats:', error);
    });
}

// Navigate to movie details
function goToMovie(movieId) {
    window.location.href = `movie-details.php?id=${movieId}`;
}

// Rate movie
function rateMovie(movieId) {
    const rating = prompt('Oceń film (1-10):');
    if (rating && rating >= 1 && rating <= 10) {
        updateMovieStatus(movieId, null, rating);
    }
}

// Mark as watched
function markAsWatched(movieId) {
    if (confirm('Oznaczyć film jako obejrzany?')) {
        updateMovieStatus(movieId, 'watched');
    }
}

// Toggle favorite
function toggleFavorite(movieId, isFavorite) {
    fetch('ajax/movie-favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            movie_id: movieId,
            favorite: isFavorite,
            csrf_token: document.querySelector('input[name="csrf_token"]')?.value || ''
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Zaktualizowano ulubione!');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showError(data.message || 'Błąd podczas aktualizacji');
        }
    })
    .catch(() => showError('Błąd połączenia'));
}

// Remove from list
function removeFromList(movieId) {
    if (confirm('Usunąć film z listy?')) {
        fetch('ajax/movie-remove.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                movie_id: movieId,
                csrf_token: document.querySelector('input[name="csrf_token"]')?.value || ''
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('Film usunięty z listy!');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showError(data.message || 'Błąd podczas usuwania');
            }
        })
        .catch(() => showError('Błąd połączenia'));
    }
}

// Update movie status
function updateMovieStatus(movieId, status, rating = null) {
    fetch('ajax/movie-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            movie_id: movieId,
            status: status,
            rating: rating,
            csrf_token: document.querySelector('input[name="csrf_token"]')?.value || ''
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Status filmu zaktualizowany!');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showError(data.message || 'Błąd podczas aktualizacji');
        }
    })
    .catch(() => showError('Błąd połączenia'));
}

// Akcje na przyciskach overlay (delegacja)
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.quick-btn');
    if (!btn) return;
    const card = btn.closest('.movie-card');
    if (!card) return;
    const movieId = card.getAttribute('data-movie-id');
    const action = btn.getAttribute('data-action');
    e.stopPropagation();
    
    if (action === 'rate') {
        rateMovie(movieId);
    } else if (action === 'favorite') {
        // Pobierz aktualny stan serca
        const heart = btn.querySelector('.fa-heart');
        const isActive = heart && heart.classList.contains('active');
        toggleFavorite(movieId, isActive ? 0 : 1);
    } else if (action === 'remove') {
        removeFromList(movieId);
    } else if (action === 'watched') {
        markAsWatched(movieId);
    }
});

// Obsługa kliknięć w karty (przejście do szczegółów)
document.addEventListener('click', function(e) {
    const card = e.target.closest('.movie-card');
    if (!card) return;
    
    // Sprawdź czy kliknięto przycisk overlay - jeśli tak, nie przechodź do szczegółów
    if (e.target.closest('.quick-btn') || e.target.closest('.movie-overlay')) {
        return;
    }
    
    const movieId = card.getAttribute('data-movie-id');
    if (movieId) {
        goToMovie(movieId);
    }
});
</script>

<!-- CSRF Token -->
<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

<?php require_once 'includes/footer.php'; ?>
