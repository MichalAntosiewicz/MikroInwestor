<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="public/img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;700&display=swap" rel="stylesheet">
    
    <script src="https://kit.fontawesome.com/8fd9367667.js" crossorigin="anonymous"></script>
    
    <link href="public/styles/main.css" rel="stylesheet">
    <link href="public/styles/navbar.css" rel="stylesheet">
    <link href="public/styles/dashboard.css" rel="stylesheet">
    <link href="public/styles/tables.css" rel="stylesheet">
    
    <title>MikroInwestor | Historia Transakcji</title>
</head>
<body>
    <nav class="main-nav">
        <div class="nav-logo"><img src="public/img/logo.png" alt="Logo" class="main-logo"></div>
        <ul class="desktop-icons">
            <li><a href="dashboard"><i class="fa-solid fa-house"></i> <span>STRONA GŁÓWNA</span></a></li>
            <li><a href="portfolio"><i class="fa-solid fa-wallet"></i> <span>PORTFEL</span></a></li>
            <li><a href="market"><i class="fa-solid fa-chart-line"></i> <span>RYNEK</span></a></li>
            <li><a href="history"><i class="fa-solid fa-history"></i> <span>HISTORIA</span></a></li>
            <li><a href="settings"><i class="fa-solid fa-gear"></i> <span>USTAWIENIA</span></a></li>
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
            <div class="history-title-box">
                <h1 style="margin: 0; font-size: 2rem;">Moja <span style="color: #00d2ff;">Historia</span></h1>
                <p style="color: #888; font-size: 0.95rem; margin-top: 5px;">Pełny log Twoich operacji giełdowych od momentu założenia konta.</p>
            </div>

            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Instrument</th>
                            <th style="text-align: center;">Typ</th>
                            <th style="text-align: center;">Ilość</th>
                            <th style="text-align: center;">Cena jedn.</th>
                            <th style="text-align: right;">Łącznie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($history)): ?>
                            <?php foreach($history as $t): ?>
                            <tr>
                                <td style="color: #666; font-size: 0.85rem;"><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></td>
                                <td>
                                    <div class="symbol-tag">
                                        <i class="fa-solid fa-receipt" style="font-size: 0.8rem; color: #444;"></i>
                                        <strong><?= htmlspecialchars($t['symbol'] ?? '') ?></strong>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="status-pill <?= $t['type'] === 'BUY' ? 'positive' : 'negative' ?>">
                                        <?= $t['type'] === 'BUY' ? 'KUPNO' : 'SPRZEDAŻ' ?>
                                    </span>
                                </td>
                                <td style="text-align: center; font-weight: 500;"><?= number_format((float)$t['amount'], 4) ?></td>
                                <td style="text-align: center; color: #aaa;">$<?= number_format((float)($t['price_per_unit'] ?? 0), 2) ?></td>
                                <td style="text-align: right; font-weight: bold; color: #00d2ff;">
                                    $<?= number_format(((float)$t['amount'] * (float)($t['price_per_unit'] ?? 0)), 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; padding: 100px; color: #444;">Brak zarejestrowanych transakcji.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="risk-notice">
            <h3 style="color: #00d2ff; margin-top: 0; font-size: 1.1rem;"><i class="fa-solid fa-shield-halved"></i> Security & Simulation</h3>
            <p style="font-size: 0.9rem; line-height: 1.6; color: #bbb;">Witaj w symulatorze <strong>MikroInwestor</strong>.</p>
            <p style="font-size: 0.85rem; line-height: 1.5; color: #888;">Wszystkie środki widoczne na Twoim koncie ($) oraz zakupione aktywa są <strong>całkowicie fikcyjne</strong>.</p>
            <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 15px 0;">
            <p style="font-size: 0.8rem; color: #666; font-style: italic;">Inwestowanie na prawdziwych rynkach wiąże się z ryzykiem utraty kapitału.</p>
        </div>
    </main>
</body>
</html>