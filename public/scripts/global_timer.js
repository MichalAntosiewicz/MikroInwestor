document.addEventListener('DOMContentLoaded', () => {
    const timerSpan = document.getElementById('timer');
    if (!timerSpan) return;

    const getCookie = (name) => {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    };

    let targetTimestamp = getCookie('market_expire_time');

    if (!targetTimestamp) {
        const secondsFromPhp = parseInt(timerSpan.innerText);
        targetTimestamp = Math.floor(Date.now() / 1000) + secondsFromPhp;
        document.cookie = `market_expire_time=${targetTimestamp}; path=/; max-age=60`;
    }

    const updateTimer = () => {
        const now = Math.floor(Date.now() / 1000);
        let timeLeft = targetTimestamp - now;

        if (timeLeft <= 0) {
            document.cookie = "market_expire_time=; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC;";
            location.reload();
            return;
        }

        timerSpan.innerText = timeLeft;
    };

    setInterval(updateTimer, 1000);
    updateTimer();
});