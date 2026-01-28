<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="public/img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;700&display=swap" rel="stylesheet">
    
    <script src="public/scripts/global_timer.js" defer></script>
    <script src="https://kit.fontawesome.com/8fd9367667.js" crossorigin="anonymous"></script>
    
    <link href="public/styles/main.css" rel="stylesheet">
    <link href="public/styles/navbar.css" rel="stylesheet">
    <link href="public/styles/dashboard.css" rel="stylesheet">
    <link href="public/styles/tables.css" rel="stylesheet">
    
    <title>MikroInwestor | Rynek Aktywów</title>
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

            <div class="market-title-box" style="background: rgba(255, 255, 255, 0.02); padding: 20px 30px; border-radius: 15px; border: 1px solid rgba(255, 255, 255, 0.05);">
                <h1 style="margin: 0; font-size: 1.8rem;">Eksploruj <span style="color: #00d2ff;">Rynek</span></h1>
                <p style="color: #666; font-size: 0.9rem; margin-top: 5px;">Wybierz aktywa i zacznij budować swoją historię inwestycyjną.</p>
            </div>

            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Aktywo</th>
                            <th>Cena (USD)</th>
                            <th style="text-align: center;">Zmiana</th>
                            <th style="text-align: right;">Handel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($assets)): ?>
                            <?php foreach($assets as $asset): ?>
                                <tr>
                                    <td>
                                        <div class="symbol-tag" onclick="location.href='asset?symbol=<?= htmlspecialchars(urlencode($asset['symbol'] ?? '')) ?>'" style="cursor:pointer;">
                                            <i class="fa-solid fa-chart-simple" style="font-size: 0.8rem; color: #444;"></i>
                                            <strong><?= htmlspecialchars($asset['symbol'] ?? '') ?></strong>
                                        </div>
                                    </td>
                                    <td style="font-weight: bold; font-size: 1.1rem;">$<?= number_format($asset['price'], 2) ?></td>
                                    <td style="text-align: center;">
                                        <span class="status-pill <?= $asset['change'] >= 0 ? 'positive' : 'negative' ?>">
                                            <i class="fa-solid fa-caret-<?= $asset['change'] >= 0 ? 'up' : 'down' ?>"></i>
                                            <?= number_format(abs($asset['change']), 2) ?>%
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <a href="trade?symbol=<?= htmlspecialchars(urlencode($asset['symbol'] ?? '')) ?>&type=BUY" class="btn-trade buy" style="padding: 8px 20px; font-size: 0.75rem;">KUP</a>
                                            <a href="trade?symbol=<?= htmlspecialchars(urlencode($asset['symbol'] ?? '')) ?>&type=SELL" class="btn-trade sell" style="padding: 8px 20px; font-size: 0.75rem;">SPRZEDAJ</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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