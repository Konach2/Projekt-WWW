<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany']);
    exit();
}

$user_id = getCurrentUserId();

try {
    // Pobierz aktualne statystyki użytkownika
    $stats = fetchOne("
        SELECT 
            COUNT(*) as total_movies,
            COUNT(CASE WHEN um.status = 'watched' THEN 1 END) as watched_count,
            COUNT(CASE WHEN um.status IN ('want_to_watch', 'watchlist') THEN 1 END) as watchlist_count,
            COUNT(CASE WHEN um.favorite = 1 THEN 1 END) as favorite_count,
            AVG(CASE WHEN um.rating > 0 THEN um.rating END) as avg_rating
        FROM user_movies um
        WHERE um.user_id = ?
    ", [$user_id]);
    
    // Dodaj total_runtime jako 0 (można dodać później gdy kolumna runtime zostanie dodana)
    $stats['total_runtime'] = 0;
    
    echo json_encode([
        'success' => true, 
        'stats' => [
            'watched_count' => (int)$stats['watched_count'],
            'watchlist_count' => (int)$stats['watchlist_count'],
            'favorite_count' => (int)$stats['favorite_count'],
            'avg_rating' => $stats['avg_rating'] ? round($stats['avg_rating'], 1) : 0,
            'total_runtime' => (int)$stats['total_runtime']
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get-movie-stats.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Błąd podczas pobierania statystyk']);
}
?>
