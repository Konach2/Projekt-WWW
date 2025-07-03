<?php
/**
 * Lista seriali - Admin Panel
 */

// Parametry paginacji i filtrowania
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;
$search = trim($_GET['search'] ?? '');

try {
    // Budowanie zapytania
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(title LIKE :search OR genre LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Liczba wszystkich seriali
    $total_shows = fetchCount("SELECT COUNT(*) FROM shows {$where_clause}", $params);
    $total_pages = ceil($total_shows / $per_page);
    
    // Pobieranie seriali
    $shows_sql = "
        SELECT s.*, 
               COUNT(DISTINCT r.id) as review_count,
               COUNT(DISTINCT us.id) as user_count,
               COALESCE(AVG(r.rating), 0) as avg_rating
        FROM shows s
        LEFT JOIN reviews r ON s.id = r.show_id
        LEFT JOIN user_shows us ON s.id = us.show_id
        {$where_clause}
        GROUP BY s.id
        ORDER BY s.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $shows = fetchAll($shows_sql, $params);
    
} catch (Exception $e) {
    $shows = [];
    $total_pages = 1;
    $total_shows = 0;
}

// Obsługa usuwania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $delete_id = (int)($_POST['show_id'] ?? 0);
        
        try {
            // Pobierz dane serialu przed usunięciem
            $show_to_delete = fetchOne("SELECT title, poster_url FROM shows WHERE id = :id", [':id' => $delete_id]);
            
            if ($show_to_delete) {
                // Usuń plik obrazu
                if (!empty($show_to_delete['poster_url'])) {
                    deleteShowImage($show_to_delete['poster_url']);
                }
                
                // Usuń serial z bazy danych
                executeQuery("DELETE FROM shows WHERE id = :id", [':id' => $delete_id]);
                
                logAdminAction('DELETE_SHOW', "Usunięto serial: {$show_to_delete['title']} (ID: $delete_id)");
                setFlashMessage('success', 'Serial został usunięty pomyślnie.');
            } else {
                setFlashMessage('error', 'Serial nie został znaleziony.');
            }
            
        } catch (Exception $e) {
            error_log("Błąd usuwania serialu: " . $e->getMessage());
            setFlashMessage('error', 'Wystąpił błąd podczas usuwania serialu.');
        }
        
        header('Location: admin.php?action=list');
        exit();
    }
}
?>

<div class="admin-header">
    <h1><i class="fas fa-list"></i> Lista seriali</h1>
    <p>Zarządzaj wszystkimi serialami w systemie</p>
</div>

<!-- Wyszukiwanie i akcje -->
<div class="admin-actions">
    <div class="search-box">
        <form method="GET" action="admin.php">
            <input type="hidden" name="action" value="list">
            <div class="input-group">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Szukaj seriali..." 
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="form-control"
                >
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
    
    <a href="admin.php?action=add" class="btn btn-success">
        <i class="fas fa-plus"></i> Dodaj nowy serial
    </a>
</div>

<!-- Lista seriali -->
<?php if (!empty($shows)): ?>
    <div class="results-info">
        <p>Znaleziono <strong><?php echo number_format($total_shows); ?></strong> seriali</p>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Plakat</th>
                    <th>Tytuł</th>
                    <th>Gatunek</th>
                    <th>Rok</th>
                    <th>Ocena</th>
                    <th>Recenzje</th>
                    <th>Użytkownicy</th>
                    <th>Dodano</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shows as $show): ?>
                    <tr>
                        <td>
                            <div class="show-thumbnail">
                                <img 
                                    src="<?php echo getImageUrl($show['poster_url']); ?>" 
                                    alt="<?php echo htmlspecialchars($show['title']); ?>"
                                    style="width: 50px; height: 75px; object-fit: cover; border-radius: 4px;"
                                >
                            </div>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($show['title']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($show['genre']); ?></td>
                        <td><?php echo $show['year']; ?></td>
                        <td>
                            <?php if ($show['avg_rating'] > 0): ?>
                                <span class="rating-badge">
                                    <i class="fas fa-star"></i>
                                    <?php echo number_format($show['avg_rating'], 1); ?>
                                </span>
                            <?php else: ?>
                                <span class="no-rating">Brak ocen</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $show['review_count']; ?></td>
                        <td><?php echo $show['user_count']; ?></td>
                        <td><?php echo date('d.m.Y', strtotime($show['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="show-details.php?id=<?php echo $show['id']; ?>" 
                                   class="btn btn-sm btn-secondary" 
                                   title="Zobacz">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="admin.php?action=edit&id=<?php echo $show['id']; ?>" 
                                   class="btn btn-sm btn-primary" 
                                   title="Edytuj">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-sm btn-danger" 
                                        onclick="confirmDelete(<?php echo $show['id']; ?>, '<?php echo htmlspecialchars($show['title'], ENT_QUOTES); ?>')"
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
        <nav class="pagination">
            <?php
            $url_params = [];
            if (!empty($search)) $url_params['search'] = $search;
            $url_params['action'] = 'list';
            
            $base_url = 'admin.php?' . http_build_query($url_params);
            $separator = '&';
            
            // Poprzednia strona
            if ($page > 1): ?>
                <a href="<?php echo $base_url . $separator . 'page=' . ($page - 1); ?>" class="pagination-btn">
                    <i class="fas fa-chevron-left"></i> Poprzednia
                </a>
            <?php endif; ?>
            
            <?php
            // Strony
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="pagination-current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="<?php echo $base_url . $separator . 'page=' . $i; ?>" class="pagination-btn"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php
            // Następna strona
            if ($page < $total_pages): ?>
                <a href="<?php echo $base_url . $separator . 'page=' . ($page + 1); ?>" class="pagination-btn">
                    Następna <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

<?php else: ?>
    <div class="no-results">
        <i class="fas fa-tv" style="font-size: 3rem; color: #666; margin-bottom: 1rem;"></i>
        <h3>Brak seriali</h3>
        <p>
            <?php if (!empty($search)): ?>
                Nie znaleziono seriali pasujących do wyszukiwania.
                <br><a href="admin.php?action=list">Pokaż wszystkie seriale</a>
            <?php else: ?>
                Nie ma jeszcze żadnych seriali w systemie.
                <br><a href="admin.php?action=add">Dodaj pierwszy serial</a>
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>

<!-- Modal usuwania -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-exclamation-triangle"></i> Potwierdź usunięcie</h3>
        <p>Czy na pewno chcesz usunąć serial <strong id="deleteShowTitle"></strong>?</p>
        <p class="warning">Ta akcja jest nieodwracalna!</p>
        
        <form method="POST" id="deleteForm">
            <?php csrfTokenField(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="show_id" id="deleteShowId">
            
            <div class="modal-actions">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Anuluj</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Usuń
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Style specyficzne dla listy */
.admin-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

.search-box {
    flex: 1;
    max-width: 400px;
}

.input-group {
    display: flex;
}

.input-group input {
    border-radius: 6px 0 0 6px;
}

.input-group button {
    border-radius: 0 6px 6px 0;
    border-left: none;
}

.results-info {
    margin-bottom: 1rem;
    color: #ccc;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.rating-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: #ff9500;
    color: #000;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.no-rating {
    color: #666;
    font-size: 0.8rem;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination-btn {
    padding: 0.5rem 1rem;
    background: #2a2a2a;
    color: #ccc;
    text-decoration: none;
    border-radius: 4px;
    border: 1px solid #3a3a3a;
    transition: all 0.3s ease;
}

.pagination-btn:hover {
    background: #3a3a3a;
    color: #fff;
}

.pagination-current {
    padding: 0.5rem 1rem;
    background: #ff9500;
    color: #000;
    border-radius: 4px;
    font-weight: 600;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 1000;
}

.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #2a2a2a;
    padding: 2rem;
    border-radius: 8px;
    max-width: 400px;
    width: 90%;
    border: 1px solid #3a3a3a;
}

.modal-content h3 {
    color: #e74c3c;
    margin-bottom: 1rem;
}

.modal-content .warning {
    color: #f39c12;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .admin-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .action-buttons {
        flex-wrap: wrap;
    }
}
</style>

<script>
function confirmDelete(showId, showTitle) {
    document.getElementById('deleteShowId').value = showId;
    document.getElementById('deleteShowTitle').textContent = showTitle;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Zamknij modal po kliknięciu poza nim
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        closeDeleteModal();
    }
}
</script>
