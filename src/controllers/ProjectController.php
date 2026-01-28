<?php

require_once 'AppController.php';
require_once __DIR__.'/../factories/MarketProviderFactory.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/TradeRepository.php';
require_once __DIR__.'/../repository/PortfolioRepository.php';

class ProjectController extends AppController {

    private $userRepository;
    private $marketService;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
        
        $user = $this->getLoggedInUser();
        // BINGO A1: Fabryka decyduje o źródle danych na podstawie preferencji usera
        $mode = $user ? $user->getMarketMode() : 'simulated';
        
        $this->marketService = MarketProviderFactory::getProvider($mode);
    }

    private function getLoggedInUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        return $this->userRepository->getUserById((int)$_SESSION['user_id']);
    }

    // Centralna metoda weryfikacji uprawnień (Wymóg: Uprawnienia użytkowników)
    private function verifyAdmin() {
        $user = $this->getLoggedInUser();
        if (!$user || $user->getRole() !== 'admin') {
            header("Location: dashboard");
            exit();
        }
        return $user;
    }

    private function getRefreshTime() {
        if (isset($_COOKIE['market_expire_time'])) {
            $remaining = $_COOKIE['market_expire_time'] - time();
            if ($remaining > 0) return $remaining;
        }

        if (!isset($_SESSION['last_market_sync'])) {
            $_SESSION['last_market_sync'] = time();
        }
        $elapsed = time() - $_SESSION['last_market_sync'];
        return 60 - ($elapsed % 60);
    }

    public function dashboard() {
        $user = $this->getLoggedInUser();
        if (!$user) {
            header("Location: login");
            exit();
        }

        $refresh_in = $this->getRefreshTime();
        $assets = $_SESSION['assets_cache'] ?? $this->marketService->getMarketData();

        require_once __DIR__.'/../repository/PortfolioRepository.php';
        $portfolioRepo = new PortfolioRepository();
        $userPortfolio = $portfolioRepo->getUserPortfolio($user->getId());

        $this->render('dashboard', [
            'assets' => $assets,
            'refresh_in' => $refresh_in,
            'balance' => $user->getBalance(),
            'user_assets' => $userPortfolio,
            'user' => $user 
        ]);
    }

    public function portfolio() {
        // ZAMIAST checkSession() używamy getLoggedInUser()
        $user = $this->getLoggedInUser();
        if (!$user) { 
            header("Location: login"); 
            exit(); 
        }
        
        require_once __DIR__.'/../repository/PortfolioRepository.php';
        $portfolioRepo = new PortfolioRepository();
        $raw_assets = $portfolioRepo->getUserPortfolio($user->getId());
        
        // BINGO: Używamy serwisu zainicjalizowanego przez Fabrykę w konstruktorze
        $marketData = $this->marketService->getMarketData();

        $user_assets = [];
        $total_portfolio_value = 0;

        foreach ($raw_assets as $asset) {
            $symbol = strtoupper(trim($asset['symbol'])); // Standaryzacja symbolu
            $amount = (float)$asset['amount'];
            $avgPrice = (float)$asset['avg_buy_price'];

            // KLUCZ: Resetujemy cenę rynkową dla każdego aktywa z osobna!
            $currentMarketPrice = 0; 

            foreach ($marketData as $m) {
                // Porównujemy symbole ignorując wielkość liter i spacje
                if (strtoupper(trim($m['symbol'])) === $symbol) {
                    $currentMarketPrice = (float)$m['price'];
                    break;
                }
            }

            // Jeśli cena rynkowa nadal wynosi 0 (nie znaleziono w marketData), 
            // ROI nie powinno być liczone
            if ($currentMarketPrice <= 0) {
                $currentValue = $amount * $avgPrice; // fallback do ceny zakupu
                $profitLoss = 0;
            } else {
                $currentValue = $amount * $currentMarketPrice;
                // Oblicz ROI
                $profitLoss = (($currentMarketPrice - $avgPrice) / $avgPrice) * 100;
                
                // Zaokrąglenie do 2 miejsc dla pewności w widoku
                $profitLoss = round($profitLoss, 2);
            }

            $total_portfolio_value += $currentValue;

            $user_assets[] = [
                'symbol' => $symbol,
                'amount' => $amount,
                'avg_buy_price' => $avgPrice,
                'current_value' => $currentValue,
                'profit_loss' => $profitLoss
            ];
        }
        

        $this->render('portfolio', [
            'user' => $user,
            'balance' => $user->getBalance(),
            'user_assets' => $user_assets,
            'total_value' => $total_portfolio_value
        ]);
    }

    public function admin_panel() {
        $user = $this->verifyAdmin();

        $this->render('admin_panel', [
            'users' => $this->userRepository->getAllUsers(),
            'user' => $user
        ]);
    }

    public function asset() {
        $user = $this->getLoggedInUser();
        if (!$user) { header("Location: login"); exit(); }

        $symbol = $_GET['symbol'] ?? null;
        if (!$symbol) { header("Location: market"); exit(); }

        $marketData = $this->marketService->getMarketData();
        $price = 0;
        foreach ($marketData as $asset) {
            if ($asset['symbol'] === $symbol) {
                $price = $asset['price'];
                break;
            }
        }

        $portfolioRepo = new PortfolioRepository();
        $userPortfolio = $portfolioRepo->getUserPortfolio($user->getId());
        $ownedAmount = 0;
        foreach ($userPortfolio as $item) {
            if ($item['symbol'] === $symbol) {
                $ownedAmount = $item['amount'];
                break;
            }
        }

        $this->render('asset_details', [
            'user' => $user,
            'symbol' => $symbol,
            'price' => $price,
            'owned_amount' => $ownedAmount,
            'balance' => $user->getBalance()
        ]);
    }

    public function assetData() {
        $symbol = $_GET['symbol'] ?? '';
        $period = $_GET['period'] ?? '1M';

        $rawHistory = $this->marketService->getHistory($symbol, $period);
        
        $labels = [];
        $values = [];

        if (isset($rawHistory['c']) && isset($rawHistory['t'])) {
            foreach ($rawHistory['t'] as $index => $timestamp) {
                $format = match($period) {
                    '1D' => 'H:i',
                    '1Y', '5Y' => 'm.Y',
                    default => 'd.m'
                };
                $labels[] = date($format, $timestamp);
                $values[] = $rawHistory['c'][$index];
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'labels' => $labels,
            'values' => $values
        ]);
        exit();
    }

    public function deleteUser() {
        $this->verifyAdmin();
        if ($this->isPost()) {
            $id = $_POST['user_id'];
            $this->userRepository->deleteUser((int)$id);
        }
        header("Location: admin_panel");
    }

    public function settings() {
        $user = $this->getLoggedInUser();
        if (!$user) { header("Location: login"); exit(); }

        if ($this->isPost()) {
            $mode = $_POST['market_mode'] ?? 'simulated';
            $this->userRepository->updateMarketMode($user->getId(), $mode);
            header("Location: settings");
            exit();
        }

        $this->render('settings', ['user' => $user]);
    }

    public function market() {
        $user = $this->getLoggedInUser();
        if (!$user) { header("Location: login"); exit(); }
        $refresh_in = $this->getRefreshTime();
        $assets = $_SESSION['assets_cache'] ?? $this->marketService->getMarketData();

        $this->render('market', [
            'assets' => $assets,
            'refresh_in' => $refresh_in,
            'balance' => $user->getBalance(),
            'user' => $user
        ]);
    }

    public function history() {
        $user = $this->getLoggedInUser();
        if (!$user) { header("Location: login"); exit(); }
        require_once __DIR__.'/../repository/TradeRepository.php';
        $tradeRepo = new TradeRepository();
        $history = $tradeRepo->getUserHistory($user->getId());

        $this->render('history', [
            'history' => $history,
            'balance' => $user->getBalance(),
            'user' => $user
        ]);
    }

    public function trade() {
        // ZAMIAST checkSession() używamy getLoggedInUser()
        $user = $this->getLoggedInUser();
        if (!$user) { 
            header("Location: login"); 
            exit(); 
        }

        $symbol = $_GET['symbol'] ?? 'BTC';
        $type = $_GET['type'] ?? 'BUY';

        // BINGO: Dane rynkowe z Fabryki
        $marketData = $this->marketService->getMarketData();

        // Znajdź cenę dla konkretnego symbolu
        $currentPrice = 0.0;
        foreach ($marketData as $asset) {
            if ($asset['symbol'] === $symbol) {
                $currentPrice = (float)$asset['price'];
                break;
            }
        }

        // Pobierz aktualny stan posiadania dla tego assetu
        require_once __DIR__.'/../repository/PortfolioRepository.php';
        $portfolioRepo = new PortfolioRepository();
        $userPortfolio = $portfolioRepo->getUserPortfolio($user->getId());
        
        $ownedAmount = 0.0;
        foreach ($userPortfolio as $item) {
            if ($item['symbol'] === $symbol) {
                $ownedAmount = (float)$item['amount'];
                break;
            }
        }

        $this->render('trade', [
            'user' => $user,
            'symbol' => $symbol,
            'type' => $type,
            'price' => $currentPrice,
            'balance' => $user->getBalance(),
            'ownedAmount' => $ownedAmount
        ]);
    }

    public function executeTrade() {
        if (!$this->isPost()) { header("Location: dashboard"); exit(); }
        $user = $this->getLoggedInUser();
        if (!$user) { header("Location: login"); exit(); }

        $type = $_POST['type'];
        $symbol = $_POST['symbol'];
        $amount = (float)$_POST['amount'];
        $price = (float)$_POST['price'];

        require_once __DIR__.'/../repository/TradeRepository.php';
        $tradeRepo = new TradeRepository();
        
        $success = ($type === 'BUY') 
            ? $tradeRepo->buy($user->getId(), $symbol, $amount, $price)
            : $tradeRepo->sell($user->getId(), $symbol, $amount, $price);

        if ($success) {
            header("Location: portfolio");
        } else {
            $_SESSION['error'] = "Błąd transakcji.";
            header("Location: market");
        }
        exit();
    }

    private function getCurrentPrice(string $symbol): float {
        foreach ($_SESSION['assets_cache'] ?? [] as $asset) {
            if ($asset['symbol'] === $symbol) return (float)$asset['price'];
        }
        return 0.0;
    }

    

    public function logout() {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        header("Location: login");
        exit();
    }
}