document.addEventListener('DOMContentLoaded', () => {
    const timerSpan = document.getElementById('timer');
    if (!timerSpan) return;

    const getCookie = (name) => {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    };

    // 1. Pobieramy timestamp wygaśnięcia z ciasteczka
    let targetTimestamp = getCookie('market_expire_time');

    // 2. Jeśli ciasteczka nie ma (pierwsze wejście), tworzymy je na 60s
    if (!targetTimestamp) {
        targetTimestamp = Math.floor(Date.now() / 1000) + 60;
        document.cookie = `market_expire_time=${targetTimestamp}; path=/; max-age=60`;
    }

    const updateTimer = () => {
        const now = Math.floor(Date.now() / 1000);
        let timeLeft = targetTimestamp - now;

        // 3. Jeśli czas minął (0 lub mniej)
        if (timeLeft <= 0) {
            timerSpan.innerText = "0";
            // Dodajemy efekt kręcenia ikonką (jeśli masz ją w HTML)
            const refreshIcon = document.getElementById('refresh-icon');
            if (refreshIcon) refreshIcon.classList.add('fa-spin');
            
            // Usuwamy ciasteczko, żeby PHP wygenerowało nowe ceny po przeładowaniu
            document.cookie = "market_expire_time=; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC;";
            
            // Przeładowujemy stronę
            location.reload();
            return;
        }

        // 4. Aktualizujemy widok tylko raz na sekundę
        timerSpan.innerText = timeLeft;
    };

    // Uruchamiamy interwał
    const timerInterval = setInterval(() => {
        updateTimer();
    }, 1000);

    // Wywołujemy od razu, żeby nie było widać opóźnienia przy starcie
    updateTimer();
});