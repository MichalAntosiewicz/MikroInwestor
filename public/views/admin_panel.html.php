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
    <link href="public/styles/tables.css" rel="stylesheet">
    
    <title>MikroInwestor | Admin Panel</title>
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
                    <a href="admin_panel" style="color: #ff4b2b; font-weight: bold;">
                        <i class="fa-solid fa-user-shield"></i> 
                        <span>ADMIN PANEL</span>
                    </a>
                </li>
            <?php endif; ?>

            <li><a href="logout"><i class="fa-solid fa-right-from-bracket"></i> <span>WYLOGUJ</span></a></li>
            <li class="balance-item">
                <i class="fa-solid fa-coins" style="color: #00d2ff;"></i>
                <span style="font-weight: bold; color: #00d2ff; margin-left: 5px;">
                    $<?= number_format($user->getBalance() ?? 151401, 2, '.', ',') ?>
                </span>
            </li>
        </ul>
    </nav>

    <main class="admin-container" style="padding: 40px;">
        <div class="market-header" style="margin-bottom: 30px;">
            <h1>Zarządzanie <span style="color: #ff4b2b;">Użytkownikami</span></h1>
            <p style="color: #888;">Lista wszystkich zarejestrowanych inwestorów w systemie.</p>
        </div>

        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Rola</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($users)): foreach($users as $u): ?>
                    <tr>
                        <td><?= (int)$u->getId() ?></td>
                        <td><strong><?= htmlspecialchars($u->getUsername()) ?></strong></td>
                        <td><?= htmlspecialchars($u->getEmail()) ?></td>
                        <td>
                            <span class="status-pill <?= $u->getRole() === 'admin' ? 'buy' : '' ?>" style="font-size: 0.7rem; padding: 3px 8px;">
                                <?= strtoupper($u->getRole()) ?>
                            </span>
                        </td>
                        <td>
                            <?php if($u->getRole() !== 'admin'): ?>
                                <form action="deleteUser" method="POST" onsubmit="return confirm('Czy na pewno chcesz usunąć tego użytkownika?');">
                                    <input type="hidden" name="user_id" value="<?= $u->getId() ?>">
                                    <button type="submit" class="btn-trade sell" style="padding: 5px 15px; font-size: 0.7rem;">USUŃ</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #555; font-size: 0.7rem;">BRAK AKCJI</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>