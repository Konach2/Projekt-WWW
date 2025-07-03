<?php
/**
 * Lista filmów - Admin Panel
 */

require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Sprawdź czy użytkownik ma uprawnienia administratora
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

// Parametry paginacji i wyszukiwania
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$search = trim($_GET['search'] ?? '');
$genre_filter = $_GET['genre'] ?? '';
$year_filter = (int)($_GET['year'] ?? 0);

// Buduj warunki WHERE
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(m.title LIKE ? OR m.director LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($genre_filter)) {
    $where_conditions[] = "m.genre = ?";
    $params[] = $genre_filter;
}

if ($year_filter > 0) {
    $where_conditions[] = "m.year = ?";
    $params[] = $year_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Pobierz całkowitą liczbę filmów
try {
    $total_count = fetchCount("SELECT COUNT(*) FROM movies m $where_clause", $params);
    $total_pages = ceil($total_count / $per_page);
    $offset = ($page - 1) * $per_page;
    
    // Pobierz filmy z paginacją
    $movies = fetchAll("
        SELECT m.*, 
               (SELECT COUNT(*) FROM user_movies um WHERE um.movie_id = m.id) as user_count
        FROM movies m 
        $where_clause
        ORDER BY m.created_at DESC 
        LIMIT $per_page OFFSET $offset
    ", $params);
    
    // Pobierz dostępne gatunki
    $genres = fetchAll("SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL ORDER BY genre");
    
    // Pobierz dostępne lata
    $years = fetchAll("SELECT DISTINCT year FROM movies WHERE year IS NOT NULL ORDER BY year DESC");
    
} catch (Exception $e) {
    $movies = [];
    $genres = [];
    $years = [];
    $total_count = 0;
    $total_pages = 0;
    error_log("Movies list error: " . $e->getMessage());
}

// Obsługa usuwania filmu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $movie_id = (int)($_POST['movie_id'] ?? 0);
        
        if ($movie_id > 0) {
            try {
                // Usuń powiązane dane użytkowników
                executeQuery("DELETE FROM user_movies WHERE movie_id = ?", [$movie_id]);
                
                // Usuń recenzje
                executeQuery("DELETE FROM movie_reviews WHERE movie_id = ?", [$movie_id]);
                
                // Usuń film
                executeQuery("DELETE FROM movies WHERE id = ?", [$movie_id]);
                
                header("Location: movies-list.php?deleted=1");
                exit();
                
            } catch (Exception $e) {
                $error_message = "Błąd podczas usuwania filmu: " . $e->getMessage();
            }
        }
    }
}

$page_title = 'Lista Filmów - Admin Panel';
require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="admin-header">
        <h1><i class="fas fa-film"></i> Lista Filmów</h1>
        <div class="admin-nav">
            <a href="admin.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Powrót do panelu
            </a>
            <a href="movies-add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Dodaj film
            </a>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">
            Film został pomyślnie usunięty.
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Filtry i wyszukiwanie -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="search">Szukaj filmu</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Tytuł lub reżyser..."
                           class="form-control">
                </div>
                
                <div class="filter-group">
                    <label for="genre">Gatunek</label>
                    <select id="genre" name="genre" class="form-control">
                        <option value="">Wszystkie gatunki</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo htmlspecialchars($genre['genre']); ?>" 
                                    <?php echo $genre_filter === $genre['genre'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($genre['genre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="year">Rok</label>
                    <select id="year" name="year" class="form-control">
                        <option value="">Wszystkie lata</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year['year']; ?>" 
                                    <?php echo $year_filter === $year['year'] ? 'selected' : ''; ?>>
                                <?php echo $year['year']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtruj
                        </button>
                        <a href="movies-list.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Wyczyść
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Statystyki -->
    <div class="stats-bar">
        <div class="stat-item">
            <span class="stat-number"><?php echo number_format($total_count); ?></span>
            <span class="stat-label">filmów</span>
        </div>
    </div>

    <!-- Lista filmów -->
    <?php if (empty($movies)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-film"></i>
            </div>
            <h3>Brak filmów</h3>
            <p>Nie znaleziono filmów spełniających kryteria wyszukiwania.</p>
            <a href="movies-add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Dodaj pierwszy film
            </a>
        </div>
    <?php else: ?>
        <div class="movies-table-container">
            <table class="movies-table">
                <thead>
                    <tr>
                        <th>Plakat</th>
                        <th>Tytuł</th>
                        <th>Gatunek</th>
                        <th>Rok</th>
                        <th>Czas</th>
                        <th>Reżyser</th>
                        <th>Użytkownicy</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movies as $movie): ?>
                        <tr>
                            <td class="poster-cell">
                                <?php if ($movie['poster_url']): ?>
                                    <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                                         class="movie-poster-thumb">
                                <?php else: ?>
                                    <div class="no-poster">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="title-cell">
                                <div class="movie-title">
                                    <?php echo htmlspecialchars($movie['title']); ?>
                                </div>
                                <div class="movie-id">ID: <?php echo $movie['id']; ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($movie['genre'] ?: '-'); ?></td>
                            <td><?php echo $movie['year']; ?></td>
                            <td><?php echo $movie['runtime'] ? $movie['runtime'] . ' min' : '-'; ?></td>
                            <td><?php echo htmlspecialchars($movie['director'] ?: '-'); ?></td>
                            <td class="users-count"><?php echo $movie['user_count']; ?></td>
                            <td class="actions-cell">
                                <div class="action-buttons">
                                    <a href="movies-edit.php?id=<?php echo $movie['id']; ?>" 
                                       class="btn btn-sm btn-outline" 
                                       title="Edytuj">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="deleteMovie(<?php echo $movie['id']; ?>, '<?php echo htmlspecialchars($movie['title'], ENT_QUOTES); ?>')"
                                            title="Usuń">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginacja -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&genre=<?php echo urlencode($genre_filter); ?>&year=<?php echo $year_filter; ?>" 
                           class="page-btn">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&genre=<?php echo urlencode($genre_filter); ?>&year=<?php echo $year_filter; ?>" 
                           class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&genre=<?php echo urlencode($genre_filter); ?>&year=<?php echo $year_filter; ?>" 
                           class="page-btn">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="pagination-info">
                    Strona <?php echo $page; ?> z <?php echo $total_pages; ?> 
                    (<?php echo number_format($total_count); ?> filmów)
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal do usuwania -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Potwierdź usunięcie</h3>
        <p>Czy na pewno chcesz usunąć film "<span id="movieTitle"></span>"?</p>
        <p class="warning">Ta operacja jest nieodwracalna i usunie wszystkie powiązane dane użytkowników.</p>
        
        <form id="deleteForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="movie_id" id="deleteMovieId">
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    Anuluj
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Usuń film
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #333;
}

.admin-header h1 {
    color: #ff9500;
    font-size: 2rem;
    margin: 0;
}

.admin-nav {
    display: flex;
    gap: 1rem;
}

.filters-section {
    background: #2a2a2a;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid #333;
}

.filters-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.filter-group label {
    color: #fff;
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: block;
    font-size: 0.9rem;
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
}

.stats-bar {
    background: #1a1a1a;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
    display: flex;
    gap: 2rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #ff9500;
}

.stat-label {
    font-size: 0.9rem;
    color: #aaa;
}

.movies-table-container {
    background: #2a2a2a;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #333;
}

.movies-table {
    width: 100%;
    border-collapse: collapse;
}

.movies-table th {
    background: #1a1a1a;
    color: #fff;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    border-bottom: 1px solid #333;
}

.movies-table td {
    padding: 1rem;
    border-bottom: 1px solid #333;
    color: #ccc;
}

.movies-table tr:last-child td {
    border-bottom: none;
}

.movies-table tr:hover {
    background: rgba(255, 149, 0, 0.05);
}

.poster-cell {
    width: 60px;
    text-align: center;
}

.movie-poster-thumb {
    width: 40px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.no-poster {
    width: 40px;
    height: 60px;
    background: #333;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
}

.title-cell {
    min-width: 200px;
}

.movie-title {
    font-weight: 600;
    color: #fff;
    margin-bottom: 0.25rem;
}

.movie-id {
    font-size: 0.8rem;
    color: #666;
}

.users-count {
    text-align: center;
    font-weight: 600;
    color: #ff9500;
}

.actions-cell {
    width: 120px;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
}

.pagination {
    display: flex;
    gap: 0.5rem;
}

.page-btn {
    padding: 0.5rem 0.75rem;
    background: #2a2a2a;
    color: #ccc;
    text-decoration: none;
    border-radius: 6px;
    border: 1px solid #333;
    transition: all 0.3s ease;
}

.page-btn:hover,
.page-btn.active {
    background: #ff9500;
    color: #fff;
    border-color: #ff9500;
}

.pagination-info {
    color: #aaa;
    font-size: 0.9rem;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #2a2a2a;
    border-radius: 12px;
    padding: 2rem;
    max-width: 400px;
    width: 90%;
    border: 1px solid #333;
}

.modal-content h3 {
    color: #fff;
    margin-bottom: 1rem;
}

.modal-content p {
    color: #ccc;
    margin-bottom: 1rem;
}

.warning {
    color: #ff6b6b !important;
    font-size: 0.9rem;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #666;
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

.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8rem;
}

.btn-primary {
    background: #ff9500;
    color: #fff;
}

.btn-primary:hover {
    background: #e68900;
}

.btn-secondary {
    background: #6c757d;
    color: #fff;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-outline {
    background: transparent;
    color: #ff9500;
    border: 1px solid #ff9500;
}

.btn-outline:hover {
    background: #ff9500;
    color: #fff;
}

.btn-danger {
    background: #dc3545;
    color: #fff;
}

.btn-danger:hover {
    background: #c82333;
}

.form-control {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 6px;
    padding: 0.5rem;
    color: #fff;
    font-size: 0.9rem;
}

.form-control:focus {
    outline: none;
    border-color: #ff9500;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.alert-success {
    background: rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.3);
    color: #d4edda;
}

.alert-danger {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #f8d7da;
}

/* Layout fixes */
.main-content {
    max-width: 100%;
    overflow-x: auto;
    box-sizing: border-box;
}

.admin-content {
    min-width: 0;
}

/* Responsywne tabele */
.table-responsive {
    max-width: 100%;
    box-sizing: border-box;
}

@media (max-width: 768px) {
    .admin-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .movies-table-container {
        overflow-x: auto;
    }
    
    .pagination-container {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .main-content {
        padding: 1rem;
    }
}
</style>

<script>
function deleteMovie(movieId, movieTitle) {
    document.getElementById('movieTitle').textContent = movieTitle;
    document.getElementById('deleteMovieId').value = movieId;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Zamknij modal po kliknięciu poza nim
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
