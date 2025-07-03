<?php
/**
 * Dodawanie nowego serialu - Admin Panel
 */

$errors = [];
$form_data = [
    'title' => '',
    'description' => '',
    'genre' => '',
    'year' => date('Y'),
    'poster_url' => ''
];

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        // Pobierz dane z formularza
        $form_data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'genre' => trim($_POST['genre'] ?? ''),
            'year' => (int)($_POST['year'] ?? date('Y')),
            'poster_url' => ''
        ];
        
        // Walidacja
        $validation = validateShowData($form_data);
        if (!$validation['valid']) {
            $errors = $validation['errors'];
        }
        
        // Sprawdź czy serial o tym tytule już istnieje
        if (empty($errors)) {
            try {
                $existing = fetchOne("SELECT id FROM shows WHERE title = :title", [':title' => $form_data['title']]);
                if ($existing) {
                    $errors[] = 'Serial o tym tytule już istnieje w bazie danych.';
                }
            } catch (Exception $e) {
                $errors[] = 'Błąd podczas sprawdzania duplikatów.';
            }
        }
        
        // Upload plakatu
        if (empty($errors) && isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = uploadShowImage($_FILES['poster']);
            if ($upload_result['success']) {
                $form_data['poster_url'] = $upload_result['filename'];
            } else {
                $errors[] = $upload_result['error'];
            }
        }
        
        // Zapisz do bazy danych
        if (empty($errors)) {
            try {
                $sql = "
                    INSERT INTO shows (title, description, genre, year, poster_url, created_at) 
                    VALUES (:title, :description, :genre, :year, :poster_url, NOW())
                ";
                
                $params = [
                    ':title' => $form_data['title'],
                    ':description' => $form_data['description'],
                    ':genre' => $form_data['genre'],
                    ':year' => $form_data['year'],
                    ':poster_url' => $form_data['poster_url']
                ];
                
                executeQuery($sql, $params);
                
                logAdminAction('ADD_SHOW', "Dodano serial: {$form_data['title']}");
                setFlashMessage('success', 'Serial został dodany pomyślnie!');
                
                header('Location: admin.php?action=list');
                exit();
                
            } catch (Exception $e) {
                error_log("Błąd dodawania serialu: " . $e->getMessage());
                $errors[] = 'Wystąpił błąd podczas zapisywania serialu. Spróbuj ponownie.';
                
                // Usuń uploadowany plik w przypadku błędu
                if (!empty($form_data['poster_url'])) {
                    deleteShowImage($form_data['poster_url']);
                }
            }
        }
    } else {
        $errors[] = 'Nieprawidłowy token bezpieczeństwa. Odśwież stronę i spróbuj ponownie.';
    }
}

$available_genres = getAvailableGenres();
?>

<div class="admin-header">
    <h1><i class="fas fa-plus"></i> Dodaj nowy serial</h1>
    <p>Wypełnij formularz, aby dodać nowy serial do bazy danych</p>
</div>

<div class="form-container">
    <!-- Wyświetlanie błędów -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <h4><i class="fas fa-exclamation-triangle"></i> Błędy formularza:</h4>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="admin-form">
        <?php csrfTokenField(); ?>
        
        <div class="form-grid">
            <!-- Lewa kolumna -->
            <div class="form-column">
                <div class="form-group">
                    <label for="title" class="required">Tytuł serialu</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-control"
                        value="<?php echo htmlspecialchars($form_data['title']); ?>"
                        required
                        maxlength="255"
                        placeholder="np. Breaking Bad"
                    >
                </div>

                <div class="form-group">
                    <label for="genre" class="required">Gatunek</label>
                    <select id="genre" name="genre" class="form-control" required>
                        <option value="">Wybierz gatunek</option>
                        <?php foreach ($available_genres as $value => $label): ?>
                            <option value="<?php echo htmlspecialchars($value); ?>"
                                    <?php echo $form_data['genre'] === $value ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="year" class="required">Rok premiery</label>
                    <input 
                        type="number" 
                        id="year" 
                        name="year" 
                        class="form-control"
                        value="<?php echo $form_data['year']; ?>"
                        required
                        min="1900"
                        max="<?php echo date('Y') + 5; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="description" class="required">Opis</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-control"
                        rows="6"
                        required
                        placeholder="Opisz fabułę serialu..."
                    ><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                    <small class="form-help">Podaj krótki opis fabuły serialu</small>
                </div>
            </div>

            <!-- Prawa kolumna -->
            <div class="form-column">
                <div class="form-group">
                    <label for="poster">Plakat serialu</label>
                    <div class="file-upload-area">
                        <input 
                            type="file" 
                            id="poster" 
                            name="poster" 
                            class="file-input"
                            accept="image/*"
                            onchange="previewImage(this)"
                        >
                        <div class="file-upload-content">
                            <div class="file-upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <p>Kliknij lub przeciągnij obraz tutaj</p>
                            <small>Dozwolone formaty: JPG, PNG, WebP, GIF (maks. 5MB)</small>
                        </div>
                    </div>
                    
                    <div id="imagePreview" class="image-preview" style="display: none;">
                        <img id="previewImg" src="" alt="Podgląd">
                        <button type="button" onclick="removePreview()" class="remove-preview">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="form-info">
                    <h4><i class="fas fa-info-circle"></i> Wskazówki</h4>
                    <ul>
                        <li>Używaj wysokiej jakości obrazów plakatu</li>
                        <li>Optymalne wymiary: 300x450 pikseli</li>
                        <li>Maksymalny rozmiar pliku: 5MB</li>
                        <li>Sprawdź pisownię przed zapisaniem</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="admin.php?action=list" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Anuluj
            </a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Dodaj serial
            </button>
        </div>
    </form>
</div>

<style>
.form-container {
    max-width: 1200px;
    margin: 0 auto;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
}

.alert-error {
    background: #e74c3c20;
    border: 1px solid #e74c3c;
    color: #e74c3c;
}

.alert h4 {
    margin-bottom: 0.5rem;
}

.alert ul {
    margin-left: 1.5rem;
}

.admin-form {
    background: #2a2a2a;
    padding: 2rem;
    border-radius: 8px;
    border: 1px solid #3a3a3a;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #fff;
    font-weight: 500;
}

.form-group label.required::after {
    content: ' *';
    color: #e74c3c;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    background: #1a1a1a;
    border: 1px solid #3a3a3a;
    border-radius: 6px;
    color: #fff;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: #ff9500;
    box-shadow: 0 0 0 2px #ff950020;
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.form-help {
    display: block;
    margin-top: 0.25rem;
    color: #888;
    font-size: 0.85rem;
}

.file-upload-area {
    position: relative;
    border: 2px dashed #3a3a3a;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.file-upload-area:hover {
    border-color: #ff9500;
    background: #ff950010;
}

.file-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.file-upload-icon {
    font-size: 2rem;
    color: #666;
    margin-bottom: 1rem;
}

.file-upload-content p {
    margin-bottom: 0.5rem;
    color: #ccc;
}

.file-upload-content small {
    color: #888;
}

.image-preview {
    position: relative;
    margin-top: 1rem;
    border-radius: 8px;
    overflow: hidden;
    max-width: 200px;
}

.image-preview img {
    width: 100%;
    height: auto;
    display: block;
}

.remove-preview {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-info {
    background: #1a1a1a;
    border: 1px solid #3a3a3a;
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 1rem;
}

.form-info h4 {
    color: #ff9500;
    margin-bottom: 1rem;
}

.form-info ul {
    margin-left: 1.2rem;
    color: #ccc;
}

.form-info li {
    margin-bottom: 0.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1.5rem;
    border-top: 1px solid #3a3a3a;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

function removePreview() {
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('poster').value = '';
}

// Drag & drop functionality
const uploadArea = document.querySelector('.file-upload-area');
const fileInput = document.getElementById('poster');

uploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    uploadArea.style.borderColor = '#ff9500';
    uploadArea.style.background = '#ff950020';
});

uploadArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    uploadArea.style.borderColor = '#3a3a3a';
    uploadArea.style.background = 'transparent';
});

uploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    uploadArea.style.borderColor = '#3a3a3a';
    uploadArea.style.background = 'transparent';
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        previewImage(fileInput);
    }
});
</script>
