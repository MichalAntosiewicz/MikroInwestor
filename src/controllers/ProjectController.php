<?php

require_once 'AppController.php';
require_once __DIR__.'/../factories/MarketProviderFactory.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/TradeRepository.php';

class ProjectController extends AppController {

    private $userRepository;
    private $marketService;

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

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
        
        $user = $this->getLoggedInUser();
        $mode = $user ? $user->getMarketMode() : 'simulated';
        
        $this->marketService = MarketProviderFactory::getProvider($mode);
    }

    private function getLoggedInUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        return $this->userRepository->getUserById((int)$_SESSION['user_id']);
    }

    public function dashboard() {
        $user = $this->getLoggedInUser();
        if (!$user) {
            header("Location: login");
            exit();
        }

        $refresh_in = $this->getRefreshTime();

        if (!isset($_SESSION['assets_cache']) || $refresh_in >= 60) {
            $assets = $this->marketService->getMarketData();
            $_SESSION['assets_cache'] = $assets;
        } else {
            $assets = $_SESSION['assets_cache'];
        }

        require_once __DIR__.'/../repository/PortfolioRepository.php';
        $portfolioRepo = new PortfolioRepository();
        $userPortfolio = $portfolioRepo->getUserPortfolio($user->getId());

        $this->render('dashboard', [
            'assets' => $assets,
            'refresh_in' => $refresh_in,
            'balance' => $user->getBalance(),
            'user_assets' => $userPortfolio,
        ]);
    }

    public function market() {
        $user = $this->getLoggedInUser();
        if (!$user) {
            header("Location: login");
            exit();
        }

        $refresh_in = $this->getRefreshTime();

        if (!isset($_SESSION['assets_cache']) || $refresh_in >= 60) {
            $assets = $this->marketService->getMarketData();
            $_SESSION['assets_cache'] = $assets;
        } else {
            $assets = $_SESSION['assets_cache'];
        }

        $this->render('market', [
            'assets' => $assets,
            'refresh_in' => $refresh_in,
            'balance' => $user->getBalance()
        ]);
    }

    public function history() {
        $user = $this->getLoggedInUser();
        if (!$user) {
            header("Location: login");
            exit();
        }

        require_once __DIR__.'/../repository/TradeRepository.php';
        $tradeRepo = new TradeRepository();
        $history = $tradeRepo->getUserHistory($user->getId());

        $this->render('history', [
            'history' => $history,
            'balance' => $user->getBalance()
        ]);
    }

    public function portfolio() {
        $user = $this->getLoggedInUser();
        if (!$user) {
            header("Location: login");
            exit();
        }

        require_once __DIR__.'/../repository/PortfolioRepository.php';
        $portfolioRepo = new PortfolioRepository();
        $raw_assets = $portfolioRepo->getUserPortfolio($user->getId());

        $marketData = $_SESSION['assets_cache'] ?? $this->marketService->getMarketData();

        $user_assets = [];
        $total_portfolio_value = 0;

        foreach ($raw_assets as $asset) {
            $symbol = $asset['symbol'];
            $amount = (float)$asset['amount'];
            $avgPrice = (float)$asset['avg_buy_price'];

            $currentMarketPrice = 0;
            foreach ($marketData as $m) {
                if ($m['symbol'] === $symbol) {
                    $currentMarketPrice = (float)$m['price'];
                    break;
                }
            }

            $currentValue = $amount * $currentMarketPrice;
            $total_portfolio_value += $currentValue;
            $profitLoss = ($avgPrice > 0) ? (($currentMarketPrice - $avgPrice) / $avgPrice) * 100 : 0;

            $user_assets[] = [
                'symbol' => $symbol,
                'amount' => $amount,
                'avg_buy_price' => $avgPrice,
                'current_value' => $currentValue,
                'profit_loss' => $profitLoss
            ];
        }

        $this->render('portfolio', [
            'user_assets' => $user_assets,
            'total_value' => $total_portfolio_value,
            'balance' => $user->getBalance(),
            'refresh_in' => $this->getRefreshTime()
        ]);
    }

    public function trade() {
        $user = $this->getLoggedInUser();
        if (!$user) {
            header("Location: login");
            exit();
        }

        unset($_SESSION['error']);
        unset($_SESSION['message']);
        
        $symbol = $_GET['symbol'] ?? null;
        $type = $_GET['type'] ?? 'BUY';

        if (!$symbol) {
            header("Location: dashboard");
            exit();
        }

        $price = $this->getCurrentPrice($symbol);

        require_once __DIR__.'/../repository/PortfolioRepository.php';
        $portfolioRepo = new PortfolioRepository();
        $userPortfolio = $portfolioRepo->getUserPortfolio($user->getId());

        $ownedAmount = 0;
        foreach ($userPortfolio as $item) {
            if ($item['symbol'] === $symbol) {
                $ownedAmount = (float)$item['amount'];
                break;
            }
        }

        $this->render('trade', [
            'symbol' => $symbol,
            'type' => $type,
            'price' => $price,
            'balance' => $user->getBalance(),
            'owned_amount' => $ownedAmount
        ]);
    }

    public function executeTrade() {
        if (!$this->isPost()) {
            header("Location: dashboard");
            exit();
        }

        $user = $this->getLoggedInUser();
        if (!$user) {
            header("Location: login");
            exit();
        }

        $type = $_POST['type'];
        $symbol = $_POST['symbol'];
        $amount = (float)$_POST['amount'];
        $price = (float)$_POST['price'];
        $totalCost = $amount * $price;

        if ($type === 'BUY' && $totalCost > $user->getBalance()) {
            $_SESSION['error'] = "Niewystarczające środki! Brakuje: $" . number_format($totalCost - $user->getBalance(), 2);
            header("Location: trade?symbol=$symbol&type=BUY");
            exit();
        }

        if ($type === 'SELL') {
            require_once __DIR__.'/../repository/PortfolioRepository.php';
            $portfolioRepo = new PortfolioRepository();
            $userPortfolio = $portfolioRepo->getUserPortfolio($user->getId());

            $ownedAmount = 0;
            foreach ($userPortfolio as $item) {
                if ($item['symbol'] === $symbol) {
                    $ownedAmount = (float)$item['amount'];
                    break;
                }
            }

            if ($amount > $ownedAmount) {
                $_SESSION['error'] = "Brak akcji! Masz: $ownedAmount";
                header("Location: trade?symbol=$symbol&type=SELL");
                exit();
            }
        }

        if ($amount <= 0) {
            $_SESSION['error'] = "Ilość musi być dodatnia!";
            header("Location: trade?symbol=$symbol&type=$type");
            exit();
        }

        require_once __DIR__.'/../repository/TradeRepository.php';
        $tradeRepo = new TradeRepository();
        
        $success = ($type === 'BUY') 
            ? $tradeRepo->buy($user->getId(), $symbol, $amount, $price)
            : $tradeRepo->sell($user->getId(), $symbol, $amount, $price);

        if ($success) {
            $_SESSION['message'] = "Sukces!";
            header("Location: portfolio");
        } else {
            $_SESSION['error'] = "Błąd transakcji.";
            header("Location: market");
        }
        exit();
    }

    public function asset() {
        $user = $this->getLoggedInUser();
        if (!$user) {
            header("Location: login");
            exit();
        }

        $symbol = $_GET['symbol'] ?? null;
        if (!$symbol) {
            header("Location: dashboard");
            exit();
        }

        $currentPrice = $this->getCurrentPrice($symbol);
        $historyData = [
            $currentPrice * 0.95, $currentPrice * 0.98, $currentPrice * 0.97, 
            $currentPrice * 1.02, $currentPrice * 0.99, $currentPrice * 1.01, $currentPrice
        ];

        require_once __DIR__.'/../repository/PortfolioRepository.php';
        $portfolioRepo = new PortfolioRepository();
        $userPortfolio = $portfolioRepo->getUserPortfolio($user->getId());

        $ownedAmount = 0;
        foreach ($userPortfolio as $item) {
            if ($item['symbol'] === $symbol) {
                $ownedAmount = (float)$item['amount'];
                break;
            }
        }

        $this->render('asset_details', [
            'symbol' => $symbol,
            'price' => $currentPrice,
            'balance' => $user->getBalance(),
            'owned_amount' => $ownedAmount,
            'history_data' => json_encode($historyData)
        ]);
    }

    private function getCurrentPrice(string $symbol): float {
        foreach ($_SESSION['assets_cache'] ?? [] as $asset) {
            if ($asset['symbol'] === $symbol) return (float)$asset['price'];
        }
        return 0.0;
    }

    public function assetData() {
        $symbol = $_GET['symbol'] ?? 'AAPL';
        $period = $_GET['period'] ?? '1M';
        $points = match($period) {
            '1D' => 24, '1W' => 7, '1M' => 30, '3M' => 90, '1Y' => 250, '5Y' => 500, default => 30
        };

        $currentPrice = $this->getCurrentPrice($symbol);
        if ($currentPrice <= 0) $currentPrice = 150.00; 

        $values = []; $labels = []; $tempPrice = $currentPrice;

        for ($i = 0; $i < $points; $i++) {
            $timestamp = ($period === '1D') ? strtotime("-$i hours") : strtotime("-$i days");
            if ($period === '1D') $labels[] = date('H:i', $timestamp);
            elseif (in_array($period, ['5Y', '1Y'])) $labels[] = date('m.Y', $timestamp);
            else $labels[] = date('d.m', $timestamp);

            $values[] = round($tempPrice, 2);
            $tempPrice -= $tempPrice * (rand(-200, 200) / 10000); 
        }

        header('Content-Type: application/json');
        echo json_encode(['labels' => array_reverse($labels), 'values' => array_reverse($values)]);
        exit();
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        header("Location: login");
        exit();
    }
}