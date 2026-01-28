<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="public/img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    
    <script src="public/scripts/global_timer.js" defer></script>
    <script src="https://kit.fontawesome.com/8fd9367667.js" crossorigin="anonymous"></script>
    
    <link href="public/styles/main.css" rel="stylesheet">
    <link href="public/styles/navbar.css" rel="stylesheet">
    <link href="public/styles/dashboard.css" rel="stylesheet">
    
    <title>MikroInwestor | Dashboard</title>
</head>
<body>
    <nav class="main-nav">
        <div class="nav-logo">
            <img src="public/img/logo.png" alt="Logo" class="main-logo">
        </div>
        <ul class="desktop-icons">
            <li><a href="dashboard"><i class="fa-solid fa-house"></i> <span>STRONA GŁÓWNA</span></a></li>
            <li><a href="portfolio"><i class="fa-solid fa-wallet"></i> <span>PORTFEL</span></a></li>
            <li><a href="market"><i class="fa-solid fa-chart-line"></i> <span>RYNEK</span></a></li>
            <li><a href="history"><i class="fa-solid fa-history"></i> <span>HISTORIA</span></a></li>
            
            <?php if(isset($user) && $user->getRole() === 'admin'): ?>
                <li>
                    <a href="admin_panel" class="admin-link">
                        <i class="fa-solid fa-user-shield"></i> 
                        <span>ADMIN PANEL</span>
                    </a>
                </li>
            <?php endif; ?>
            <li><a href="logout"><i class="fa-solid fa-right-from-bracket"></i> <span>WYLOGUJ</span></a></li>
            <li class="balance-item">
                <i class="fa-solid fa-coins" style="color: #00d2ff;"></i>
                <span style="font-weight: bold; color: #00d2ff; margin-left: 5px;">
                    $<?= number_format($balance ?? 151401, 2, '.', ',') ?>
                </span>
            </li>
        </ul>
    </nav>

    <main>
        <div class="content-left">
            <div class="market-header">
                <div class="local-timer">
                    <i class="fa-solid fa-arrows-rotate" id="refresh-icon"></i>
                    Rynek odświeży się za: <span id="timer"><?= (int)($refresh_in ?? 60) ?></span>s
                </div>
                <div style="color: #666; font-size: 0.7rem; letter-spacing: 2px; font-weight: bold;">
                    <i class="fa-solid fa-circle" style="color: #00ff88; font-size: 0.5rem; margin-right: 5px;"></i>
                    NASDAQ / NYSE SIM
                </div>
            </div>

            <section class="assets-grid">
                <?php if(isset($assets)): ?>
                    <?php foreach($assets as $asset): ?>
                        <div class="card">
                            <div onclick="location.href='asset?symbol=<?= htmlspecialchars(urlencode($asset['symbol'] ?? '')) ?>'" style="cursor: pointer;">
                                <div class="card-header">
                                    <i class="fa-solid fa-chart-line"></i>
                                    <strong><?= htmlspecialchars($asset['symbol'] ?? '') ?></strong>
                                </div>
                                <p class="price">$<?= number_format((float)$asset['price'], 2) ?></p>
                                <p class="change <?= $asset['change'] >= 0 ? 'positive' : 'negative' ?>">
                                    <?= $asset['change'] >= 0 ? '+' : '' ?><?= (float)$asset['change'] ?>%
                                </p>
                            </div>
                            <div class="trade-buttons">
                                <a href="trade?symbol=<?= htmlspecialchars(urlencode($asset['symbol'] ?? '')) ?>&type=BUY" class="btn-trade buy">KUP</a>
                                <?php 
                                    $userHasAsset = false;
                                    if(isset($user_assets)) {
                                        foreach($user_assets as $ua) {
                                            if($ua['symbol'] == $asset['symbol'] && (float)$ua['amount'] > 0) {
                                                $userHasAsset = true;
                                                break;
                                            }
                                        }
                                    }
                                ?>
                                <a href="trade?symbol=<?= htmlspecialchars(urlencode($asset['symbol'] ?? '')) ?>&type=SELL" 
                                   class="btn-trade sell <?= !$userHasAsset ? 'disabled' : '' ?>">
                                     SPRZEDAJ
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>

        <div class="risk-notice">
            <h3 style="color: #00d2ff; margin-top: 0; font-size: 1.1rem;"><i class="fa-solid fa-shield-halved"></i> Security & Simulation</h3>
            <p style="font-size: 0.9rem; line-height: 1.6; color: #bbb;">
                Witaj w symulatorze <strong>MikroInwestor</strong>.
            </p>
            <p style="font-size: 0.85rem; line-height: 1.5; color: #888;">
                Wszystkie środki widoczne na Twoim koncie ($) oraz zakupione aktywa są <strong>całkowicie fikcyjne</strong>.
                Służą one wyłącznie celom edukacyjnym i testowym w ramach projektu studenckiego.
            </p>
            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 15px 0;">
            <p style="font-size: 0.8rem; color: #666; font-style: italic;">
                Inwestowanie na prawdziwych rynkach wiąże się z ryzykiem utraty kapitału. Nie traktuj danych z tej aplikacji jako porad inwestycyjnych.
            </p>
        </div>
    </main>
</body>
</html>