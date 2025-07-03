<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

try {
    // Polecane seriale (najpopularniejsze)
    $popular_shows = fetchAll("
        SELECT s.*, COUNT(us.id) as popularity 
        FROM shows s 
        LEFT JOIN user_shows us ON s.id = us.show_id 
        GROUP BY s.id 
        ORDER BY popularity DESC, s.created_at DESC 
        LIMIT 12
    ");
    
    // Najnowsze seriale
    $newest_shows = fetchAll("
        SELECT * FROM shows 
        ORDER BY created_at DESC 
        LIMIT 8
    ");
    
    // Polecane filmy (najpopularniejsze)
    $popular_movies = fetchAll("
        SELECT m.*, COUNT(um.id) as popularity 
        FROM movies m 
        LEFT JOIN user_movies um ON m.id = um.movie_id 
        GROUP BY m.id 
        ORDER BY popularity DESC, m.created_at DESC 
        LIMIT 12
    ");
    
    // Najnowsze filmy
    $newest_movies = fetchAll("
        SELECT * FROM movies 
        ORDER BY created_at DESC 
        LIMIT 8
    ");
    
    // Gatunki seriali
    $show_genres = fetchAll("
        SELECT genre, COUNT(*) as count 
        FROM shows 
        GROUP BY genre 
        HAVING count >= 2
        ORDER BY count DESC
    ");
    
    // Gatunki filmów
    $movie_genres = fetchAll("
        SELECT genre, COUNT(*) as count 
        FROM movies 
        GROUP BY genre 
        HAVING count >= 2
        ORDER BY count DESC
    ");
    
} catch (Exception $e) {
    $popular_shows = [];
    $newest_shows = [];
    $popular_movies = [];
    $newest_movies = [];
    $show_genres = [];
    $movie_genres = [];
}

$page_title = 'Eksploruj - TV Time Clone';
?>

<div class="explore-container">
    <!-- Hero Section -->
    <div class="explore-hero">
        <div class="hero-content">
            <h1>Odkrywaj nowe seriale i filmy</h1>
            <p>Znajdź swój następny ulubiony tytuł w naszej bibliotece</p>
            
            <div class="search-box-large">
                <input type="text" id="exploreSearch" placeholder="Szukaj seriali i filmów..." onkeyup="liveSearch(this.value)">
                <button type="button" onclick="performSearch(document.getElementById('exploreSearch').value)">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        
        <div class="hero-image">
            <div class="floating-cards">
                <?php foreach (array_slice($popular_shows, 0, 3) as $show): ?>
                <div class="floating-card">
                    <img src="<?php echo getImageUrl($show['poster_url']); ?>" 
                         alt="<?php echo htmlspecialchars($show['title']); ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Content Type Toggle -->
    <div class="content-toggle">
        <button class="toggle-btn active" onclick="showContent('shows')" id="shows-toggle">
            <i class="fas fa-tv"></i>
            Seriale
        </button>
        <button class="toggle-btn" onclick="showContent('movies')" id="movies-toggle">
            <i class="fas fa-film"></i>
            Filmy
        </button>
    </div>

    <!-- Shows Content -->
    <div class="content-section active" id="shows-content">
        <!-- Quick Genres for Shows -->
        <div class="genres-section">
            <h2>Eksploruj seriale według gatunku</h2>
            <div class="genres-grid">
                <?php foreach ($show_genres as $genre): ?>
                <div class="genre-card" onclick="filterByGenre('<?php echo htmlspecialchars($genre['genre']); ?>', 'shows')">
                    <div class="genre-icon">
                        <?php
                        $icons = [
                            'Drama' => 'fas fa-theater-masks',
                            'Comedy' => 'fas fa-laugh',
                            'Action' => 'fas fa-fist-raised',
                            'Horror' => 'fas fa-ghost',
                            'Sci-Fi' => 'fas fa-rocket',
                            'Romance' => 'fas fa-heart',
                            'Thriller' => 'fas fa-eye',
                            'Crime' => 'fas fa-user-secret'
                        ];
                        $icon = $icons[$genre['genre']] ?? 'fas fa-tv';
                        ?>
                        <i class="<?php echo $icon; ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($genre['genre']); ?></h3>
                    <p><?php echo $genre['count']; ?> seriali</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Popular Shows -->
        <section class="content-section-main">
            <div class="section-header">
                <h2>Popularne seriale</h2>
                <button class="btn-more" onclick="loadMore('popular-shows')">Zobacz więcej</button>
            </div>
            
            <div class="shows-grid">
                <?php foreach ($popular_shows as $show): ?>
                    <div class="show-card" onclick="goToShow(<?php echo $show['id']; ?>)">
                        <div class="show-poster">
                            <img src="<?php echo getImageUrl($show['poster_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($show['title']); ?>"
                                 loading="lazy">
                            
                            <div class="show-overlay">
                                <div class="show-rating">
                                    <i class="fas fa-users"></i>
                                    <?php echo $show['popularity']; ?>
                                </div>
                                <button class="add-to-list quick-add-btn" 
                                        onclick="event.stopPropagation(); addToWatchlist(<?php echo $show['id']; ?>)"
                                        title="Dodaj do listy">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="show-info">
                            <h3><?php echo htmlspecialchars($show['title']); ?></h3>
                            <p><?php echo htmlspecialchars($show['genre']); ?> • <?php echo $show['year']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Newest Shows -->
        <section class="content-section-main">
            <div class="section-header">
                <h2>Najnowsze seriale</h2>
                <button class="btn-more" onclick="loadMore('newest-shows')">Zobacz więcej</button>
            </div>
            
            <div class="shows-grid">
                <?php foreach ($newest_shows as $show): ?>
                    <div class="show-card" onclick="goToShow(<?php echo $show['id']; ?>)">
                        <div class="show-poster">
                            <img src="<?php echo getImageUrl($show['poster_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($show['title']); ?>"
                                 loading="lazy">
                            
                            <div class="show-overlay">
                                <div class="show-badge">Nowy</div>
                                <button class="add-to-list quick-add-btn" 
                                        onclick="event.stopPropagation(); addToWatchlist(<?php echo $show['id']; ?>)"
                                        title="Dodaj do listy">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="show-info">
                            <h3><?php echo htmlspecialchars($show['title']); ?></h3>
                            <p><?php echo htmlspecialchars($show['genre']); ?> • <?php echo $show['year']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- Movies Content -->
    <div class="content-section" id="movies-content">
        <!-- Quick Genres for Movies -->
        <div class="genres-section">
            <h2>Eksploruj filmy według gatunku</h2>
            <div class="genres-grid">
                <?php foreach ($movie_genres as $genre): ?>
                <div class="genre-card" onclick="filterByGenre('<?php echo htmlspecialchars($genre['genre']); ?>', 'movies')">
                    <div class="genre-icon">
                        <?php
                        $icons = [
                            'Drama' => 'fas fa-theater-masks',
                            'Comedy' => 'fas fa-laugh',
                            'Action' => 'fas fa-fist-raised',
                            'Horror' => 'fas fa-ghost',
                            'Sci-Fi' => 'fas fa-rocket',
                            'Romance' => 'fas fa-heart',
                            'Thriller' => 'fas fa-eye',
                            'Crime' => 'fas fa-user-secret',
                            'Adventure' => 'fas fa-map',
                            'Fantasy' => 'fas fa-magic'
                        ];
                        $icon = $icons[$genre['genre']] ?? 'fas fa-film';
                        ?>
                        <i class="<?php echo $icon; ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($genre['genre']); ?></h3>
                    <p><?php echo $genre['count']; ?> filmów</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Popular Movies -->
        <section class="content-section-main">
            <div class="section-header">
                <h2>Popularne filmy</h2>
                <button class="btn-more" onclick="loadMore('popular-movies')">Zobacz więcej</button>
            </div>
            
            <div class="shows-grid">
                <?php foreach ($popular_movies as $movie): ?>
                    <div class="show-card" onclick="goToMovie(<?php echo $movie['id']; ?>)">
                        <div class="show-poster">
                            <img src="<?php echo getMovieImageUrl($movie['poster_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                 loading="lazy">
                            
                            <div class="show-overlay">
                                <div class="show-rating">
                                    <i class="fas fa-users"></i>
                                    <?php echo $movie['popularity']; ?>
                                </div>
                                <button class="add-to-list quick-add-btn" 
                                        onclick="event.stopPropagation(); addMovieToWatchlist(<?php echo $movie['id']; ?>)"
                                        title="Dodaj do listy">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="show-info">
                            <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <p><?php echo htmlspecialchars($movie['genre']); ?> • <?php echo $movie['year']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Newest Movies -->
        <section class="content-section-main">
            <div class="section-header">
                <h2>Najnowsze filmy</h2>
                <button class="btn-more" onclick="loadMore('newest-movies')">Zobacz więcej</button>
            </div>
            
            <div class="shows-grid">
                <?php if (!empty($newest_movies)): ?>
                    <?php foreach ($newest_movies as $movie): ?>
                        <div class="show-card" onclick="goToMovie(<?php echo $movie['id']; ?>)">
                            <div class="show-poster">
                                <img src="<?php echo getMovieImageUrl($movie['poster_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                     loading="lazy">
                                
                                <div class="show-overlay">
                                    <div class="show-badge">Nowy</div>
                                <button class="add-to-list quick-add-btn" 
                                        onclick="event.stopPropagation(); addMovieToWatchlist(<?php echo $movie['id']; ?>)"
                                        title="Dodaj do listy">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="show-info">
                            <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <p><?php echo htmlspecialchars($movie['genre']); ?> • <?php echo $movie['year']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php else: ?>
                    <p>Brak najnowszych filmów.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<style>
.explore-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.explore-hero {
    display: flex;
    align-items: center;
    gap: 3rem;
    margin-bottom: 4rem;
    min-height: 400px;
}

.hero-content {
    flex: 1;
}

.hero-content h1 {
    font-size: 3.5rem;
    color: #fff;
    margin-bottom: 1rem;
    font-weight: 700;
}

.hero-content p {
    font-size: 1.3rem;
    color: #aaa;
    margin-bottom: 2rem;
}

.search-box-large {
    display: flex;
    max-width: 500px;
    background: #2a2a2a;
    border-radius: 25px;
    overflow: hidden;
    border: 2px solid #333;
    transition: border-color 0.3s ease;
}

.search-box-large:focus-within {
    border-color: #ff9500;
}

.search-box-large input {
    flex: 1;
    padding: 1rem 1.5rem;
    background: transparent;
    border: none;
    color: #fff;
    font-size: 1.1rem;
}

.search-box-large input::placeholder {
    color: #888;
}

.search-box-large button {
    padding: 1rem 1.5rem;
    background: #ff9500;
    border: none;
    color: #fff;
    cursor: pointer;
    transition: background 0.3s ease;
}

.search-box-large button:hover {
    background: #ffa733;
}

.hero-image {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
}

.floating-cards {
    position: relative;
    width: 300px;
    height: 300px;
}

.floating-card {
    position: absolute;
    width: 120px;
    height: 180px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    transition: transform 0.3s ease;
}

.floating-card:nth-child(1) {
    top: 0;
    left: 0;
    z-index: 3;
    animation: float1 6s ease-in-out infinite;
}

.floating-card:nth-child(2) {
    top: 60px;
    right: 0;
    z-index: 2;
    animation: float2 6s ease-in-out infinite 2s;
}

.floating-card:nth-child(3) {
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1;
    animation: float3 6s ease-in-out infinite 4s;
}

.floating-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

@keyframes float1 {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

@keyframes float2 {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-15px); }
}

@keyframes float3 {
    0%, 100% { transform: translateX(-50%) translateY(0px); }
    50% { transform: translateX(-50%) translateY(-25px); }
}

.genres-section {
    margin-bottom: 4rem;
}

.genres-section h2 {
    color: #ff9500;
    font-size: 2rem;
    margin-bottom: 2rem;
    text-align: center;
}

.genres-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1.5rem;
}

.genre-card {
    background: #2a2a2a;
    padding: 2rem 1rem;
    border-radius: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid #333;
}

.genre-card:hover {
    transform: translateY(-5px);
    border-color: #ff9500;
    box-shadow: 0 8px 25px rgba(255, 149, 0, 0.15);
}

.genre-icon {
    font-size: 2.5rem;
    color: #ff9500;
    margin-bottom: 1rem;
}

.genre-card h3 {
    color: #fff;
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.genre-card p {
    color: #aaa;
    font-size: 0.9rem;
}

.shows-section {
    margin-bottom: 4rem;
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

.shows-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
}

.show-card {
    background: #2a2a2a;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.3s ease;
    border: 1px solid #333;
}

.show-card:hover {
    transform: translateY(-5px);
}

.show-poster {
    position: relative;
    aspect-ratio: 2/3;
    overflow: hidden;
}

.show-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.show-card:hover .show-poster img {
    transform: scale(1.05);
}

.show-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 1rem;
}

.show-card:hover .show-overlay {
    opacity: 1;
}

.show-rating {
    background: rgba(0,0,0,0.8);
    padding: 0.5rem;
    border-radius: 8px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    align-self: flex-start;
}

.show-badge {
    background: #ff9500;
    color: #fff;
    padding: 0.3rem 0.8rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    align-self: flex-start;
}

.add-to-list {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    align-self: flex-end;
}

.show-info {
    padding: 1rem;
}

.show-info h3 {
    color: #fff;
    margin-bottom: 0.5rem;
    font-size: 1rem;
    line-height: 1.3;
}

.show-info p {
    color: #aaa;
    font-size: 0.9rem;
}

.content-toggle {
    display: flex;
    justify-content: center;
    margin-bottom: 3rem;
}

.toggle-btn {
    background: #2a2a2a;
    color: #fff;
    border: none;
    padding: 1rem 2rem;
    border-radius: 25px;
    cursor: pointer;
    transition: background 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.toggle-btn i {
    font-size: 1.2rem;
}

.toggle-btn.active {
    background: #ff9500;
    color: #fff;
}

.content-section {
    display: none;
}

.content-section.active {
    display: block;
}

.content-section-main {
    margin-bottom: 4rem;
}

.btn-more {
    background: #ff9500;
    color: #fff;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 25px;
    cursor: pointer;
    transition: background 0.3s ease;
    align-self: flex-start;
}

.btn-more:hover {
    background: #ffa733;
}

@media (max-width: 768px) {
    .explore-hero {
        flex-direction: column;
        text-align: center;
        gap: 2rem;
    }
    
    .hero-content h1 {
        font-size: 2.5rem;
    }
    
    .genres-grid {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    }
    
    .shows-grid {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
}
</style>

<script>
function searchShows(query) {
    if (query.trim().length >= 2) {
        window.location.href = `index.php?search=${encodeURIComponent(query)}`;
    }
}

function filterByGenre(genre, type = 'shows') {
    window.location.href = `index.php?genre=${encodeURIComponent(genre)}&type=${type}`;
}

function goToShow(showId) {
    window.location.href = `show-details.php?id=${showId}`;
}

function goToMovie(movieId) {
    window.location.href = `movie-details.php?id=${movieId}`;
}

function addToWatchlist(showId, type = 'show') {
    <?php if (isLoggedIn()): ?>
    showAddToListModal(showId, type);
    <?php else: ?>
    window.location.href = 'login.php';
    <?php endif; ?>
}

function addMovieToWatchlist(movieId) {
    addToWatchlist(movieId, 'movie');
}

// Globalna funkcja do odświeżania statystyk filmów
function refreshStats() {
    fetch('ajax/get-movie-stats.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const stats = data.stats;
            
            // Znajdź i zaktualizuj każdy stat-number jeśli strona ma statystyki
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

function showAddToListModal(itemId, type = 'show') {
    // Usuń poprzedni modal jeśli istnieje
    const existingModal = document.getElementById('addToListModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const isMovie = type === 'movie';
    const itemName = isMovie ? 'film' : 'serial';
    
    const modal = document.createElement('div');
    modal.id = 'addToListModal';
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Dodaj ${itemName} do listy</h3>
                <button class="modal-close" onclick="closeAddToListModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <p>Wybierz status dla tego ${itemName}a:</p>
                
                <div class="status-options">
                    ${isMovie ? `
                    <button class="status-btn watching" onclick="addToListWithStatus(${itemId}, 'watching', '${type}')">
                        <div class="status-icon">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        <div class="status-info">
                            <span class="status-title">Oglądane</span>
                            <span class="status-desc">Obecnie oglądam</span>
                        </div>
                    </button>
                    
                    <button class="status-btn planned" onclick="addToListWithStatus(${itemId}, 'want_to_watch', '${type}')">
                        <div class="status-icon">
                            <i class="fas fa-bookmark"></i>
                        </div>
                        <div class="status-info">
                            <span class="status-title">Chcę obejrzeć</span>
                            <span class="status-desc">Planuję to obejrzeć</span>
                        </div>
                    </button>
                    
                    <button class="status-btn completed" onclick="addToListWithStatus(${itemId}, 'watched', '${type}')">
                        <div class="status-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="status-info">
                            <span class="status-title">Obejrzane</span>
                            <span class="status-desc">Już obejrzałem</span>
                        </div>
                    </button>
                    
                    <button class="status-btn dropped" onclick="addToListWithStatus(${itemId}, 'dropped', '${type}')">
                        <div class="status-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="status-info">
                            <span class="status-title">Porzucone</span>
                            <span class="status-desc">Nie dokończę</span>
                        </div>
                    </button>
                    ` : `
                    <button class="status-btn watching" onclick="addToListWithStatus(${itemId}, 'watching', '${type}')">
                        <div class="status-icon">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        <div class="status-info">
                            <span class="status-title">Oglądane</span>
                            <span class="status-desc">Obecnie oglądam</span>
                        </div>
                    </button>
                    
                    <button class="status-btn planned" onclick="addToListWithStatus(${itemId}, 'plan_to_watch', '${type}')">
                        <div class="status-icon">
                            <i class="fas fa-bookmark"></i>
                        </div>
                        <div class="status-info">
                            <span class="status-title">Chcę obejrzeć</span>
                            <span class="status-desc">Planuję to obejrzeć</span>
                        </div>
                    </button>
                    
                    <button class="status-btn completed" onclick="addToListWithStatus(${itemId}, 'completed', '${type}')">
                        <div class="status-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="status-info">
                            <span class="status-title">Ukończone</span>
                            <span class="status-desc">Już obejrzałem</span>
                        </div>
                    </button>
                    
                    <button class="status-btn on-hold" onclick="addToListWithStatus(${itemId}, 'on_hold', '${type}')">
                        <div class="status-icon">
                            <i class="fas fa-pause-circle"></i>
                        </div>
                        <div class="status-info">
                            <span class="status-title">Wstrzymane</span>
                            <span class="status-desc">Tymczasowo przerwałem</span>
                        </div>
                    </button>
                    `}
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Animacja pojawiania się
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
    
    // Zamknij modal po kliknięciu tła
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeAddToListModal();
        }
    });
}

function closeAddToListModal() {
    const modal = document.getElementById('addToListModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

function addToListWithStatus(itemId, status, type = 'show') {
    const isMovie = type === 'movie';
    const endpoint = isMovie ? 'ajax/movie-status.php' : 'ajax/update-show-status.php';
    const itemKey = isMovie ? 'movie_id' : 'show_id';
    
    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            [itemKey]: itemId,
            status: status,
            csrf_token: document.querySelector('input[name="csrf_token"]')?.value || ''
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAddToListModal();
            showSuccessNotification(isMovie ? 'Film dodany do listy!' : 'Serial dodany do listy!');
            
            // Odśwież statystyki na stronie movies.php jeśli istnieje
            if (typeof refreshStats === 'function') {
                refreshStats();
            }
            
            // Sprawdź czy jesteśmy na stronie movies.php i odśwież ją
            if (window.location.pathname.includes('movies.php')) {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            showErrorNotification(data.message || 'Wystąpił błąd');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorNotification('Wystąpił błąd połączenia');
    });
}

function showSuccessNotification(message) {
    showNotification(message, 'success');
}

function showErrorNotification(message) {
    showNotification(message, 'error');
}

function showNotification(message, type = 'info') {
    // Usuń poprzednie powiadomienia
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animacja pojawiania się
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Automatyczne usunięcie po 3 sekundach
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

function addMovieToWatchlist(movieId) {
    <?php if (isLoggedIn()): ?>
    showAddToListModal(movieId, 'movie');
    <?php else: ?>
    if (confirm('Aby dodać filmy do listy, musisz się zalogować. Przejść do logowania?')) {
        window.location.href = 'login.php';
    }
    <?php endif; ?>
}

function showContent(type) {
    const isShow = type === 'shows';
    
    document.getElementById('shows-content').classList.toggle('active', isShow);
    document.getElementById('movies-content').classList.toggle('active', !isShow);
    document.getElementById('shows-toggle').classList.toggle('active', isShow);
    document.getElementById('movies-toggle').classList.toggle('active', !isShow);
}

// Enter w polu wyszukiwania
document.getElementById('exploreSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchShows(this.value);
    }
});
</script>

<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

<?php require_once 'includes/footer.php'; ?>
