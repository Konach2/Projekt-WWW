<?php
/**
 * Strona logowania TV Time Clone
 * Autor: System
 * Data: 2025-06-29
 */

require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Przekieruj zalogowanych użytkowników
if (isLoggedIn()) {
    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
    header('Location: ' . $redirect);
    exit();
}

// Ustawienia strony
$page_title = 'Logowanie - TV Time Clone';
$page_description = 'Zaloguj się do swojego konta TV Time Clone';

$error = '';
$username = '';

// Obsługa formularza logowania
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Weryfikacja tokenu CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Nieprawidłowy token bezpieczeństwa. Spróbuj ponownie.';
    } else {
        // Walidacja danych wejściowych
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username)) {
            $error = 'Podaj nazwę użytkownika lub email.';
        } elseif (empty($password)) {
            $error = 'Podaj hasło.';
        } elseif (strlen($username) < 3) {
            $error = 'Nazwa użytkownika musi mieć co najmniej 3 znaki.';
        } elseif (strlen($password) < 6) {
            $error = 'Hasło musi mieć co najmniej 6 znaków.';
        } else {
            try {
                // Sprawdź czy logowanie po nazwie użytkownika czy email
                $is_email = filter_var($username, FILTER_VALIDATE_EMAIL);
                
                if ($is_email) {
                    $sql = "SELECT id, username, email, password_hash, role FROM users WHERE email = :login";
                } else {
                    $sql = "SELECT id, username, email, password_hash, role FROM users WHERE username = :login";
                }
                
                $user = fetchOne($sql, [':login' => $username]);
                
                if ($user && verifyPassword($password, $user['password_hash'])) {
                    // Logowanie udane
                    loginUser($user['id'], $user['username'], $user['email'], $user['role']);
                    
                    // Przekierowanie
                    $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 
                               (isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php');
                    
                    // Walidacja URL przekierowania (podstawowe zabezpieczenie)
                    if (!filter_var($redirect, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) && 
                        !preg_match('/^[a-zA-Z0-9._-]+\.php(\?.*)?$/', $redirect)) {
                        $redirect = 'index.php';
                    }
                    
                    setFlashMessage('success', 'Zostałeś pomyślnie zalogowany!');
                    header('Location: ' . $redirect);
                    exit();
                } else {
                    $error = 'Nieprawidłowa nazwa użytkownika/email lub hasło.';
                    
                    // Logowanie próby nieprawidłowego logowania (opcjonalnie)
                    error_log("Nieprawidłowa próba logowania dla: " . $username . " z IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                }
                
            } catch (Exception $e) {
                error_log("Błąd logowania: " . $e->getMessage());
                $error = 'Wystąpił błąd podczas logowania. Spróbuj ponownie.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>
                <i class="fas fa-sign-in-alt"></i>
                Zaloguj się
            </h1>
            <p>Wprowadź swoje dane, aby uzyskać dostęp do konta</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form id="loginForm" method="POST" class="auth-form">
            <?php csrfTokenField(); ?>
            
            <!-- Ukryte pole przekierowania -->
            <?php if (isset($_GET['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
            <?php endif; ?>
            
            <!-- Nazwa użytkownika/Email -->
            <div class="form-group">
                <label for="username" class="form-label">
                    <i class="fas fa-user"></i>
                    Nazwa użytkownika lub email
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($username); ?>"
                    required 
                    autofocus
                    autocomplete="username"
                    placeholder="Wprowadź nazwę użytkownika lub email"
                >
            </div>
            
            <!-- Hasło -->
            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i>
                    Hasło
                </label>
                <div class="password-input">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        required
                        autocomplete="current-password"
                        placeholder="Wprowadź hasło"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye" id="password-eye"></i>
                    </button>
                </div>
            </div>
            
            <!-- Zapamiętaj mnie (opcjonalnie w przyszłości) -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="remember" name="remember" class="form-check-input">
                    <label for="remember" class="form-check-label">
                        Zapamiętaj mnie
                    </label>
                </div>
            </div>
            
            <!-- Przycisk logowania -->
            <button type="submit" class="btn btn-primary btn-lg btn-full">
                <i class="fas fa-sign-in-alt"></i>
                Zaloguj się
            </button>
        </form>
        
        <div class="auth-footer">
            <p>
                Nie masz jeszcze konta?
                <a href="register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
                    Zarejestruj się
                </a>
            </p>
            
            <!-- Link do odzyskiwania hasła (do implementacji w przyszłości) -->
            <!--
            <p>
                <a href="forgot-password.php">Zapomniałeś hasła?</a>
            </p>
            -->
        </div>
    </div>
    
    <!-- Dodatkowe informacje -->
    <div class="auth-info">
        <div class="info-section">
            <h3>
                <i class="fas fa-tv"></i>
                Śledź swoje seriale
            </h3>
            <p>Organizuj swoją listę seriali, oznaczaj obejrzane odcinki i nigdy nie zapomnij, gdzie skończyłeś oglądanie.</p>
        </div>
        
        <div class="info-section">
            <h3>
                <i class="fas fa-star"></i>
                Oceniaj i recenzuj
            </h3>
            <p>Dziel się swoimi opiniami, oceniaj seriale i odkrywaj nowe tytuły dzięki rekomendacjom społeczności.</p>
        </div>
        
        <div class="info-section">
            <h3>
                <i class="fas fa-users"></i>
                Dołącz do społeczności
            </h3>
            <p>Poznaj innych fanów telewizji, dyskutuj o ulubionych serialach i odkrywaj nowe gatunki.</p>
        </div>
    </div>
</div>

<style>
/* Style dla stron autoryzacji */
.auth-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    max-width: 1000px;
    margin: 2rem auto;
    padding: 2rem;
    min-height: 70vh;
    align-items: start;
}

.auth-card {
    background-color: #2a2a2a;
    border: 1px solid #3a3a3a;
    border-radius: 12px;
    padding: 2.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-header h1 {
    color: #ff9500;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.auth-header p {
    color: #cccccc;
    font-size: 1rem;
}

.auth-form {
    margin-bottom: 2rem;
}

.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.alert-error {
    background-color: rgba(255, 68, 68, 0.1);
    border: 1px solid #ff4444;
    color: #ff6b6b;
}

.alert-success {
    background-color: rgba(0, 200, 81, 0.1);
    border: 1px solid #00c851;
    color: #00e676;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.password-input {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #888888;
    cursor: pointer;
    padding: 0.25rem;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #ff9500;
}

.btn-full {
    width: 100%;
}

.auth-footer {
    text-align: center;
    padding-top: 1.5rem;
    border-top: 1px solid #3a3a3a;
}

.auth-footer p {
    color: #cccccc;
    margin-bottom: 0.5rem;
}

.auth-footer a {
    color: #ff9500;
    text-decoration: none;
    font-weight: 500;
}

.auth-footer a:hover {
    color: #ffb347;
}

.auth-info {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.info-section {
    background-color: #2a2a2a;
    padding: 2rem;
    border-radius: 12px;
    border: 1px solid #3a3a3a;
}

.info-section h3 {
    color: #ff9500;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.info-section p {
    color: #cccccc;
    line-height: 1.6;
}

/* Responsywność */
@media (max-width: 768px) {
    .auth-container {
        grid-template-columns: 1fr;
        gap: 2rem;
        padding: 1rem;
    }
    
    .auth-card {
        padding: 2rem;
    }
    
    .auth-header h1 {
        font-size: 1.75rem;
    }
}

@media (max-width: 480px) {
    .auth-card {
        padding: 1.5rem;
    }
    
    .info-section {
        padding: 1.5rem;
    }
}

/* Animacje */
.auth-card, .info-section {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Focus states */
.form-control:focus {
    border-color: #ff9500;
    box-shadow: 0 0 0 3px rgba(255, 149, 0, 0.1);
}

.btn:focus {
    outline: 2px solid #ff9500;
    outline-offset: 2px;
}
</style>

<script>
/**
 * Przełącz widoczność hasła
 * @param {string} inputId - ID pola hasła
 */
function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const eyeIcon = document.getElementById(inputId + '-eye');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        eyeIcon.className = 'fas fa-eye';
    }
}

// Walidacja formularza po stronie klienta
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    let isValid = true;
    
    // Wyczyść poprzednie błędy
    clearFormErrors();
    
    // Walidacja nazwy użytkownika
    if (username.length < 3) {
        showFieldError('username', 'Nazwa użytkownika musi mieć co najmniej 3 znaki');
        isValid = false;
    }
    
    // Walidacja hasła
    if (password.length < 6) {
        showFieldError('password', 'Hasło musi mieć co najmniej 6 znaków');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
    }
});

/**
 * Pokaż błąd walidacji pola
 */
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    field.classList.add('is-invalid');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Wyczyść błędy walidacji
 */
function clearFormErrors() {
    const errors = document.querySelectorAll('.form-error');
    errors.forEach(error => error.remove());
    
    const invalidFields = document.querySelectorAll('.is-invalid');
    invalidFields.forEach(field => field.classList.remove('is-invalid'));
}

// Auto-focus na pierwszy błędny element
document.addEventListener('DOMContentLoaded', function() {
    const errorAlert = document.querySelector('.alert-error');
    if (errorAlert) {
        const usernameField = document.getElementById('username');
        if (usernameField.value.trim() === '') {
            usernameField.focus();
        } else {
            document.getElementById('password').focus();
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
