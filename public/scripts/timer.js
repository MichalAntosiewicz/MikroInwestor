document.addEventListener('DOMContentLoaded', () => {
    const timerElement = document.getElementById('timer');
    let timeLeft = parseInt(timerElement.innerText);

    const countdown = setInterval(() => {
        timeLeft--;
        timerElement.innerText = timeLeft;

        if (timeLeft <= 0) {
            clearInterval(countdown);
            document.getElementById('refresh-icon').classList.add('fa-spin');
            window.location.reload();
        }
    }, 1000);
});