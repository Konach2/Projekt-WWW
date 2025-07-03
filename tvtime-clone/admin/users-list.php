<?php
/**
 * Lista użytkowników - Admin Panel
 */

// Parametry paginacji i filtrowania
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;
$search = trim($_GET['search'] ?? '');

try {
    // Budowanie zapytania
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(username LIKE :search OR email LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Liczba wszystkich użytkowników
    $total_users = fetchCount("SELECT COUNT(*) FROM users {$where_clause}", $params);
    $total_pages = ceil($total_users / $per_page);
    
    // Pobieranie użytkowników z ich statystykami
    $users_sql = "
        SELECT u.*, 
               COUNT(DISTINCT us.id) as shows_count,
               COUNT(DISTINCT r.id) as reviews_count,
               AVG(r.rating) as avg_rating_given
        FROM users u
        LEFT JOIN user_shows us ON u.id = us.user_id
        LEFT JOIN reviews r ON u.id = r.user_id
        {$where_clause}
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    
    $users = fetchAll($users_sql, $params);
    
} catch (Exception $e) {
    $users = [];
    $total_pages = 1;
    $total_users = 0;
}
?>

<div class="admin-header">
    <h1><i class="fas fa-users"></i> Zarządzanie użytkownikami</h1>
    <p>Przegląd wszystkich użytkowników systemu</p>
</div>

<!-- Wyszukiwanie -->
<div class="admin-actions">
    <div class="search-box">
        <form method="GET" action="admin.php">
            <input type="hidden" name="action" value="users">
            <div class="input-group">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Szukaj użytkowników..." 
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="form-control"
                >
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
    
    <div class="user-stats">
        <span class="stat-item">
            <i class="fas fa-users"></i>
            Łącznie: <strong><?php echo number_format($total_users); ?></strong>
        </span>
    </div>
</div>

<!-- Lista użytkowników -->
<?php if (!empty($users)): ?>
    <div class="results-info">
        <p>Znaleziono <strong><?php echo number_format($total_users); ?></strong> użytkowników</p>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nazwa użytkownika</th>
                    <th>Email</th>
                    <th>Rola</th>
                    <th>Seriale</th>
                    <th>Recenzje</th>
                    <th>Śr. ocena</th>
                    <th>Dołączył</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <div class="user-info">
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="admin-badge">
                                        <i class="fas fa-crown"></i> Admin
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo $user['role'] === 'admin' ? 'Administrator' : 'Użytkownik'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="count-badge">
                                <i class="fas fa-tv"></i>
                                <?php echo $user['shows_count']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="count-badge">
                                <i class="fas fa-star"></i>
                                <?php echo $user['reviews_count']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['avg_rating_given'] > 0): ?>
                                <span class="rating-badge">
                                    <?php echo number_format($user['avg_rating_given'], 1); ?>
                                </span>
                            <?php else: ?>
                                <span class="no-rating">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="date-info">
                                <div><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></div>
                                <small><?php echo date('H:i', strtotime($user['created_at'])); ?></small>
                            </div>
                        </td>
                        <td>
                            <?php
                            $last_activity = time() - strtotime($user['created_at']);
                            $days_ago = floor($last_activity / (24 * 60 * 60));
                            ?>
                            <span class="activity-badge <?php echo $days_ago < 7 ? 'recent' : ($days_ago < 30 ? 'moderate' : 'old'); ?>">
                                <?php
                                if ($days_ago == 0) {
                                    echo 'Dziś';
                                } elseif ($days_ago == 1) {
                                    echo '1 dzień temu';
                                } elseif ($days_ago < 7) {
                                    echo $days_ago . ' dni temu';
                                } elseif ($days_ago < 30) {
                                    echo floor($days_ago / 7) . ' tyg. temu';
                                } else {
                                    echo floor($days_ago / 30) . ' mies. temu';
                                }
                                ?>
                            </span>
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
            $url_params['action'] = 'users';
            
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
        <i class="fas fa-users" style="font-size: 3rem; color: #666; margin-bottom: 1rem;"></i>
        <h3>Brak użytkowników</h3>
        <p>
            <?php if (!empty($search)): ?>
                Nie znaleziono użytkowników pasujących do wyszukiwania.
                <br><a href="admin.php?action=users">Pokaż wszystkich użytkowników</a>
            <?php else: ?>
                Nie ma jeszcze żadnych użytkowników w systemie.
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>

<style>
/* Style specyficzne dla listy użytkowników */
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

.user-stats {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.stat-item {
    color: #ccc;
    font-size: 0.9rem;
}

.stat-item i {
    color: #ff9500;
    margin-right: 0.25rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.admin-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: #f39c12;
    color: #000;
    padding: 0.15rem 0.4rem;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: 600;
}

.role-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.role-admin {
    background: #e74c3c;
    color: #fff;
}

.role-user {
    background: #27ae60;
    color: #fff;
}

.count-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    color: #ccc;
    font-size: 0.9rem;
}

.count-badge i {
    color: #ff9500;
}

.rating-badge {
    display: inline-flex;
    align-items: center;
    background: #ff9500;
    color: #000;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.no-rating {
    color: #666;
    font-size: 0.9rem;
}

.date-info {
    text-align: center;
}

.date-info small {
    color: #888;
    font-size: 0.75rem;
}

.activity-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.activity-badge.recent {
    background: #27ae60;
    color: #fff;
}

.activity-badge.moderate {
    background: #f39c12;
    color: #000;
}

.activity-badge.old {
    background: #666;
    color: #ccc;
}

/* Responsive design */
@media (max-width: 1024px) {
    .admin-table th:nth-child(6),
    .admin-table td:nth-child(6),
    .admin-table th:nth-child(7),
    .admin-table td:nth-child(7) {
        display: none;
    }
}

@media (max-width: 768px) {
    .admin-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .admin-table th:nth-child(3),
    .admin-table td:nth-child(3),
    .admin-table th:nth-child(5),
    .admin-table td:nth-child(5) {
        display: none;
    }
    
    .user-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}
</style>
