<?php
/**
 * Zarządzanie sesjami dla TV Time Clone
 * Autor: System
 * Data: 2025-06-29
 */

// Konfiguracja bezpieczeństwa sesji (przed rozpoczęciem sesji)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Ustaw na 1 dla HTTPS
    session_start();
}

/**
 * Sprawdza czy użytkownik jest zalogowany
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Sprawdza czy użytkownik jest zalogowany, jeśli nie - przekierowuje na logowanie
 * @param string $redirect_url URL do przekierowania po zalogowaniu
 */
function requireLogin($redirect_url = '') {
    if (!isLoggedIn()) {
        $redirect = !empty($redirect_url) ? '?redirect=' . urlencode($redirect_url) : '';
        header('Location: login.php' . $redirect);
        exit();
    }
}

/**
 * Loguje użytkownika do systemu
 * @param int $user_id
 * @param string $username
 * @param string $email
 * @param string $role
 */
function loginUser($user_id, $username, $email, $role = 'user') {
    // Regeneracja ID sesji dla bezpieczeństwa
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['login_time'] = time();
}

/**
 * Wylogowuje użytkownika z systemu
 */
function logoutUser() {
    // Czyszczenie wszystkich zmiennych sesji
    $_SESSION = array();
    
    // Usunięcie cookie sesji
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Zniszczenie sesji
    session_destroy();
}

/**
 * Pobiera ID aktualnie zalogowanego użytkownika
 * @return int|null
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Pobiera nazwę użytkownika aktualnie zalogowanego użytkownika
 * @return string|null
 */
function getCurrentUsername() {
    return isLoggedIn() ? $_SESSION['username'] : null;
}

/**
 * Pobiera email aktualnie zalogowanego użytkownika
 * @return string|null
 */
function getCurrentUserEmail() {
    return isLoggedIn() ? $_SESSION['email'] : null;
}

/**
 * Pobiera rolę aktualnie zalogowanego użytkownika
 * @return string|null
 */
function getCurrentUserRole() {
    return isLoggedIn() ? $_SESSION['role'] : null;
}

/**
 * Sprawdza czy użytkownik jest adminem
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && getCurrentUserRole() === 'admin';
}

/**
 * Generuje token CSRF
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Weryfikuje token CSRF
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Wyświetla pole ukryte z tokenem CSRF
 */
function csrfTokenField() {
    echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Ustawia komunikat flash
 * @param string $type Typ komunikatu (success, error, warning, info)
 * @param string $message Treść komunikatu
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Pobiera i usuwa komunikaty flash
 * @return array
 */
function getFlashMessages() {
    $messages = isset($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Sprawdza czy istnieją komunikaty flash
 * @return bool
 */
function hasFlashMessages() {
    return isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages']);
}
?>
