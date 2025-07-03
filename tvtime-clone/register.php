<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$page_title = 'Rejestracja - TV Time Clone';
$page_description = 'Stwórz konto TV Time Clone i zacznij śledzić swoje ulubione seriale';

$errors = [];
$form_data = [
    'username' => '',
    'email' => '',
    'password' => '',
    'confirm_password' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pobierz dane z formularza
    $form_data['username'] = trim($_POST['username'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    $form_data['password'] = $_POST['password'] ?? '';
    $form_data['confirm_password'] = $_POST['confirm_password'] ?? '';
    
    // Walidacja
    if (empty($form_data['username'])) {
        $errors['username'] = 'Nazwa użytkownika jest wymagana.';
    } elseif (strlen($form_data['username']) < 3) {
        $errors['username'] = 'Nazwa użytkownika musi mieć co najmniej 3 znaki.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['username'])) {
        $errors['username'] = 'Nazwa użytkownika może zawierać tylko litery, cyfry i podkreślenia.';
    }
    
    if (empty($form_data['email'])) {
        $errors['email'] = 'Adres email jest wymagany.';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Nieprawidłowy format adresu email.';
    }
    
    if (empty($form_data['password'])) {
        $errors['password'] = 'Hasło jest wymagane.';
    } elseif (strlen($form_data['password']) < 6) {
        $errors['password'] = 'Hasło musi mieć co najmniej 6 znaków.';
    }
    
    if ($form_data['password'] !== $form_data['confirm_password']) {
        $errors['confirm_password'] = 'Hasła nie są identyczne.';
    }
    
    if (!isset($_POST['terms'])) {
        $errors['terms'] = 'Musisz zaakceptować regulamin.';
    }
    
    // Sprawdź czy username/email już istnieją
    if (empty($errors)) {
        try {
            $existing_user = fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", 
                                    [$form_data['username'], $form_data['email']]);
            if ($existing_user) {
                $errors['general'] = 'Użytkownik o tej nazwie lub emailu już istnieje.';
            }
        } catch (Exception $e) {
            $errors['general'] = 'Błąd sprawdzania danych użytkownika.';
        }
    }
    
    // Jeśli nie ma błędów, zarejestruj użytkownika
    if (empty($errors)) {
        try {
            // Hash hasła
            $password_hash = hashPassword($form_data['password']);
            
            // Wstaw nowego użytkownika
            $sql = "INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = getDB()->prepare($sql);
            $stmt->execute([$form_data['username'], $form_data['email'], $password_hash]);
            
            // Pobierz ID nowego użytkownika
            $user_id = getDB()->lastInsertId();
            
            // Automatyczne logowanie po rejestracji
            loginUser($user_id, $form_data['username'], $form_data['email']);
            
            // Przekierowanie z komunikatem sukcesu
            setFlashMessage('success', 'Konto zostało utworzone pomyślnie! Witamy w TV Time Clone!');
            
            $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 
                       (isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php');
            
            // Walidacja URL przekierowania
            if (!preg_match('/^[a-zA-Z0-9._-]+\.php(\?.*)?$/', $redirect)) {
                $redirect = 'index.php';
            }
            
            header('Location: ' . $redirect);
            exit();
            
        } catch (Exception $e) {
            error_log("Błąd rejestracji: " . $e->getMessage());
            $errors['general'] = 'Wystąpił błąd podczas tworzenia konta. Spróbuj ponownie.';
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>
                <i class="fas fa-user-plus"></i>
                Stwórz konto
            </h1>
            <p>Dołącz do społeczności fanów telewizji</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form id="registerForm" method="POST" class="auth-form" novalidate>
            <?php if (function_exists('csrfTokenField')): ?>
                <?php csrfTokenField(); ?>
            <?php endif; ?>
            
            <!-- Ukryte pole przekierowania -->
            <?php if (isset($_GET['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
            <?php endif; ?>
            
            <!-- Nazwa użytkownika -->
            <div class="form-group">
                <label for="username" class="form-label">
                    <i class="fas fa-user"></i>
                    Nazwa użytkownika
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                    value="<?php echo htmlspecialchars($form_data['username']); ?>"
                    required 
                    autofocus
                    autocomplete="username"
                    placeholder="Wybierz unikalną nazwę użytkownika"
                    minlength="3"
                    maxlength="50"
                >
                <?php if (isset($errors['username'])): ?>
                    <div class="form-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['username']); ?>
                    </div>
                <?php endif; ?>
                <div class="form-help">
                    Może zawierać litery, cyfry i podkreślenia. Minimum 3 znaki.
                </div>
            </div>
            
            <!-- Email -->
            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i>
                    Adres email
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                    value="<?php echo htmlspecialchars($form_data['email']); ?>"
                    required
                    autocomplete="email"
                    placeholder="Twój adres email"
                    maxlength="100"
                >
                <?php if (isset($errors['email'])): ?>
                    <div class="form-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['email']); ?>
                    </div>
                <?php endif; ?>
                <div class="form-help">
                    Będzie używany do logowania i powiadomień.
                </div>
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
                        class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                        required
                        autocomplete="new-password"
                        placeholder="Wybierz bezpieczne hasło"
                        minlength="6"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye" id="password-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['password'])): ?>
                    <div class="form-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['password']); ?>
                    </div>
                <?php endif; ?>
                <div class="form-help">
                    Minimum 6 znaków. Użyj kombinacji liter, cyfr i znaków specjalnych.
                </div>
            </div>
            
            <!-- Potwierdzenie hasła -->
            <div class="form-group">
                <label for="confirm_password" class="form-label">
                    <i class="fas fa-lock"></i>
                    Powtórz hasło
                </label>
                <div class="password-input">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                        required
                        autocomplete="new-password"
                        placeholder="Powtórz hasło"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye" id="confirm_password-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['confirm_password'])): ?>
                    <div class="form-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['confirm_password']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Regulamin -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
                    <label for="terms" class="form-check-label">
                        Akceptuję <a href="#terms" target="_blank">regulamin</a> 
                        i <a href="#privacy" target="_blank">politykę prywatności</a>
                    </label>
                </div>
                <?php if (isset($errors['terms'])): ?>
                    <div class="form-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['terms']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Newsletter (opcjonalnie) -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="newsletter" name="newsletter" class="form-check-input">
                    <label for="newsletter" class="form-check-label">
                        Chcę otrzymywać newsletter z nowościami i rekomendacjami seriali
                    </label>
                </div>
            </div>
            
            <!-- Przycisk rejestracji -->
            <button type="submit" class="btn btn-primary btn-lg btn-full">
                <i class="fas fa-user-plus"></i>
                Stwórz konto
            </button>
        </form>
        
        <div class="auth-footer">
            <p>
                Masz już konto?
                <a href="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
                    Zaloguj się
                </a>
            </p>
        </div>
    </div>
    
    <!-- Informacje o korzyściach -->
    <div class="auth-info">
        <div class="info-section">
            <h3>
                <i class="fas fa-list-check"></i>
                Śledź postępy
            </h3>
            <p>Oznaczaj obejrzane odcinki, śledź postępy w serialach i nigdy nie zatrać się w fabule.</p>
        </div>
        
        <div class="info-section">
            <h3>
                <i class="fas fa-heart"></i>
                Twoja lista ulubionych
            </h3>
            <p>Twórz listy seriali do obejrzenia, ukończonych i porzuconych. Wszystko w jednym miejscu.</p>
        </div>
        
        <div class="info-section">
            <h3>
                <i class="fas fa-chart-line"></i>
                Statystyki oglądania
            </h3>
            <p>Zobacz ile czasu spędziłeś oglądając seriale, jakie gatunki preferujesz i wiele więcej.</p>
        </div>
        
        <div class="info-section">
            <h3>
                <i class="fas fa-comments"></i>
                Społeczność
            </h3>
            <p>Oceniaj seriale, pisz recenzje i dziel się opiniami z innymi miłośnikami dobrej telewizji.</p>
        </div>
    </div>
</div>

<style>
/* Dodatkowe style specyficzne dla rejestracji */
.form-help {
    font-size: 0.8rem;
    color: #888888;
    margin-top: 0.25rem;
    line-height: 1.4;
}

.form-error {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #ff4444;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.form-control.is-invalid {
    border-color: #ff4444;
    box-shadow: 0 0 0 3px rgba(255, 68, 68, 0.1);
}

.form-check-label a {
    color: #ff9500;
    text-decoration: none;
}

.form-check-label a:hover {
    color: #ffb347;
    text-decoration: underline;
}

.alert ul {
    margin: 0;
    padding-left: 1.5rem;
}

.alert li {
    margin: 0.25rem 0;
}

/* Animacje walidacji */
.form-control.is-invalid {
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Loading state dla przycisku */
.btn-loading {
    position: relative;
    color: transparent;
}

.btn-loading::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    color: white;
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}
</style>

<script>
// Walidacja formularza rejestracji
document.getElementById('registerForm').addEventListener('submit', function(e) {
    // Sprawdź czy wszystkie wymagane pola są wypełnione
    const requiredFields = ['username', 'email', 'password', 'confirm_password', 'terms'];
    let allValid = true;
    
    requiredFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (!validateField(field)) {
            allValid = false;
        }
    });
    
    if (!allValid) {
        e.preventDefault();
        showError('Wypełnij wszystkie wymagane pola poprawnie.');
        return false;
    }
    
    // Dodaj loading state do przycisku
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;
});

// Real-time walidacja pól
document.getElementById('username').addEventListener('input', function() {
    validateUsername(this);
});

document.getElementById('email').addEventListener('input', function() {
    validateEmail(this);
});

document.getElementById('password').addEventListener('input', function() {
    validatePassword(this);
});

document.getElementById('confirm_password').addEventListener('input', function() {
    validatePasswordConfirm(this);
});

/**
 * Walidacja nazwy użytkownika
 */
function validateUsername(field) {
    const value = field.value.trim();
    clearFieldError(field);
    
    if (value.length === 0) {
        return true; // Będzie walidowane przy submit
    }
    
    if (value.length < 3) {
        showFieldError(field, 'Nazwa użytkownika musi mieć co najmniej 3 znaki');
        return false;
    }
    
    if (value.length > 50) {
        showFieldError(field, 'Nazwa użytkownika nie może być dłuższa niż 50 znaków');
        return false;
    }
    
    if (!/^[a-zA-Z0-9_]+$/.test(value)) {
        showFieldError(field, 'Nazwa użytkownika może zawierać tylko litery, cyfry i podkreślenia');
        return false;
    }
    
    return true;
}

/**
 * Walidacja adresu email
 */
function validateEmail(field) {
    const value = field.value.trim();
    clearFieldError(field);
    
    if (value.length === 0) {
        return true; // Będzie walidowane przy submit
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
        showFieldError(field, 'Podaj prawidłowy adres email');
        return false;
    }
    
    if (value.length > 100) {
        showFieldError(field, 'Adres email nie może być dłuższy niż 100 znaków');
        return false;
    }
    
    return true;
}

/**
 * Walidacja hasła
 */
function validatePassword(field) {
    const value = field.value;
    clearFieldError(field);
    
    if (value.length === 0) {
        return true; // Będzie walidowane przy submit
    }
    
    if (value.length < 6) {
        showFieldError(field, 'Hasło musi mieć co najmniej 6 znaków');
        return false;
    }
    
    if (value.length > 255) {
        showFieldError(field, 'Hasło nie może być dłuższe niż 255 znaków');
        return false;
    }
    
    return true;
}

/**
 * Walidacja potwierdzenia hasła
 */
function validatePasswordConfirm(field) {
    const password = document.getElementById('password').value;
    const confirmPassword = field.value;
    
    clearFieldError(field);
    
    if (confirmPassword.length === 0) {
        return true; // Będzie walidowane przy submit
    }
    
    if (password !== confirmPassword) {
        showFieldError(field, 'Hasła nie są identyczne');
        return false;
    }
    
    return true;
}

/**
 * Uniwersalna walidacja pola
 */
function validateField(field) {
    const fieldName = field.name;
    
    switch (fieldName) {
        case 'username':
            return validateUsername(field);
        case 'email':
            return validateEmail(field);
        case 'password':
            return validatePassword(field);
        case 'confirm_password':
            return validatePasswordConfirm(field);
        case 'terms':
            if (!field.checked) {
                showError('Musisz zaakceptować regulamin i politykę prywatności.');
                return false;
            }
            return true;
        default:
            return true;
    }
}

/**
 * Pokaż błąd pola
 */
function showFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('is-invalid');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Wyczyść błąd pola
 */
function clearFieldError(field) {
    field.classList.remove('is-invalid');
    const existingError = field.parentNode.querySelector('.form-error');
    if (existingError) {
        existingError.remove();
    }
}

/**
 * Przełącz widoczność hasła
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

/**
 * Pokaż komunikat błędu
 */
function showError(message) {
    // Usuń poprzednie alerty
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-error';
    alertDiv.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <span>${message}</span>
    `;
    
    const form = document.getElementById('registerForm');
    form.parentNode.insertBefore(alertDiv, form);
    
    // Przewiń do alertu
    alertDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>

<?php include 'includes/footer.php'; ?>
