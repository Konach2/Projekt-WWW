/**
 * TV Time Clone - Main JavaScript
 * Autor: System
 * Data: 2025-06-29
 */

$(document).ready(function() {
    // Inicjalizacja aplikacji
    initializeApp();
});

/**
 * Inicjalizacja głównych funkcji aplikacji
 */
function initializeApp() {
    initializeSearch();
    initializeShowActions();
    initializeFormValidation();
    initializeTooltips();
    initializeImageLazyLoading();
    initializeFileUploads();
    initializeQuickAddButtons();
    initializeShowCardEffects();
}

/**
 * Inicjalizacja wyszukiwania na żywo
 */
function initializeSearch() {
    // Wyszukiwanie w nawigacji
    const navSearchInput = $('#navSearchInput');
    const navSearchResults = $('#navSearchResults');
    
    // Wyszukiwanie na stronie głównej
    const searchInput = $('#searchInput');
    const searchResults = $('#searchResults');
    
    let searchTimeout;
    
    // Wyszukiwanie w nawigacji
    if (navSearchInput.length) {
        navSearchInput.on('input', function() {
            const query = $(this).val().trim();
            
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(function() {
                if (query.length >= 2) {
                    performNavSearch(query);
                } else {
                    hideNavSearchResults();
                }
            }, 300);
        });
        
        // Ukryj wyniki po kliknięciu poza
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.nav-search').length) {
                hideNavSearchResults();
            }
        });
        
        // Enter w polu wyszukiwania nawigacji
        navSearchInput.on('keypress', function(e) {
            if (e.which === 13) { // Enter
                e.preventDefault();
                const query = $(this).val().trim();
                if (query.length >= 2) {
                    window.location.href = 'index.php?search=' + encodeURIComponent(query);
                }
            }
        });
    }
    
    // Wyszukiwanie na stronie głównej (existing code)
    if (searchInput.length) {
        searchInput.on('input', function() {
            const query = $(this).val().trim();
            
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(function() {
                if (query.length >= 2) {
                    performSearch(query);
                } else {
                    hideSearchResults();
                }
            }, 300);
        });
        
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.search-box').length) {
                hideSearchResults();
            }
        });
    }
}

/**
 * Wykonaj wyszukiwanie AJAX
 * @param {string} query - Zapytanie wyszukiwania
 */
function performSearch(query) {
    showLoading();
    
    $.ajax({
        url: 'ajax/search-shows.php',
        type: 'POST',
        dataType: 'json',
        data: {
            query: query,
            csrf_token: $('input[name="csrf_token"]').val()
        },
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                displaySearchResults(response.data);
            } else {
                showError(response.message || 'Błąd podczas wyszukiwania');
            }
        },
        error: function() {
            hideLoading();
            showError('Błąd połączenia. Spróbuj ponownie.');
        }
    });
}

/**
 * Wykonaj wyszukiwanie AJAX w nawigacji
 * @param {string} query - Zapytanie wyszukiwania
 */
function performNavSearch(query) {
    $.ajax({
        url: 'ajax/search-shows.php',
        type: 'POST',
        dataType: 'json',
        data: {
            query: query,
            csrf_token: $('input[name="csrf_token"]').val() || generateTempCSRFToken()
        },
        success: function(response) {
            if (response.success) {
                displayNavSearchResults(response.data);
            } else {
                displayNavSearchResults([]);
            }
        },
        error: function() {
            displayNavSearchResults([]);
        }
    });
}

/**
 * Wyświetl wyniki wyszukiwania
 * @param {Array} shows - Lista seriali
 */
function displaySearchResults(shows) {
    const searchResults = $('#searchResults');
    
    if (shows.length === 0) {
        searchResults.html('<div class="search-no-results">Brak wyników wyszukiwania</div>');
    } else {
        let html = '<div class="search-results-list">';
        
        shows.forEach(function(show) {
            html += `
                <div class="search-result-item" onclick="window.location.href='show-details.php?id=${show.id}'">
                    <div class="search-result-poster">
                        <img src="${show.poster_url || 'assets/uploads/placeholder.jpg'}" alt="${show.title}">
                    </div>
                    <div class="search-result-info">
                        <h4>${show.title}</h4>
                        <p>${show.year} • ${show.genre}</p>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        searchResults.html(html);
    }
    
    searchResults.show();
}

/**
 * Wyświetl wyniki wyszukiwania w nawigacji
 * @param {Array} shows - Lista seriali
 */
function displayNavSearchResults(shows) {
    const searchResults = $('#navSearchResults');
    
    if (shows.length === 0) {
        searchResults.html('<div class="search-no-results">Brak wyników wyszukiwania</div>');
    } else {
        let html = '';
        
        shows.forEach(function(show) {
            const rating = show.avg_rating > 0 ? 
                `<div class="search-result-rating">
                    <i class="fas fa-star"></i>
                    ${show.avg_rating}
                </div>` : '';
            
            html += `
                <a href="${show.url}" class="search-result-item">
                    <img src="${show.poster_url}" alt="${show.title}" class="search-result-poster">
                    <div class="search-result-info">
                        <div class="search-result-title">${show.title}</div>
                        <div class="search-result-meta">
                            ${show.year} • ${show.genre}
                            ${rating}
                        </div>
                    </div>
                </a>
            `;
        });
        
        searchResults.html(html);
    }
    
    searchResults.addClass('active');
}

/**
 * Ukryj wyniki wyszukiwania
 */
function hideSearchResults() {
    $('#searchResults').hide();
}

/**
 * Ukryj wyniki wyszukiwania w nawigacji
 */
function hideNavSearchResults() {
    $('#navSearchResults').removeClass('active');
}

/**
 * Inicjalizacja akcji dla seriali
 */
function initializeShowActions() {
    // Dodaj serial do listy
    $(document).on('click', '.add-to-list-btn', function(e) {
        e.preventDefault();
        
        const showId = $(this).data('show-id');
        const status = $(this).data('status') || 'plan_to_watch';
        
        addToList(showId, status, $(this));
    });
    
    // Zmień status serialu
    $(document).on('change', '.status-select', function() {
        const showId = $(this).data('show-id');
        const newStatus = $(this).val();
        
        updateShowStatus(showId, newStatus, $(this));
    });
    
    // Zmień ocenę serialu
    $(document).on('change', '.rating-input', function() {
        const showId = $(this).data('show-id');
        const rating = $(this).val();
        
        updateShowRating(showId, rating, $(this));
    });
    
    // Oznacz odcinek jako obejrzany
    $(document).on('change', '.episode-checkbox', function() {
        const episodeId = $(this).data('episode-id');
        const watched = $(this).is(':checked');
        
        toggleEpisode(episodeId, watched, $(this));
    });
}

/**
 * Dodaj serial do listy użytkownika
 * @param {number} showId - ID serialu
 * @param {string} status - Status serialu
 * @param {jQuery} button - Przycisk który został kliknięty
 */
function addToList(showId, status, button) {
    const originalText = button.html();
    button.html('<i class="fas fa-spinner fa-spin"></i> Dodawanie...');
    button.prop('disabled', true);
    
    $.ajax({
        url: 'ajax/add-to-list.php',
        type: 'POST',
        dataType: 'json',
        data: {
            show_id: showId,
            status: status,
            csrf_token: $('input[name="csrf_token"]').val()
        },
        success: function(response) {
            if (response.success) {
                button.html('<i class="fas fa-check"></i> Dodano');
                button.removeClass('btn-primary').addClass('btn-success');
                showSuccess('Serial został dodany do Twojej listy!');
                
                // Odśwież statystyki jeśli istnieją
                updateUserStats();
            } else {
                showError(response.message || 'Błąd podczas dodawania serialu');
                button.html(originalText);
                button.prop('disabled', false);
            }
        },
        error: function() {
            showError('Błąd połączenia. Spróbuj ponownie.');
            button.html(originalText);
            button.prop('disabled', false);
        }
    });
}

/**
 * Szybkie dodawanie serialu do listy użytkownika
 * @param {number} showId - ID serialu
 * @param {string} status - Status serialu
 * @param {jQuery} button - Przycisk który został kliknięty
 */
function quickAddToList(showId, status, button) {
    const originalHtml = button.html();
    const originalClass = button.attr('class');
    
    // Pokaż loading
    button.html('<i class="fas fa-spinner fa-spin"></i>').addClass('loading').prop('disabled', true);
    
    $.ajax({
        url: 'ajax/add-to-list.php',
        type: 'POST',
        dataType: 'json',
        data: {
            show_id: showId,
            status: status,
            csrf_token: $('input[name="csrf_token"]').val()
        },
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Zmień wygląd przycisku na "dodane"
                button.removeClass('btn-success btn-primary')
                      .addClass('btn-secondary')
                      .html('<i class="fas fa-check"></i>')
                      .prop('title', 'Dodane: ' + response.status_name);
                
                // Wyłącz przycisk na 2 sekundy
                setTimeout(function() {
                    button.removeClass('loading btn-secondary')
                          .addClass(originalClass.includes('btn-success') ? 'btn-success' : 'btn-primary')
                          .html(originalHtml)
                          .prop('disabled', false);
                }, 2000);
                
            } else {
                showError(response.message || 'Błąd podczas dodawania serialu do listy');
                button.removeClass('loading').html(originalHtml).prop('disabled', false);
            }
        },
        error: function() {
            showError('Błąd połączenia. Spróbuj ponownie.');
            button.removeClass('loading').html(originalHtml).prop('disabled', false);
        }
    });
}

/**
 * Zaktualizuj status serialu
 * @param {number} showId - ID serialu
 * @param {string} status - Nowy status
 * @param {jQuery} select - Element select
 */
function updateShowStatus(showId, status, select) {
    const originalValue = select.data('original-value');
    
    $.ajax({
        url: 'ajax/update-show-status.php',
        type: 'POST',
        dataType: 'json',
        data: {
            show_id: showId,
            status: status,
            csrf_token: $('input[name="csrf_token"]').val()
        },
        success: function(response) {
            if (response.success) {
                select.data('original-value', status);
                showSuccess('Status serialu został zaktualizowany!');
                
                // Zaktualizuj kolor statusu
                updateStatusColor(select, status);
                updateUserStats();
            } else {
                showError(response.message || 'Błąd podczas aktualizacji statusu');
                select.val(originalValue);
            }
        },
        error: function() {
            showError('Błąd połączenia. Spróbuj ponownie.');
            select.val(originalValue);
        }
    });
}

/**
 * Zaktualizuj ocenę serialu
 * @param {number} showId - ID serialu
 * @param {number} rating - Nowa ocena
 * @param {jQuery} input - Element input
 */
function updateShowRating(showId, rating, input) {
    const originalValue = input.data('original-value');
    
    $.ajax({
        url: 'ajax/update-show-rating.php',
        type: 'POST',
        dataType: 'json',
        data: {
            show_id: showId,
            rating: rating,
            csrf_token: $('input[name="csrf_token"]').val()
        },
        success: function(response) {
            if (response.success) {
                input.data('original-value', rating);
                showSuccess('Ocena została zaktualizowana!');
                
                // Zaktualizuj wyświetlanie gwiazdek
                updateStarRating(input.closest('.rating-container'), rating);
            } else {
                showError(response.message || 'Błąd podczas aktualizacji oceny');
                input.val(originalValue);
            }
        },
        error: function() {
            showError('Błąd połączenia. Spróbuj ponownie.');
            input.val(originalValue);
        }
    });
}

/**
 * Przełącz status odcinka (obejrzany/nieobejrzany)
 * @param {number} episodeId - ID odcinka
 * @param {boolean} watched - Czy odcinek obejrzany
 * @param {jQuery} checkbox - Checkbox
 */
function toggleEpisode(episodeId, watched, checkbox) {
    $.ajax({
        url: 'ajax/toggle-episode.php',
        type: 'POST',
        dataType: 'json',
        data: {
            episode_id: episodeId,
            watched: watched ? 1 : 0,
            csrf_token: $('input[name="csrf_token"]').val()
        },
        success: function(response) {
            if (response.success) {
                // Animacja sukcesu
                const episodeRow = checkbox.closest('.episode-row');
                if (watched) {
                    episodeRow.addClass('watched');
                } else {
                    episodeRow.removeClass('watched');
                }
                
                // Zaktualizuj licznik postępu
                updateProgressBar();
                updateUserStats();
            } else {
                showError(response.message || 'Błąd podczas aktualizacji odcinka');
                checkbox.prop('checked', !watched);
            }
        },
        error: function() {
            showError('Błąd połączenia. Spróbuj ponownie.');
            checkbox.prop('checked', !watched);
        }
    });
}

/**
 * Inicjalizacja walidacji formularzy
 */
function initializeFormValidation() {
    // Walidacja formularza dodawania serialu
    $('#addShowForm').on('submit', function(e) {
        if (!validateAddShowForm()) {
            e.preventDefault();
        }
    });
    
    // Walidacja formularza logowania
    $('#loginForm').on('submit', function(e) {
        if (!validateLoginForm()) {
            e.preventDefault();
        }
    });
    
    // Walidacja formularza rejestracji
    $('#registerForm').on('submit', function(e) {
        if (!validateRegisterForm()) {
            e.preventDefault();
        }
    });
    
    // Real-time walidacja
    $('.form-control').on('blur', function() {
        validateField($(this));
    });
}

/**
 * Walidacja formularza dodawania serialu
 * @return {boolean}
 */
function validateAddShowForm() {
    let isValid = true;
    
    // Sprawdź tytuł
    const title = $('#title');
    if (title.val().trim().length < 2) {
        showFieldError(title, 'Tytuł musi mieć co najmniej 2 znaki');
        isValid = false;
    } else {
        clearFieldError(title);
    }
    
    // Sprawdź opis
    const description = $('#description');
    if (description.val().trim().length < 10) {
        showFieldError(description, 'Opis musi mieć co najmniej 10 znaków');
        isValid = false;
    } else {
        clearFieldError(description);
    }
    
    // Sprawdź rok
    const year = $('#year');
    const yearValue = parseInt(year.val());
    const currentYear = new Date().getFullYear();
    
    if (yearValue < 1900 || yearValue > currentYear + 5) {
        showFieldError(year, `Rok musi być między 1900 a ${currentYear + 5}`);
        isValid = false;
    } else {
        clearFieldError(year);
    }
    
    // Sprawdź plik (jeśli został wybrany)
    const poster = $('#poster');
    const file = poster[0].files[0];
    
    if (file) {
        // Sprawdź typ pliku
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            showFieldError(poster, 'Dozwolone są tylko pliki JPG, PNG i GIF');
            isValid = false;
        }
        
        // Sprawdź rozmiar pliku (5MB max)
        else if (file.size > 5 * 1024 * 1024) {
            showFieldError(poster, 'Plik nie może być większy niż 5MB');
            isValid = false;
        }
        
        else {
            clearFieldError(poster);
        }
    }
    
    return isValid;
}

/**
 * Walidacja formularza logowania
 * @return {boolean}
 */
function validateLoginForm() {
    let isValid = true;
    
    const username = $('#username');
    const password = $('#password');
    
    if (username.val().trim().length < 3) {
        showFieldError(username, 'Nazwa użytkownika musi mieć co najmniej 3 znaki');
        isValid = false;
    } else {
        clearFieldError(username);
    }
    
    if (password.val().length < 6) {
        showFieldError(password, 'Hasło musi mieć co najmniej 6 znaków');
        isValid = false;
    } else {
        clearFieldError(password);
    }
    
    return isValid;
}

/**
 * Walidacja formularza rejestracji
 * @return {boolean}
 */
function validateRegisterForm() {
    let isValid = true;
    
    const username = $('#username');
    const email = $('#email');
    const password = $('#password');
    const confirmPassword = $('#confirm_password');
    
    // Walidacja nazwy użytkownika
    if (username.val().trim().length < 3) {
        showFieldError(username, 'Nazwa użytkownika musi mieć co najmniej 3 znaki');
        isValid = false;
    } else if (!/^[a-zA-Z0-9_]+$/.test(username.val())) {
        showFieldError(username, 'Nazwa użytkownika może zawierać tylko litery, cyfry i podkreślenia');
        isValid = false;
    } else {
        clearFieldError(username);
    }
    
    // Walidacja email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.val())) {
        showFieldError(email, 'Podaj prawidłowy adres email');
        isValid = false;
    } else {
        clearFieldError(email);
    }
    
    // Walidacja hasła
    if (password.val().length < 6) {
        showFieldError(password, 'Hasło musi mieć co najmniej 6 znaków');
        isValid = false;
    } else {
        clearFieldError(password);
    }
    
    // Walidacja potwierdzenia hasła
    if (password.val() !== confirmPassword.val()) {
        showFieldError(confirmPassword, 'Hasła nie są identyczne');
        isValid = false;
    } else {
        clearFieldError(confirmPassword);
    }
    
    return isValid;
}

/**
 * Walidacja pojedynczego pola
 * @param {jQuery} field - Pole do walidacji
 */
function validateField(field) {
    const fieldName = field.attr('name');
    
    switch (fieldName) {
        case 'username':
            if (field.val().trim().length < 3) {
                showFieldError(field, 'Nazwa użytkownika musi mieć co najmniej 3 znaki');
            } else {
                clearFieldError(field);
            }
            break;
            
        case 'email':
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.val())) {
                showFieldError(field, 'Podaj prawidłowy adres email');
            } else {
                clearFieldError(field);
            }
            break;
            
        case 'password':
            if (field.val().length < 6) {
                showFieldError(field, 'Hasło musi mieć co najmniej 6 znaków');
            } else {
                clearFieldError(field);
            }
            break;
    }
}

/**
 * Pokaż błąd walidacji pola
 * @param {jQuery} field - Pole formularza
 * @param {string} message - Komunikat błędu
 */
function showFieldError(field, message) {
    clearFieldError(field);
    field.addClass('is-invalid');
    field.after(`<div class="form-error">${message}</div>`);
}

/**
 * Wyczyść błąd walidacji pola
 * @param {jQuery} field - Pole formularza
 */
function clearFieldError(field) {
    field.removeClass('is-invalid');
    field.next('.form-error').remove();
}

/**
 * Inicjalizacja tooltipów
 */
function initializeTooltips() {
    $('[data-tooltip]').each(function() {
        const $this = $(this);
        const title = $this.attr('data-tooltip');
        
        $this.on('mouseenter', function() {
            showTooltip($this, title);
        });
        
        $this.on('mouseleave', function() {
            hideTooltip();
        });
    });
}

/**
 * Pokaż tooltip
 * @param {jQuery} element - Element nad którym pokazać tooltip
 * @param {string} text - Tekst tooltipa
 */
function showTooltip(element, text) {
    const tooltip = $(`<div class="tooltip">${text}</div>`);
    $('body').append(tooltip);
    
    const elementOffset = element.offset();
    const elementWidth = element.outerWidth();
    const elementHeight = element.outerHeight();
    const tooltipWidth = tooltip.outerWidth();
    
    tooltip.css({
        top: elementOffset.top - tooltip.outerHeight() - 10,
        left: elementOffset.left + (elementWidth / 2) - (tooltipWidth / 2)
    });
    
    tooltip.fadeIn(200);
}

/**
 * Ukryj tooltip
 */
function hideTooltip() {
    $('.tooltip').fadeOut(200, function() {
        $(this).remove();
    });
}

/**
 * Inicjalizacja lazy loading obrazów
 */
function initializeImageLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback dla starszych przeglądarek
        images.forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
        });
    }
}

/**
 * Inicjalizacja obsługi uploadów plików
 */
function initializeFileUploads() {
    // Obsługa drag & drop
    $('.file-upload-area').on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });
    
    $('.file-upload-area').on('dragleave dragend', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });
    
    $('.file-upload-area').on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            const fileInput = $(this).find('.file-input')[0];
            fileInput.files = files;
            previewImage(fileInput);
        }
    });
}

/**
 * Podgląd obrazu przed uploadem
 * @param {HTMLInputElement} input 
 */
function previewImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Sprawdź rozmiar pliku (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showNotification('Plik jest zbyt duży. Maksymalny rozmiar to 5MB.', 'error');
            input.value = '';
            return;
        }
        
        // Sprawdź typ pliku
        if (!file.type.match('image.*')) {
            showNotification('Wybierz prawidłowy plik obrazu.', 'error');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#previewImg').attr('src', e.target.result);
            $('#imagePreview').show();
        };
        reader.readAsDataURL(file);
    }
}

/**
 * Usuwa podgląd obrazu
 */
function removePreview() {
    $('#imagePreview').hide();
    $('#previewImg').attr('src', '');
    $('.file-input').val('');
}

/**
 * Pokazuje powiadomienie
 * @param {string} message 
 * @param {string} type 
 */
function showNotification(message, type = 'info') {
    const notification = $(`
        <div class="notification notification-${type}">
            <div class="notification-content">
                <i class="fas fa-${getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="closeNotification(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `);
    
    $('body').append(notification);
    
    // Auto-hide po 5 sekundach
    setTimeout(() => {
        notification.fadeOut(() => notification.remove());
    }, 5000);
}

/**
 * Pobiera ikonę dla typu powiadomienia
 * @param {string} type 
 */
function getNotificationIcon(type) {
    switch(type) {
        case 'success': return 'check-circle';
        case 'error': return 'exclamation-circle';
        case 'warning': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}

/**
 * Zamyka powiadomienie
 * @param {HTMLElement} button 
 */
function closeNotification(button) {
    $(button).closest('.notification').fadeOut(function() {
        $(this).remove();
    });
}

/**
 * Aktualizuj kolor statusu
 * @param {jQuery} select - Element select
 * @param {string} status - Status
 */
function updateStatusColor(select, status) {
    select.removeClass('status-watching status-completed status-plan_to_watch status-dropped');
    select.addClass(`status-${status}`);
}

/**
 * Aktualizuj wyświetlanie gwiazdek
 * @param {jQuery} container - Kontener oceny
 * @param {number} rating - Ocena
 */
function updateStarRating(container, rating) {
    const stars = container.find('.rating-stars');
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;
    
    let starsHtml = '';
    
    for (let i = 1; i <= 5; i++) {
        if (i <= fullStars) {
            starsHtml += '<i class="fas fa-star"></i>';
        } else if (i === fullStars + 1 && hasHalfStar) {
            starsHtml += '<i class="fas fa-star-half-alt"></i>';
        } else {
            starsHtml += '<i class="far fa-star"></i>';
        }
    }
    
    stars.html(starsHtml);
}

/**
 * Aktualizuj pasek postępu
 */
function updateProgressBar() {
    const progressBars = $('.progress-bar');
    
    progressBars.each(function() {
        const $this = $(this);
        const watched = parseInt($this.data('watched'));
        const total = parseInt($this.data('total'));
        const percentage = total > 0 ? Math.round((watched / total) * 100) : 0;
        
        $this.find('.progress-fill').css('width', percentage + '%');
        $this.find('.progress-text').text(`${watched}/${total} (${percentage}%)`);
    });
}

/**
 * Aktualizuj statystyki użytkownika
 */
function updateUserStats() {
    $.ajax({
        url: 'ajax/get-user-stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('.user-stats').each(function() {
                    const $this = $(this);
                    $this.find('.total-shows').text(response.data.total_shows);
                    $this.find('.watching-shows').text(response.data.watching);
                    $this.find('.completed-shows').text(response.data.completed);
                    $this.find('.total-episodes').text(response.data.total_episodes);
                });
            }
        }
    });
}

/**
 * Pokaż overlay ładowania
 */
function showLoading() {
    $('#loadingOverlay').addClass('active');
}

/**
 * Ukryj overlay ładowania
 */
function hideLoading() {
    $('#loadingOverlay').removeClass('active');
}

/**
 * Pokaż komunikat sukcesu
 * @param {string} message - Treść komunikatu
 */
function showSuccess(message) {
    showFlashMessage('success', message);
}

/**
 * Pokaż komunikat błędu
 * @param {string} message - Treść komunikatu
 */
function showError(message) {
    showFlashMessage('error', message);
}

/**
 * Pokaż komunikat ostrzeżenia
 * @param {string} message - Treść komunikatu
 */
function showWarning(message) {
    showFlashMessage('warning', message);
}

/**
 * Pokaż komunikat informacyjny
 * @param {string} message - Treść komunikatu
 */
function showInfo(message) {
    showFlashMessage('info', message);
}

/**
 * Pokaż komunikat flash
 * @param {string} type - Typ komunikatu
 * @param {string} message - Treść komunikatu
 */
function showFlashMessage(type, message) {
    const iconClass = type === 'success' ? 'check-circle' : 
                     (type === 'error' ? 'exclamation-circle' : 
                      (type === 'warning' ? 'exclamation-triangle' : 'info-circle'));
    
    const flashMessage = $(`
        <div class="flash-message flash-${type}">
            <i class="fas fa-${iconClass}"></i>
            <span>${message}</span>
            <button class="flash-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `);
    
    $('.flash-messages').append(flashMessage);
    
    // Auto-hide po 5 sekundach
    setTimeout(function() {
        flashMessage.fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);
}

/**
 * Generuj tymczasowy CSRF token (fallback)
 */
function generateTempCSRFToken() {
    // Fallback - spróbuj pobrać z meta tag lub hidden input
    const metaToken = $('meta[name="csrf-token"]').attr('content');
    const hiddenToken = $('input[name="csrf_token"]').val();
    
    return metaToken || hiddenToken || '';
}

/**
 * Inicjalizacja przycisków szybkiego dodawania
 */
function initializeQuickAddButtons() {
    $(document).on('click', '.quick-add-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const button = $(this);
        const showId = parseInt(button.data('show-id'));
        const status = button.data('status');
        
        if (!showId || !status) {
            showError('Nieprawidłowe dane serialu');
            return;
        }
        
        quickAddToList(showId, status, button);
    });
}

/**
 * Efekt hover dla kart seriali
 */
function initializeShowCardEffects() {
    $('.show-card').hover(
        function() {
            $(this).addClass('show-card-hover');
        },
        function() {
            $(this).removeClass('show-card-hover');
        }
    );
}
