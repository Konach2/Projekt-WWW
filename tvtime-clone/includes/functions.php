<?php
/**
 * Funkcje pomocnicze dla TV Time Clone
 * Autor: System
 * Data: 2025-07-02
 */

/**
 * Hashuje hasło używając bcrypt
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Weryfikuje hasło
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Wymaga uprawnień admina
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Pobiera dozwolone rozszerzenia plików obrazów
 * @return array
 */
function getAllowedImageExtensions() {
    return ['jpg', 'jpeg', 'png', 'webp', 'gif'];
}

/**
 * Generuje bezpieczną nazwę pliku
 * @param string $original_name
 * @return string
 */
function generateSafeFilename($original_name) {
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $safe_name = preg_replace('/[^a-zA-Z0-9]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
    $safe_name = substr($safe_name, 0, 50); // Ogranicz długość
    return $safe_name . '_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
}

/**
 * Uploaduje obraz dla serialu
 * @param array $file $_FILES element
 * @param string $upload_dir
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function uploadShowImage($file, $upload_dir = 'assets/uploads/shows/') {
    $result = ['success' => false, 'filename' => '', 'error' => ''];
    
    // Sprawdź czy plik został przesłany
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'Błąd podczas przesyłania pliku.';
        return $result;
    }
    
    // Sprawdź rozmiar pliku (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $result['error'] = 'Plik jest zbyt duży. Maksymalny rozmiar to 5MB.';
        return $result;
    }
    
    // Sprawdź rozszerzenie
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, getAllowedImageExtensions())) {
        $result['error'] = 'Niedozwolony format pliku. Dozwolone: ' . implode(', ', getAllowedImageExtensions());
        return $result;
    }
    
    // Sprawdź czy to rzeczywiście obraz
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        $result['error'] = 'Przesłany plik nie jest obrazem.';
        return $result;
    }
    
    // Utwórz katalog jeśli nie istnieje
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generuj bezpieczną nazwę
    $filename = generateSafeFilename($file['name']);
    $filepath = $upload_dir . $filename;
    
    // Przenieś plik
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $result['success'] = true;
        $result['filename'] = $filename;
    } else {
        $result['error'] = 'Błąd podczas zapisywania pliku.';
    }
    
    return $result;
}

/**
 * Usuwa plik obrazu
 * @param string $filename
 * @param string $upload_dir
 * @return bool
 */
function deleteShowImage($filename, $upload_dir = 'assets/uploads/shows/') {
    if (empty($filename)) return true;
    
    $filepath = $upload_dir . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return true;
}

/**
 * Sprawdza czy plik obrazu istnieje
 * @param string $filename
 * @param string $upload_dir
 * @return bool
 */
function imageExists($filename, $upload_dir = 'assets/uploads/shows/') {
    if (empty($filename)) return false;
    
    // Jeśli filename już zawiera pełną ścieżkę (zaczyna się od 'assets/')
    if (strpos($filename, 'assets/') === 0) {
        return file_exists($filename);
    }
    
    // Standardowa logika - dodaj upload_dir do filename
    return file_exists($upload_dir . $filename);
}

/**
 * Pobiera URL obrazu z fallbackiem
 * @param string $filename
 * @param string $upload_dir
 * @return string
 */
function getImageUrl($filename, $upload_dir = 'assets/uploads/shows/') {
    if (empty($filename)) {
        return 'assets/images/placeholder-show.jpg';
    }
    
    // Jeśli filename już zawiera pełną ścieżkę (zaczyna się od 'assets/')
    if (strpos($filename, 'assets/') === 0) {
        if (file_exists($filename)) {
            return $filename;
        }
        return 'assets/images/placeholder-show.jpg';
    }
    
    // Standardowa logika - dodaj upload_dir do filename
    if (imageExists($filename, $upload_dir)) {
        return $upload_dir . $filename;
    }
    
    return 'assets/images/placeholder-show.jpg';
}

/**
 * Pobiera URL obrazu filmu z fallbackiem
 * @param string $filename
 * @param string $upload_dir
 * @return string
 */
function getMovieImageUrl($filename, $upload_dir = 'assets/uploads/movies/') {
    if (empty($filename)) {
        return 'assets/images/placeholder-show.jpg';
    }
    
    // Jeśli filename już zawiera pełną ścieżkę (zaczyna się od 'assets/')
    if (strpos($filename, 'assets/') === 0) {
        if (file_exists($filename)) {
            return $filename;
        }
        return 'assets/images/placeholder-show.jpg';
    }
    
    // Standardowa logika - dodaj upload_dir do filename
    if (file_exists($upload_dir . $filename)) {
        return $upload_dir . $filename;
    }
    
    return 'assets/images/placeholder-show.jpg';
}

/**
 * Waliduje dane serialu
 * @param array $data
 * @return array ['valid' => bool, 'errors' => array]
 */
function validateShowData($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors[] = 'Tytuł jest wymagany.';
    } elseif (strlen($data['title']) > 255) {
        $errors[] = 'Tytuł nie może być dłuższy niż 255 znaków.';
    }
    
    if (empty($data['description'])) {
        $errors[] = 'Opis jest wymagany.';
    }
    
    if (empty($data['genre'])) {
        $errors[] = 'Gatunek jest wymagany.';
    } elseif (strlen($data['genre']) > 100) {
        $errors[] = 'Gatunek nie może być dłuższy niż 100 znaków.';
    }
    
    if (empty($data['year'])) {
        $errors[] = 'Rok jest wymagany.';
    } elseif (!is_numeric($data['year']) || $data['year'] < 1900 || $data['year'] > date('Y') + 5) {
        $errors[] = 'Rok musi być liczbą między 1900 a ' . (date('Y') + 5) . '.';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Pobiera dostępne gatunki
 * @return array
 */
function getAvailableGenres() {
    return [
        'Action' => 'Akcja',
        'Adventure' => 'Przygodowy',
        'Animation' => 'Animacja',
        'Comedy' => 'Komedia',
        'Crime' => 'Kryminał',
        'Documentary' => 'Dokumentalny',
        'Drama' => 'Dramat',
        'Family' => 'Familijny',
        'Fantasy' => 'Fantasy',
        'History' => 'Historyczny',
        'Horror' => 'Horror',
        'Music' => 'Muzyczny',
        'Mystery' => 'Tajemnica',
        'Romance' => 'Romans',
        'Sci-Fi' => 'Sci-Fi',
        'Thriller' => 'Thriller',
        'War' => 'Wojenny',
        'Western' => 'Western'
    ];
}

/**
 * Loguje akcje admina
 * @param string $action
 * @param string $details
 */
function logAdminAction($action, $details = '') {
    if (!isAdmin()) return;
    
    $log_entry = date('Y-m-d H:i:s') . " - User: " . getCurrentUsername() . " - Action: $action";
    if (!empty($details)) {
        $log_entry .= " - Details: $details";
    }
    $log_entry .= "\n";
    
    error_log($log_entry, 3, 'logs/admin.log');
}

/**
 * Czyści przeterminowane sesje (można wywołać przez cron)
 */
function cleanExpiredSessions() {
    // Usuń sesje starsze niż 24 godziny
    $expired_time = time() - (24 * 60 * 60);
    // Tu można dodać logikę usuwania z bazy sesji jeśli są przechowywane w DB
}

/**
 * Formatuje rozmiar pliku
 * @param int $bytes
 * @return string
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    return number_format($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
}

/**
 * Sprawdza czy string zawiera tylko dozwolone znaki dla filename
 * @param string $filename
 * @return bool
 */
function isSafeFilename($filename) {
    return preg_match('/^[a-zA-Z0-9._-]+$/', $filename);
}

/**
 * Zwraca czas w formacie "x czasu temu"
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'przed chwilą';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' ' . ($minutes == 1 ? 'minutę' : ($minutes < 5 ? 'minuty' : 'minut')) . ' temu';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' ' . ($hours == 1 ? 'godzinę' : ($hours < 5 ? 'godziny' : 'godzin')) . ' temu';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' ' . ($days == 1 ? 'dzień' : 'dni') . ' temu';
    } elseif ($time < 31536000) {
        $months = floor($time / 2592000);
        return $months . ' ' . ($months == 1 ? 'miesiąc' : ($months < 5 ? 'miesiące' : 'miesięcy')) . ' temu';
    } else {
        $years = floor($time / 31536000);
        return $years . ' ' . ($years == 1 ? 'rok' : ($years < 5 ? 'lata' : 'lat')) . ' temu';
    }
}
?>
