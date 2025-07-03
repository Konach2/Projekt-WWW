# TV Time Clone - Instrukcja Administratora

## 🚀 Funkcjonalności Projektu

### ✅ Zaimplementowane funkcje:

1. **Bezpieczne hasła (bcrypt)**
   - Wszystkie hasła są hashowane używając bcrypt
   - Domyślne konto admin: `admin` / `admin123`
   - Domyślne konto user: `user` / `user123`

2. **Upload zdjęć seriali**
   - Dodawanie plakatów przez panel admina
   - Obsługa drag & drop
   - Podgląd przed uploadem
   - Automatyczna walidacja (rozmiar, format)
   - Fallback na placeholder jeśli brak obrazu

3. **Panel administracyjny**
   - Dashboard ze statystykami
   - Zarządzanie serialami (dodawanie, edycja, usuwanie)
   - Lista użytkowników
   - Logowanie akcji admina

4. **Bezpieczeństwo**
   - CSRF tokeny we wszystkich formularzach
   - Walidacja danych po stronie serwera
   - Bezpieczne nazwy plików
   - Kontrola uprawnień

## 📁 Struktura plików

```
projekt/
├── admin/                      # Pliki panelu administracyjnego
│   ├── shows-add.php          # Dodawanie seriali
│   ├── shows-edit.php         # Edycja seriali
│   ├── shows-list.php         # Lista seriali
│   └── users-list.php         # Lista użytkowników
├── assets/
│   ├── css/style.css          # Style CSS z obsługą uploadów
│   ├── js/script.js           # JavaScript z drag & drop
│   ├── images/
│   │   └── placeholder-show.jpg # Placeholder dla seriali
│   └── uploads/
│       └── shows/             # Katalog na plakaty seriali
├── includes/
│   ├── functions.php          # Funkcje: bcrypt, upload, walidacja
│   ├── session.php           # Zarządzanie sesjami i CSRF
│   ├── header.php            # Header z linkiem do panelu admina
│   └── footer.php
├── config/
│   └── database.php          # Konfiguracja bazy danych
├── admin.php                 # Główny panel administracyjny
├── index.php                 # Strona główna z getImageUrl()
├── login.php                 # Logowanie z bcrypt
├── register.php              # Rejestracja z bcrypt
├── database-structure.sql    # Aktualna struktura bazy
└── update_passwords.php      # Migracja starych haseł
```

## 🔧 Jak używać panelu admina

### 1. Logowanie jako admin
- Przejdź na `login.php`
- Login: `admin`
- Hasło: `admin123`
- **✅ HASŁA ZOSTAŁY NAPRAWIONE** (2025-07-02)

### 2. Logowanie jako zwykły użytkownik
- Login: `user`  
- Hasło: `user123`

### 3. Dodawanie nowego serialu
1. Kliknij "Panel Admina" w menu
2. Wybierz "Dodaj serial" lub "Lista seriali" → "Dodaj nowy"
3. Wypełnij formularz:
   - Tytuł (wymagane)
   - Opis (wymagane)
   - Gatunek (wybierz z listy)
   - Rok premiery (wymagane)
   - Plakat (opcjonalne) - przeciągnij lub kliknij by wybrać

### 4. Upload obrazów
- Obsługiwane formaty: JPG, PNG, WebP, GIF
- Maksymalny rozmiar: 5MB
- Optymalne wymiary: 300x450 pikseli
- Drag & drop lub kliknięcie w obszar upload
- Podgląd przed zapisaniem

### 5. Zarządzanie serialami
- Lista wszystkich seriali z miniaturkami
- Edycja wszystkich danych
- Usuwanie z potwierdzeniem
- Zmiana plakatu

## 🛠️ Funkcje techniczne

### Bezpieczeństwo uploadów:
```php
// Walidacja pliku
- Sprawdzenie typu MIME
- Sprawdzenie rozszerzenia
- Sprawdzenie rozmiaru (max 5MB)
- Generowanie bezpiecznej nazwy
- Ochrona przed php injection
```

### Obsługa obrazów:
```php
// Funkcja getImageUrl()
// Automatyczny fallback na placeholder
$image_url = getImageUrl($show['poster_url']);
```

### CSRF Protection:
```php
// W każdym formularzu
csrfTokenField(); // Generuje ukryte pole
verifyCSRFToken($_POST['csrf_token']); // Weryfikacja
```

## 📋 Lista kontrolna

### ✅ Gotowe:
- [x] Hasła bcrypt (admin i użytkownicy)
- [x] Upload zdjęć z walidacją
- [x] Panel admina z pełną funkcjonalnością
- [x] Linkowanie obrazów z fallbackiem
- [x] Bezpieczne formularze z CSRF
- [x] Responsywny design uploadów
- [x] Drag & drop interface
- [x] Podgląd obrazów
- [x] Logowanie akcji admina
- [x] Walidacja danych

### 🔄 Do zaimplementowania (TVTime Clone):
- [ ] **Onboarding proces (3 kroki)** - wybór seriali/filmów
- [ ] **Oddzielne sekcje Shows/Movies** - kompletnie nowy interfejs
- [ ] **Watch List z tracking odcinków** - lista "do obejrzenia"
- [ ] **Episode tracking** - automatyczne przechodzenie do kolejnego odcinka
- [ ] **Platformy streamingowe** - linki "Where to watch"
- [ ] **Rating system** - oceny odcinków i seriali
- [ ] **Explore page** - odkrywanie nowych treści
- [ ] **User Profile** - statystyki użytkownika
- [ ] **Responsive design** - wygląd identyczny z TVTime
- [ ] **Ciemny motyw** - charakterystyczny dla TVTime

### 📝 Struktura bazy do przebudowy:
- [ ] Dodanie tabeli `movies` (oddzielnie od seriali)
- [ ] Tabela `streaming_platforms` (Netflix, Disney+, etc.)
- [ ] Tabela `user_episode_progress` (tracking odcinków)
- [ ] Rozszerzenie `user_shows` o status watching
- [ ] Tabela `onboarding_steps` dla procesu rejestracji

## 🚨 Ważne uwagi

1. **Uprawnienia katalogów:**
   ```bash
   chmod 755 assets/uploads/shows/
   chmod 755 logs/
   ```

2. **Produkcja:**
   - Zmień hasło admina
   - Ustaw `session.cookie_secure = 1` dla HTTPS
   - Skonfiguruj limits PHP dla uploadów

3. **Backup:**
   - Regularnie rób kopie bazy danych
   - Backup katalogu uploads/

## 📞 Pomoc techniczna

Jeśli masz problemy:
1. Sprawdź logi w katalogu `logs/`
2. Użyj `test_admin.php` do diagnostyki
3. Sprawdź uprawnienia katalogów
4. Zweryfikuj konfigurację PHP (upload_max_filesize)

---
**Projekt gotowy do użycia! 🎉**
