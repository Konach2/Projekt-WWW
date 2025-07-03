<?php
/**
 * AJAX endpoint do wyszukiwania seriali na żywo
 */

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Sprawdź metodę żądania
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Nieprawidłowa metoda żądania.';
        echo json_encode($response);
        exit();
    }

    // Weryfikuj CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $response['message'] = 'Nieprawidłowy token bezpieczeństwa.';
        echo json_encode($response);
        exit();
    }

    // Pobierz zapytanie wyszukiwania
    $query = trim($_POST['query'] ?? '');
    
    if (strlen($query) < 2) {
        $response['message'] = 'Zapytanie musi mieć co najmniej 2 znaki.';
        echo json_encode($response);
        exit();
    }

    // Ograniczone wyszukiwanie - maksymalnie 8 wyników
    $search_param = '%' . $query . '%';
    
    $sql = "SELECT s.id, s.title, s.year, s.genre, s.poster_url,
                   AVG(COALESCE(us.rating, 0)) as avg_rating,
                   COUNT(r.id) as review_count
            FROM shows s
            LEFT JOIN user_shows us ON s.id = us.show_id AND us.rating > 0
            LEFT JOIN reviews r ON s.id = r.show_id
            WHERE (s.title LIKE ? OR s.description LIKE ? OR s.genre LIKE ?)
            GROUP BY s.id, s.title, s.year, s.genre, s.poster_url
            ORDER BY 
                CASE 
                    WHEN s.title LIKE ? THEN 1
                    WHEN s.title LIKE ? THEN 2
                    ELSE 3
                END,
                s.title ASC
            LIMIT 8";
    
    $params = [
        $search_param,  // title LIKE
        $search_param,  // description LIKE
        $search_param,  // genre LIKE
        $query . '%',   // title exact start match (priority 1)
        '%' . $query . '%', // title contains (priority 2)
    ];
    
    $shows = fetchAll($sql, $params);
    
    // Formatuj wyniki
    $formatted_shows = [];
    foreach ($shows as $show) {
        $formatted_shows[] = [
            'id' => (int)$show['id'],
            'title' => $show['title'],
            'year' => (int)$show['year'],
            'genre' => $show['genre'],
            'poster_url' => getImageUrl($show['poster_url']),
            'avg_rating' => round((float)$show['avg_rating'], 1),
            'review_count' => (int)$show['review_count'],
            'url' => 'show-details.php?id=' . $show['id']
        ];
    }
    
    $response['success'] = true;
    $response['data'] = $formatted_shows;
    $response['total'] = count($formatted_shows);
    
    if (empty($formatted_shows)) {
        $response['message'] = 'Nie znaleziono seriali dla zapytania: ' . htmlspecialchars($query);
    }

} catch (Exception $e) {
    error_log('Error in search-shows.php: ' . $e->getMessage());
    $response['message'] = 'Wystąpił błąd podczas wyszukiwania.';
}

echo json_encode($response);
?>
