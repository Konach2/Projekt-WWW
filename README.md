\documentclass[12pt,a4paper]{article}
\usepackage[utf8]{inputenc}
\usepackage[T1]{fontenc}
\usepackage{lmodern}
\usepackage{geometry}
\geometry{margin=2.5cm}
\usepackage{hyperref}
\usepackage{graphicx}
\usepackage{enumitem}
\setlength{\parskip}{6pt}
\setlength{\parindent}{0pt}
\title{TV Time Clone -- Dokumentacja Projektu}
\author{Mateusz Konachowicz \\ I~rok~informatyki,~studia~stacjonarne~inżynierskie \\ Uniwersytet w~Siedlcach \\ Wydział Nauk Ścisłych i~Przyrodniczych}
\date{Siedlce,~rok~akademicki~2024/2025,~semestr~letni}

\begin{document}
\maketitle
\thispagestyle{empty}
\newpage

\tableofcontents
\newpage

\section{Cel projektu}
Celem projektu było stworzenie nowoczesnej aplikacji webowej pozwalającej na zarządzanie osobistą listą seriali i~filmów, inspirowanej popularną platformą \emph{TV Time}. Użytkownicy mogą śledzić swoje postępy w~oglądaniu, dodawać filmy do listy życzeń, oceniać oraz pisać recenzje. System powstał z~myślą o~bezpieczeństwie danych, intuicyjnej obsłudze i~możliwości dalszej rozbudowy.

Projekt wykonano \textbf{samodzielnie} -- od projektowania bazy danych, przez implementację backendu w~PHP, po stworzenie responsywnego interfejsu użytkownika. Zastosowano nowoczesne rozwiązania~UI/UX, bezpieczne zarządzanie danymi oraz zaawansowane funkcjonalności administracyjne.

\section{Link do~GitHuba}
Projekt dostępny jest pod adresem: \url{https://github.com/[username]/tvtime-clone}

\section{Spis funkcjonalności}
\subsection{Funkcje użytkownika standardowego}
\begin{itemize}[leftmargin=*]
  \item Rejestracja i~logowanie (hashowanie haseł \emph{bcrypt}).
  \item Przeglądanie bazy seriali i~filmów z~filtrowaniem według gatunków.
  \item Dodawanie pozycji do~własnej listy z~różnymi statusami (oglądane, chcę obejrzeć, ukończone, porzucone, wstrzymane).
  \item Ocenianie w~skali 1--10.
  \item Pisanie recenzji z~opcją oznaczania spoilerów.
  \item Podgląd statystyk i~profilu użytkownika.
  \item System powiadomień o~aktualizacjach.
  \item Zaawansowana wyszukiwarka treści.
  \item Szczegółowe karty serialu i~filmu wraz z~sezonami i~odcinkami.
\end{itemize}
\subsection{Uprawnienia administratora}
\begin{itemize}[leftmargin=*]
  \item Dodawanie, edycja i~usuwanie seriali oraz filmów.
  \item Zarządzanie kontami użytkowników i~rolami.
  \item Moderacja recenzji i~treści użytkowników.
  \item Panel administracyjny z~rozbudowanymi statystykami.
\end{itemize}

\section{Prezentacja działania}
W tej sekcji należy umieścić zrzuty ekranu prezentujące kluczowe funkcjonalności aplikacji.  
Poniżej znajduje się lista widoków do udokumentowania (obrazy wstaw w osobnym pliku):

\begin{enumerate}[leftmargin=*]
  \item Strona główna -- eksploracja treści (\texttt{explore.php}).
  \item Formularz logowania i~rejestracji (\texttt{login.php}, \texttt{register.php}).
  \item Modal dodawania do~listy.
  \item Panel ``Moje Filmy'' ze~statystykami.
  \item Karta serialu z~systemem recenzji.
  \item Formularz dodawania recenzji.
  \item Panel administracyjny oraz lista i~formularz dodawania serialu.
\end{enumerate}

\section{Diagram tabel}
% Wstaw diagram ER np. wygenerowany z~phpMyAdmin
\vspace{0.5cm}
\textbf{Główne tabele:}
\begin{itemize}[leftmargin=*]
  \item \texttt{users} -- dane użytkowników (hasła \emph{bcrypt})
  \item \texttt{shows}, \texttt{movies} -- informacje o~tytułach
  \item \texttt{user\_shows}, \texttt{user\_movies} -- relacje użytkownik--tytuł (status, ocena)
  \item \texttt{reviews} -- recenzje
  \item \texttt{seasons}, \texttt{episodes}, \texttt{user\_episodes}
\end{itemize}
Relacje zabezpieczone kluczami obcymi, indeksy oraz ograniczenia \texttt{CHECK} (ocena~1--10).

\section{Technologie użyte}
\begin{itemize}[leftmargin=*]
  \item Backend: PHP~8 (PDO)
  \item Baza danych: MySQL~8 (InnoDB)
  \item Frontend: HTML5, CSS3, JavaScript (ES6)
  \item Style: własne CSS (Flexbox, Grid, animacje)
  \item Bezpieczeństwo: \emph{bcrypt}, CSRF tokens, prepared statements
  \item Responsywność: podejście mobile--first, media queries
\end{itemize}

\section{Struktura plików projektu}
\begin{verbatim}
├── index.php
├── explore.php
├── movies.php
├── shows.php
├── login.php
├── register.php
├── admin/
│   ├── admin.php
│   └── ...
├── ajax/
│   └── *.php (8 plików)
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── sql/
    ├── structure.sql
    ├── movies.sql
    └── extensions.sql
\end{verbatim}

\section*{Kluczowe cechy techniczne}
\begin{itemize}[leftmargin=*]
  \item Nowoczesny modal zamiast klasycznych \texttt{alert()}.
  \item Recenzje z~ochroną spoilerów.
  \item Statystyki użytkownika aktualizowane w~czasie rzeczywistym.
  \item Walidacja formularzy po stronie klienta i~serwera.
  \item Optymalizacja obrazów funkcją \texttt{getImageUrl()}.
  \item Elastyczny system ról użytkowników.
\end{itemize}

\vfill
\begin{center}
\end{center}

\end{document}
