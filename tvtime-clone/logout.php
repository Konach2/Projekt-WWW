<?php
/**
 * Wylogowanie użytkownika TV Time Clone
 * Autor: System
 * Data: 2025-06-29
 */

require_once 'includes/session.php';

// Sprawdź czy użytkownik jest zalogowany
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Pobierz nazwę użytkownika przed wylogowaniem
$username = getCurrentUsername();

// Wyloguj użytkownika
logoutUser();

// Ustaw komunikat pożegnalny
setFlashMessage('info', "Do zobaczenia, {$username}! Zostałeś pomyślnie wylogowany.");

// Przekieruj na stronę główną
header('Location: index.php');
exit();
?>
