<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="public/img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/8fd9367667.js" crossorigin="anonymous"></script>
    <link href="public/styles/main.css" rel="stylesheet">
    <link href="public/styles/dashboard.css" rel="stylesheet">
    <link href="public/styles/navbar.css" rel="stylesheet">
    <title>HANDEL | <?= htmlspecialchars($symbol ?? 'Aktywo') ?></title>
</head>
<body>
    <nav class="main-nav">
        <div class="nav-logo">
            <img src="public/img/logo.png" alt="Logo" class="main-logo">
        </div>
        <ul class="desktop-icons">
            <li><a href="dashboard"><i class="fa-solid fa-house"></i> <span>HOME</span></a></li>
            <li><a href="market"><i class="fa-solid fa-chart-line"></i> <span>POWRÓT DO RYNKU</span></a></li>
            <li class="balance-item">
                <i class="fa-solid fa-coins" style="color: #00d2ff;"></i>
                <span style="font-weight: bold; color: #00d2ff; margin-left: 5px;">
                    $<?= number_format($balance ?? 151401, 2, '.', ',') ?>
                </span>
            </li>
        </ul>
    </nav>

    <main style="display: flex; justify-content: center; align-items: flex-start; min-height: 80vh; padding-top: 50px;">
        <div class="card" style="max-width: 500px; width: 100%; padding: 40px; text-align: left;">

            <?php if (isset($_SESSION['error'])): ?>
                <div style="background: rgba(255, 68, 68, 0.2); border: 1px solid #ff4444; color: #ff4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <h1 style="margin-top: 0; font-size: 1.5rem; color: #fff;">Panel <?= $type === 'BUY' ? 'Zakupu' : 'Sprzedaży' ?></h1>
            <div style="font-size: 2.5rem; color: #00d2ff; margin-bottom: 20px; font-weight: bold;">
                <?= htmlspecialchars($symbol ?? '') ?>
            </div>

            <div class="trade-info" style="margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px;">
                <p style="margin: 5px 0;">Aktualna cena: <strong id="price-display" style="color: #fff;">$<?= number_format($price, 2) ?></strong></p>
                <p style="margin: 5px 0;">Twój portfel: <strong style="color: #fff;">$<?= number_format($balance, 2) ?></strong></p>
                <?php if($type === 'SELL'): ?>
                    <p style="margin: 5px 0;">Dostępne jednostki: <strong style="color: #00d2ff;"><?= number_format($ownedAmount ?? 0, 4) ?></strong></p>
                <?php endif; ?>
            </div>

            <form action="executeTrade" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="symbol" value="<?= htmlspecialchars($symbol ?? '') ?>">
                <input type="hidden" name="price" id="price-hidden" value="<?= (float)$price ?>">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type ?? '') ?>">

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px; color: #888; font-size: 0.9rem;">Ilość jednostek:</label>
                    <input type="number" name="amount" id="amount-input" step="1" min="1" 
                        <?php if($type === 'SELL'): ?> max="<?= (int)($ownedAmount ?? 0) ?>" <?php endif; ?>
                        value="1" required
                        style="width: 100%; padding: 15px; background: rgba(0,0,0,0.3); border: 1px solid #444; color: white; border-radius: 8px; font-size: 1.5rem; font-weight: bold;">
                    
                    <div id="zero-error" style="color: #ff4444; font-size: 0.8rem; margin-top: 10px; display: none; font-weight: bold;">
                        <i class="fa-solid fa-triangle-exclamation"></i> Musisz podać ilość większą od 0!
                    </div>
                </div>

                <div id="summary-box" style="background: rgba(0,210,255,0.1); padding: 20px; border-radius: 12px; margin-bottom: 30px; border: 1px solid rgba(0,210,255,0.2);">
                    <p style="margin: 0; font-size: 0.8rem; color: #888; text-transform: uppercase;">Przewidywana wartość (USD):</p>
                    <p style="margin: 5px 0 0 0; font-size: 1.8rem; font-weight: bold; color: #00ff88;">
                        $ <span id="total-value">0.00</span>
                    </p>
                </div>

                <button type="submit" id="submit-btn" style="width: 100%; padding: 20px; font-size: 1.1rem; background: <?= $type === 'BUY' ? '#00d2ff' : '#ff4444' ?>; color: black; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; text-transform: uppercase;">
                    POTWIERDŹ <?= $type === 'BUY' ? 'ZAKUP' : 'SPRZEDAŻ' ?>
                </button>
            </form>
        </div>
    </main>

    <script>
        const amountInput = document.getElementById('amount-input');
        const totalValueSpan = document.getElementById('total-value');
        const priceHidden = document.getElementById('price-hidden'); 
        const submitBtn = document.getElementById('submit-btn');
        const zeroError = document.getElementById('zero-error');
        const summaryBox = document.getElementById('summary-box');
        
        const currentPrice = parseFloat(priceHidden.value) || 0;
        const userBalance = <?= (float)($balance ?? 0) ?>;
        const tradeType = <?= json_encode($type) ?>;

        const updateTotal = () => {
            let amount = parseInt(amountInput.value) || 0;
            const total = amount * currentPrice;

            if (amount <= 0) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = "0.3";
                zeroError.style.display = "block";
            } else if (tradeType === 'BUY' && total > userBalance) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = "0.3";
                zeroError.style.display = "none";
                summaryBox.style.background = "rgba(255, 68, 68, 0.1)";
                totalValueSpan.style.color = "#ff4444";
            } else {
                submitBtn.disabled = false;
                submitBtn.style.opacity = "1";
                zeroError.style.display = "none";
                summaryBox.style.background = "rgba(0,210,255,0.1)";
                totalValueSpan.style.color = "#00ff88";
            }

            totalValueSpan.innerText = total.toLocaleString('pl-PL', { minimumFractionDigits: 2 });
        };

        amountInput.addEventListener('input', updateTotal);
        updateTotal(); 
    </script>
</body>
</html>