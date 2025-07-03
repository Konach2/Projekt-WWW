<?php
/**
 * Edycja serialu - Admin Panel
 */

$show_id = (int)($_GET['id'] ?? 0);
$errors = [];
$show = null;

// Pobierz dane serialu
try {
    $show = fetchOne("SELECT * FROM shows WHERE id = :id", [':id' => $show_id]);
    if (!$show) {
        setFlashMessage('error', 'Serial nie został znaleziony.');
        header('Location: admin.php?action=list');
        exit();
    }
} catch (Exception $e) {
    setFlashMessage('error', 'Błąd podczas ładowania danych serialu.');
    header('Location: admin.php?action=list');
    exit();
}

$form_data = [
    'title' => $show['title'],
    'description' => $show['description'],
    'genre' => $show['genre'],
    'year' => $show['year'],
    'poster_url' => $show['poster_url']
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
            'poster_url' => $show['poster_url'] // Zachowaj obecny plakat
        ];
        
        // Walidacja
        $validation = validateShowData($form_data);
        if (!$validation['valid']) {
            $errors = $validation['errors'];
        }
        
        // Sprawdź czy serial o tym tytule już istnieje (poza edytowanym)
        if (empty($errors)) {
            try {
                $existing = fetchOne(
                    "SELECT id FROM shows WHERE title = :title AND id != :id", 
                    [':title' => $form_data['title'], ':id' => $show_id]
                );
                if ($existing) {
                    $errors[] = 'Serial o tym tytule już istnieje w bazie danych.';
                }
            } catch (Exception $e) {
                $errors[] = 'Błąd podczas sprawdzania duplikatów.';
            }
        }
        
        // Obsługa nowego plakatu
        $new_poster_uploaded = false;
        if (empty($errors) && isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = uploadShowImage($_FILES['poster']);
            if ($upload_result['success']) {
                // Usuń stary plakat
                if (!empty($show['poster_url'])) {
                    deleteShowImage($show['poster_url']);
                }
                $form_data['poster_url'] = $upload_result['filename'];
                $new_poster_uploaded = true;
            } else {
                $errors[] = $upload_result['error'];
            }
        }
        
        // Obsługa usuwania plakatu
        if (empty($errors) && isset($_POST['remove_poster']) && $_POST['remove_poster'] === '1') {
            if (!empty($show['poster_url'])) {
                deleteShowImage($show['poster_url']);
            }
            $form_data['poster_url'] = '';
        }
        
        // Aktualizuj w bazie danych
        if (empty($errors)) {
            try {
                $sql = "
                    UPDATE shows 
                    SET title = :title, description = :description, genre = :genre, 
                        year = :year, poster_url = :poster_url
                    WHERE id = :id
                ";
                
                $params = [
                    ':title' => $form_data['title'],
                    ':description' => $form_data['description'],
                    ':genre' => $form_data['genre'],
                    ':year' => $form_data['year'],
                    ':poster_url' => $form_data['poster_url'],
                    ':id' => $show_id
                ];
                
                executeQuery($sql, $params);
                
                logAdminAction('EDIT_SHOW', "Edytowano serial: {$form_data['title']} (ID: $show_id)");
                setFlashMessage('success', 'Serial został zaktualizowany pomyślnie!');
                
                header('Location: admin.php?action=list');
                exit();
                
            } catch (Exception $e) {
                error_log("Błąd edycji serialu: " . $e->getMessage());
                $errors[] = 'Wystąpił błąd podczas zapisywania zmian. Spróbuj ponownie.';
                
                // Cofnij upload nowego plakatu w przypadku błędu
                if ($new_poster_uploaded && !empty($form_data['poster_url'])) {
                    deleteShowImage($form_data['poster_url']);
                    $form_data['poster_url'] = $show['poster_url'];
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
    <h1><i class="fas fa-edit"></i> Edytuj serial</h1>
    <p>Modyfikuj dane serialu "<?php echo htmlspecialchars($show['title']); ?>"</p>
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
                    ><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                </div>
            </div>

            <!-- Prawa kolumna -->
            <div class="form-column">
                <div class="form-group">
                    <label>Plakat serialu</label>
                    
                    <!-- Obecny plakat -->
                    <?php if (!empty($form_data['poster_url']) && imageExists($form_data['poster_url'])): ?>
                        <div class="current-poster">
                            <h4>Obecny plakat:</h4>
                            <div class="poster-preview">
                                <img src="<?php echo getImageUrl($form_data['poster_url']); ?>" 
                                     alt="Obecny plakat" 
                                     style="max-width: 200px; height: auto; border-radius: 8px;">
                            </div>
                            <div class="poster-actions">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="remove_poster" value="1" id="removePoster">
                                    <span class="checkmark"></span>
                                    Usuń obecny plakat
                                </label>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-poster">
                            <i class="fas fa-image" style="font-size: 2rem; color: #666;"></i>
                            <p>Brak plakatu</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Upload nowego plakatu -->
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label for="poster">Nowy plakat (opcjonalnie)</label>
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
                                <p>Kliknij lub przeciągnij nowy obraz</p>
                                <small>JPG, PNG, WebP, GIF (maks. 5MB)</small>
                            </div>
                        </div>
                        
                        <div id="imagePreview" class="image-preview" style="display: none;">
                            <img id="previewImg" src="" alt="Podgląd">
                            <button type="button" onclick="removePreview()" class="remove-preview">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-info">
                    <h4><i class="fas fa-info-circle"></i> Informacje</h4>
                    <ul>
                        <li><strong>ID:</strong> <?php echo $show['id']; ?></li>
                        <li><strong>Utworzony:</strong> <?php echo date('d.m.Y H:i', strtotime($show['created_at'])); ?></li>
                        <li><strong>Plik plakatu:</strong> <?php echo !empty($show['poster_url']) ? htmlspecialchars($show['poster_url']) : 'Brak'; ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="admin.php?action=list" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Anuluj
            </a>
            <a href="show-details.php?id=<?php echo $show['id']; ?>" class="btn btn-info">
                <i class="fas fa-eye"></i> Zobacz serial
            </a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Zapisz zmiany
            </button>
        </div>
    </form>
</div>

<style>
/* Dodatkowe style dla edycji */
.current-poster {
    margin-bottom: 1rem;
    padding: 1rem;
    background: #1a1a1a;
    border-radius: 8px;
    border: 1px solid #3a3a3a;
}

.current-poster h4 {
    color: #ff9500;
    margin-bottom: 1rem;
}

.poster-preview {
    text-align: center;
    margin-bottom: 1rem;
}

.poster-actions {
    text-align: center;
}

.checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    color: #e74c3c;
    font-size: 0.9rem;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.no-poster {
    text-align: center;
    padding: 2rem;
    background: #1a1a1a;
    border-radius: 8px;
    border: 1px solid #3a3a3a;
    color: #666;
}

.no-poster p {
    margin-top: 0.5rem;
    margin-bottom: 0;
}

/* Style z poprzedniego formularza (kopiowane dla spójności) */
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

.file-upload-area {
    position: relative;
    border: 2px dashed #3a3a3a;
    border-radius: 8px;
    padding: 1.5rem;
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
    font-size: 1.5rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.file-upload-content p {
    margin-bottom: 0.25rem;
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

// Gdy zaznaczone jest usuwanie plakatu, wyłącz upload nowego
document.getElementById('removePoster')?.addEventListener('change', function() {
    const posterInput = document.getElementById('poster');
    if (this.checked) {
        posterInput.disabled = true;
        removePreview();
    } else {
        posterInput.disabled = false;
    }
});
</script>
