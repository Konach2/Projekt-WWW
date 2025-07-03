<?php
/**
 * Strona główna TV Time Clone
 * Autor: System
 * Data: 2025-06-29
 */

require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Ustawienia strony
$page_title = 'TV Time Clone - Śledź swoje ulubione seriale';
$page_description = 'Odkrywaj nowe seriale, śledź postępy w oglądaniu i dziel się opiniami z innymi fanami';

// Parametry wyszukiwania i filtrowania
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Parametry paginacji
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Walidacja parametrów sortowania
$allowed_sort = ['title', 'year', 'created_at', 'avg_rating'];
$allowed_order = ['ASC', 'DESC'];

if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'created_at';
}

if (!in_array($order, $allowed_order)) {
    $order = 'DESC';
}

try {
    // Budowanie zapytania SQL
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(s.title LIKE :search OR s.description LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    if (!empty($genre)) {
        $where_conditions[] = "s.genre = :genre";
        $params[':genre'] = $genre;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Zapytanie o liczbę wszystkich seriali
    $count_sql = "
        SELECT COUNT(DISTINCT s.id) as total
        FROM shows s
        {$where_clause}
    ";
    
    $total_shows = fetchCount($count_sql, $params);
    $total_pages = ceil($total_shows / $per_page);
    
    // Zapytanie o seriale - uproszczone
    $shows_sql = "
        SELECT 
            s.*,
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(r.id) as review_count
        FROM shows s
        LEFT JOIN reviews r ON s.id = r.show_id
        {$where_clause}
        GROUP BY s.id, s.title, s.description, s.genre, s.year, s.poster_url, s.created_at
        ORDER BY s.{$sort_by} {$order}
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $shows = fetchAll($shows_sql, $params);
    
    // Pobierz dostępne gatunki
    $genres_sql = "SELECT DISTINCT genre FROM shows ORDER BY genre";
    $genres = fetchAll($genres_sql);
    
} catch (Exception $e) {
    error_log("Błąd na stronie głównej: " . $e->getMessage());
    $shows = [];
    $genres = [];
    $total_pages = 1;
    $total_shows = 0;
    setFlashMessage('error', 'Wystąpił błąd podczas ładowania seriali. Spróbuj ponownie.');
}

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1>Odkrywaj i śledź swoje ulubione seriale</h1>
        <p>Dołącz do społeczności fanów telewizji. Śledź postępy w oglądaniu, oceniaj i dziel się opiniami o swoich ulubionych serialach.</p>
        
        <?php if (!isLoggedIn()): ?>
            <div class="hero-actions">
                <a href="register.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-user-plus"></i>
                    Dołącz teraz
                </a>
                <a href="login.php" class="btn btn-outline btn-lg">
                    <i class="fas fa-sign-in-alt"></i>
                    Zaloguj się
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Search and Filters -->
<section class="search-section">
    <div class="search-filters">
        <!-- Search Box -->
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input 
                type="text" 
                id="searchInput"
                class="search-input form-control" 
                placeholder="Wyszukaj seriale..."
                value="<?php echo htmlspecialchars($search); ?>"
                autocomplete="off"
            >
            <div id="searchResults" class="search-results" style="display: none;"></div>
        </div>
        
        <!-- Genre Filter -->
        <select class="form-control filter-select" id="genreFilter" onchange="applyFilters()">
            <option value="">Wszystkie gatunki</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?php echo htmlspecialchars($g['genre']); ?>" 
                        <?php echo $genre === $g['genre'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($g['genre']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <!-- Sort Options -->
        <select class="form-control filter-select" id="sortFilter" onchange="applyFilters()">
            <option value="created_at-DESC" <?php echo ($sort_by === 'created_at' && $order === 'DESC') ? 'selected' : ''; ?>>
                Najnowsze
            </option>
            <option value="title-ASC" <?php echo ($sort_by === 'title' && $order === 'ASC') ? 'selected' : ''; ?>>
                Tytuł A-Z
            </option>
            <option value="title-DESC" <?php echo ($sort_by === 'title' && $order === 'DESC') ? 'selected' : ''; ?>>
                Tytuł Z-A
            </option>
            <option value="year-DESC" <?php echo ($sort_by === 'year' && $order === 'DESC') ? 'selected' : ''; ?>>
                Najnowsze filmy
            </option>
            <option value="year-ASC" <?php echo ($sort_by === 'year' && $order === 'ASC') ? 'selected' : ''; ?>>
                Najstarsze filmy
            </option>
            <option value="avg_rating-DESC" <?php echo ($sort_by === 'avg_rating' && $order === 'DESC') ? 'selected' : ''; ?>>
                Najwyżej oceniane
            </option>
        </select>
        
        <!-- Clear Filters Button -->
        <?php if (!empty($search) || !empty($genre)): ?>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times"></i>
                Wyczyść filtry
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- Shows Grid -->
<section class="shows-section">
    <?php if (empty($shows)): ?>
        <div class="no-results">
            <div class="no-results-icon">
                <i class="fas fa-tv"></i>
            </div>
            <h3>Brak seriali</h3>
            <p>
                <?php if (!empty($search) || !empty($genre)): ?>
                    Nie znaleziono seriali spełniających kryteria wyszukiwania.
                    <br><a href="index.php">Pokaż wszystkie seriale</a>
                <?php else: ?>
                    Nie ma jeszcze żadnych seriali w bazie danych.
                    <?php if (isLoggedIn()): ?>
                        <br><a href="add-show.php">Dodaj pierwszy serial</a>
                    <?php endif; ?>
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <!-- Results Info -->
        <div class="results-info">
            <p>
                <?php if (!empty($search) || !empty($genre)): ?>
                    Znaleziono <strong><?php echo number_format($total_shows); ?></strong> 
                    <?php echo $total_shows === 1 ? 'serial' : ($total_shows < 5 ? 'seriale' : 'seriali'); ?>
                    <?php if (!empty($search)): ?>
                        dla zapytania "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    <?php endif; ?>
                    <?php if (!empty($genre)): ?>
                        w kategorii "<strong><?php echo htmlspecialchars($genre); ?></strong>"
                    <?php endif; ?>
                <?php else: ?>
                    <strong><?php echo number_format($total_shows); ?></strong> 
                    <?php echo $total_shows === 1 ? 'serial' : ($total_shows < 5 ? 'seriale' : 'seriali'); ?> 
                    w bazie danych
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Shows Grid -->
        <div class="shows-grid">
            <?php foreach ($shows as $show): ?>
                <div class="show-card fade-in">
                    <div class="show-poster">
                        <a href="show-details.php?id=<?php echo $show['id']; ?>">
                            <img 
                                src="<?php echo getImageUrl($show['poster_url']); ?>" 
                                alt="<?php echo htmlspecialchars($show['title']); ?>"
                                loading="lazy"
                            >
                        </a>
                        
                        <?php if (isLoggedIn()): ?>
                            <!-- Status badge można dodać później -->
                        <?php endif; ?>
                    </div>
                    
                    <div class="show-info">
                        <h3 class="show-title">
                            <a href="show-details.php?id=<?php echo $show['id']; ?>">
                                <?php echo htmlspecialchars($show['title']); ?>
                            </a>
                        </h3>
                        
                        <div class="show-year"><?php echo $show['year']; ?></div>
                        
                        <div class="show-genre"><?php echo htmlspecialchars($show['genre']); ?></div>
                        
                        <p class="show-description">
                            <?php echo htmlspecialchars(mb_substr($show['description'], 0, 120)) . '...'; ?>
                        </p>
                        
                        <div class="show-rating">
                            <div class="rating-stars">
                                <?php
                                $rating = round($show['avg_rating'], 1);
                                $full_stars = floor($rating);
                                $half_star = ($rating - $full_stars) >= 0.5;
                                
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $full_stars) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i == $full_stars + 1 && $half_star) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            
                            <span class="rating-value">
                                <?php echo $rating > 0 ? number_format($rating, 1) : 'Brak ocen'; ?>
                            </span>
                            
                            <?php if ($show['review_count'] > 0): ?>
                                <span class="rating-count">
                                    (<?php echo $show['review_count']; ?> 
                                    <?php echo $show['review_count'] === 1 ? 'ocena' : 'ocen'; ?>)
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isLoggedIn()): ?>
                            <div class="show-actions">
                                <!-- Quick Add Buttons -->
                                <div class="quick-add-buttons">
                                    <button class="btn btn-sm btn-success quick-add-btn" 
                                            data-show-id="<?php echo $show['id']; ?>" 
                                            data-status="plan_to_watch"
                                            title="Dodaj do Chcę obejrzeć">
                                        <i class="fas fa-bookmark"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary quick-add-btn" 
                                            data-show-id="<?php echo $show['id']; ?>" 
                                            data-status="watching"
                                            title="Oznacz jako Oglądane">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                                
                                <a href="show-details.php?id=<?php echo $show['id']; ?>" class="btn btn-outline btn-sm">
                                    <i class="fas fa-info-circle"></i>
                                    Szczegóły
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="show-actions">
                                <a href="show-details.php?id=<?php echo $show['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i>
                                    Zobacz więcej
                                </a>
                                <a href="login.php" class="btn btn-outline btn-sm">
                                    <i class="fas fa-user-plus"></i>
                                    Zaloguj się
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="pagination">
                <?php
                // Parametry URL do zachowania filtrów
                $url_params = [];
                if (!empty($search)) $url_params['search'] = $search;
                if (!empty($genre)) $url_params['genre'] = $genre;
                if ($sort_by !== 'created_at') $url_params['sort'] = $sort_by;
                if ($order !== 'DESC') $url_params['order'] = $order;
                
                $base_url = 'index.php?' . http_build_query($url_params);
                $separator = empty($url_params) ? '?' : '&';
                
                // Poprzednia strona
                if ($page > 1): ?>
                    <a href="<?php echo $base_url . $separator . 'page=' . ($page - 1); ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php
                // Oblicz zakres stron do wyświetlenia
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                // Pierwsze strony
                if ($start_page > 1): ?>
                    <a href="<?php echo $base_url . $separator . 'page=1'; ?>">1</a>
                    <?php if ($start_page > 2): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php
                // Strony w okolicy aktualnej
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo $base_url . $separator . 'page=' . $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php
                // Ostatnie strony
                if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span>...</span>
                    <?php endif; ?>
                    <a href="<?php echo $base_url . $separator . 'page=' . $total_pages; ?>"><?php echo $total_pages; ?></a>
                <?php endif; ?>
                
                <?php
                // Następna strona
                if ($page < $total_pages): ?>
                    <a href="<?php echo $base_url . $separator . 'page=' . ($page + 1); ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<!-- CSRF Token for AJAX -->
<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

<script>
// Funkcja do zastosowania filtrów
function applyFilters() {
    const searchValue = document.getElementById('searchInput').value;
    const genreValue = document.getElementById('genreFilter').value;
    const sortValue = document.getElementById('sortFilter').value;
    
    const [sortBy, order] = sortValue.split('-');
    
    let url = 'index.php?';
    const params = [];
    
    if (searchValue.trim()) {
        params.push('search=' + encodeURIComponent(searchValue.trim()));
    }
    
    if (genreValue) {
        params.push('genre=' + encodeURIComponent(genreValue));
    }
    
    if (sortBy !== 'created_at') {
        params.push('sort=' + encodeURIComponent(sortBy));
    }
    
    if (order !== 'DESC') {
        params.push('order=' + encodeURIComponent(order));
    }
    
    url += params.join('&');
    
    window.location.href = url;
}

// Live search z debouncing
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        applyFilters();
    }, 1000); // Czekaj 1 sekundę po zakończeniu wpisywania
});

// Enter w polu wyszukiwania
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        clearTimeout(searchTimeout);
        applyFilters();
    }
});

// Animacja pojawiania się kart
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.show-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        }, index * 50);
    });
});
</script>

<style>
/* Dodatkowe style dla strony głównej */
.hero {
    text-align: center;
    padding: 4rem 0;
    background: linear-gradient(135deg, #2a2a2a 0%, #1a1a1a 100%);
    border-radius: 12px;
    margin-bottom: 3rem;
}

.hero-content h1 {
    font-size: 3rem;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #ff9500, #ffb347);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.hero-content p {
    font-size: 1.2rem;
    color: #cccccc;
    max-width: 600px;
    margin: 0 auto 2rem;
    line-height: 1.6;
}

.hero-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.search-section {
    margin-bottom: 2rem;
}

.results-info {
    margin-bottom: 1.5rem;
    color: #cccccc;
}

.no-results {
    text-align: center;
    padding: 4rem 2rem;
    color: #888888;
}

.no-results-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: #ff9500;
}

.no-results h3 {
    color: #ffffff;
    margin-bottom: 1rem;
}

.no-results a {
    color: #ff9500;
    text-decoration: none;
}

.no-results a:hover {
    color: #ffb347;
}

.show-actions {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #3a3a3a;
}

.rating-count {
    color: #888888;
    font-size: 0.8rem;
    margin-left: 0.5rem;
}

@media (max-width: 768px) {
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .hero-content p {
        font-size: 1rem;
    }
    
    .hero-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .hero-actions .btn {
        width: 100%;
        max-width: 300px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
