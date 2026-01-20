document.addEventListener('DOMContentLoaded', () => {
    const timerSpan = document.getElementById('timer');
    if (!timerSpan) return;

    // Funkcja pobierająca wartość ciasteczka
    const getCookie = (name) => {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    };

    // 1. Sprawdzamy, czy mamy już zapisaną datę wygaśnięcia w ciasteczku
    let targetTimestamp = getCookie('market_expire_time');

    if (!targetTimestamp) {
        // Jeśli nie ma (pierwsze wejście), obliczamy ją na podstawie tego, co wysłał PHP
        const secondsFromPhp = parseInt(timerSpan.innerText);
        targetTimestamp = Math.floor(Date.now() / 1000) + secondsFromPhp;
        // Zapisujemy w ciasteczku na 1 minutę
        document.cookie = `market_expire_time=${targetTimestamp}; path=/; max-age=60`;
    }

    const updateTimer = () => {
        const now = Math.floor(Date.now() / 1000);
        let timeLeft = targetTimestamp - now;

        if (timeLeft <= 0) {
            // Czyścimy ciasteczko przed reloadem, żeby PHP wygenerowało nową bazę
            document.cookie = "market_expire_time=; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC;";
            location.reload();
            return;
        }

        timerSpan.innerText = timeLeft;
    };

    // Odświeżaj co 1 sekundę, ale licz czas względem stałego punktu w przyszłości
    setInterval(updateTimer, 1000);
    updateTimer(); // Pierwsze wywołanie natychmiastowe
});