<?php

require_once 'AppController.php';
require_once __DIR__.'/../services/MarketService.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/TradeRepository.php';

class ProjectController extends AppController {

    private $userRepository;

    public function __construct() {
        parent::__construct();
        // Inicjalizujemy repozytorium, aby nie było nullem
        $this->userRepository = new UserRepository();
    }

    public function dashboard() {
        if (!isset($_SESSION['user_id'])) {
            $this->render('login');
            return;
        }

        $userId = $_SESSION['user_id'];
        $currentTime = time();
        $cacheDuration = 60;

        // Pobieranie danych rynkowych (Logic Cache)
        if (isset($_SESSION['assets_cache']) && (time() - $_SESSION['assets_timestamp'] < $cacheDuration)) {
            $assets = $_SESSION['assets_cache'];
        } else {
            $marketService = new MarketService();
            $assets = $marketService->getMarketData();
            $_SESSION['assets_cache'] = $assets;
            $_SESSION['assets_timestamp'] = $currentTime;
        }

        $secondsLeft = $cacheDuration - ($currentTime - ($_SESSION['assets_timestamp'] ?? $currentTime));
        
        // Pobieranie prawdziwego salda z bazy
        $user = $this->userRepository->getUser($_SESSION['user_id']);
        $balance = $user ? $user->getBalance() : 0;

        $marketService = new MarketService();
        $assets = $_SESSION['assets_cache'] ?? $marketService->getMarketData();

        // 2. Pobieramy portfel użytkownika, aby wiedzieć co może sprzedać
        require_once __DIR__.'/../repository/PortfolioRepository.php';
        $portfolioRepo = new PortfolioRepository();
        $userPortfolio = $portfolioRepo->getUserPortfolio($user->getId());

        $this->render('dashboard', [
            'assets' => $assets,
            'refresh_in' => $secondsLeft,
            'balance' => $user->getBalance(),
            'user_assets' => $userPortfolio,
        ]);
    }

    public function market() {
        if (!isset($_SESSION['user_id'])) {
            $this->render('login');
            return;
        }

        $currentTime = time();
        $cacheDuration = 60;

        if (isset($_SESSION['assets_cache']) && ($currentTime - $_SESSION['assets_timestamp'] < $cacheDuration)) {
            $assets = $_SESSION['assets_cache'];
        } else {
            $marketService = new MarketService();
            $assets = $marketService->getMarketData();
            $_SESSION['assets_cache'] = $assets;
            $_SESSION['assets_timestamp'] = $currentTime;
        }

        $secondsLeft = $cacheDuration - ($currentTime - $_SESSION['assets_timestamp']);

        // POPRAWKA: getUser zamiast getUserById (linia 72)
        $user = $this->userRepository->getUser($_SESSION['user_id']);
        $balance = $user ? $user->getBalance() : 0;

        $this->render('market', [
            'assets' => $assets,
            'refresh_in' => $secondsLeft,
            'balance' => $balance
        ]);
    }

    public function history() {
        if (!isset($_SESSION['user_id'])) {
            $this->render('login');
            return;
        }

        $user = $this->userRepository->getUser($_SESSION['user_id']);
        
        require_once __DIR__.'/../repository/TradeRepository.php';
        $tradeRepo = new TradeRepository();
        
        // Pobieramy historię z bazy danych
        $history = $tradeRepo->getUserHistory($user->getId());

        $this->render('history', [
            'history' => $history,
            'balance' => $user->getBalance()
        ]);
    }

    public function portfolio() {
        if (!isset($_SESSION['user_id'])) {
            $this->render('login');
            return;
        }

        $user = $this->userRepository->getUser($_SESSION['user_id']);
        require_once __DIR__.'/../repository/PortfolioRepository.php';
        $portfolioRepo = new PortfolioRepository();
        $raw_assets = $portfolioRepo->getUserPortfolio($user->getId());

        // Pobieramy aktualne ceny z serwisu rynkowego
        $marketService = new MarketService();
        $marketData = $_SESSION['assets_cache'] ?? $marketService->getMarketData();

        $user_assets = [];
        $total_portfolio_value = 0;

        foreach ($raw_assets as $asset) {
            $symbol = $asset['symbol'];
            $amount = (float)$asset['amount'];
            $avgPrice = (float)$asset['avg_buy_price'];

            // Znajdź aktualną cenę rynkową dla tego symbolu
            $currentMarketPrice = 0;
            foreach ($marketData as $m) {
                if ($m['symbol'] === $symbol) {
                    $currentMarketPrice = (float)$m['price'];
                    break;
                }
            }

            $currentValue = $amount * $currentMarketPrice;
            $total_portfolio_value += $currentValue;

            // Oblicz zysk/stratę w %
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
            'balance' => $user->getBalance()
        ]);
    }

    public function trade() {
        unset($_SESSION['error']);
        unset($_SESSION['message']);
        // Pobieramy dane z tablicy $_GET (bo są w adresie URL)
        $symbol = $_GET['symbol'] ?? null;
        $type = $_GET['type'] ?? 'BUY';

        if (!$symbol) {
            header("Location: dashboard");
            return;
        }

        // Pobieramy aktualną cenę dla tego symbolu z cache sesji
        $price = 0;
        if (isset($_SESSION['assets_cache'])) {
            foreach ($_SESSION['assets_cache'] as $asset) {
                if ($asset['symbol'] === $symbol) {
                    $price = $asset['price'];
                    break;
                }
            }
        }

        $user = $this->userRepository->getUser($_SESSION['user_id']);
        $balance = $user ? $user->getBalance() : 0;

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

        // Renderujemy widok trade.html
        $this->render('trade', [
            'symbol' => $symbol,
            'type' => $type,
            'price' => $price,
            'balance' => $balance,
            'owned_amount' => $ownedAmount
        ]);
    }

    public function executeTrade() {
        if (!$this->isPost()) {
            header("Location: dashboard");
            return;
        }

        $user = $this->userRepository->getUser($_SESSION['user_id']);
        $type = $_POST['type'];
        $symbol = $_POST['symbol'];
        $amount = (float)$_POST['amount'];
        $price = (float)$_POST['price'];
        $totalCost = $amount * $price;

        // Walidacja środków dla zakupu
        if ($type === 'BUY' && $totalCost > $user->getBalance()) {
            $_SESSION['error'] = "Niewystarczające środki na koncie! Brakuje: $" . number_format($totalCost - $user->getBalance(), 2);
            header("Location: trade?symbol=$symbol&type=BUY");
            exit();
        }

        if ($type === 'SELL') {
            // 1. Pobieramy portfel, żeby sprawdzić stan posiadania
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

            // 2. Sprawdzamy, czy użytkownik nie chce sprzedać więcej niż ma
            if ($amount > $ownedAmount) {
                $_SESSION['error'] = "Nie masz wystarczającej ilości akcji! Posiadasz: $ownedAmount";
                header("Location: trade?symbol=$symbol&type=SELL");
                exit();
            }
        }

        if ($amount <= 0) {
            $_SESSION['error'] = "Ilość musi być większa niż zero!";
            header("Location: trade?symbol=$symbol&type=$type");
            exit();
        }

        require_once __DIR__.'/../repository/TradeRepository.php';
        $tradeRepo = new TradeRepository();
        
        $success = ($type === 'BUY') 
            ? $tradeRepo->buy($user->getId(), $symbol, $amount, $price)
            : $tradeRepo->sell($user->getId(), $symbol, $amount, $price);

        if ($success) {
            $_SESSION['message'] = "Transakcja zakończona pomyślnie!";
            header("Location: portfolio");
        } else {
            $_SESSION['error'] = "Wystąpił błąd podczas przetwarzania transakcji.";
            header("Location: market");
        }
        exit();
    }

    private function getCurrentPrice(string $symbol): float
    {
        foreach ($_SESSION['assets_cache'] ?? [] as $asset) {
            if ($asset['symbol'] === $symbol) {
                return $asset['price'];
            }
        }
        return 0;
    }

    public function asset() {
        $symbol = $_GET['symbol'] ?? null;
        if (!$symbol) {
            header("Location: dashboard");
            return;
        }

        $user = $this->userRepository->getUser($_SESSION['user_id']);
        
        // Pobieramy aktualną cenę z cache
        $currentPrice = $this->getCurrentPrice($symbol);
        
        // Symulacja danych historycznych dla wykresu (7 punktów)
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
            'history_data' => json_encode($historyData) // Przekazujemy do JS
        ]);
    }
    public function assetData() {
        $symbol = $_GET['symbol'] ?? 'AAPL';
        $period = $_GET['period'] ?? '1M';

        // 1. Ustalamy liczbę punktów
        $points = match($period) {
            '1D' => 24,
            '1W' => 7,
            '1M' => 30,
            '3M' => 90,
            '1Y' => 250,
            '5Y' => 500,
            default => 30
        };

        $currentPrice = $this->getCurrentPrice($symbol);
        if ($currentPrice <= 0) $currentPrice = 150.00; 

        $values = [];
        $labels = [];
        $tempPrice = $currentPrice;

        // 2. Generujemy dane
        for ($i = 0; $i < $points; $i++) {
            // Poprawka: $period === '1D' odejmuje godziny, reszta dni
            $timestamp = ($period === '1D') ? strtotime("-$i hours") : strtotime("-$i days");
            
            // 3. Formatowanie daty (Poprawiona logika if/else)
            if ($period === '1D') {
                $labels[] = date('H:i', $timestamp);
            } elseif ($period === '5Y' || $period === '1Y') {
                $labels[] = date('m.Y', $timestamp);
            } else {
                $labels[] = date('d.m', $timestamp);
            }

            $values[] = round($tempPrice, 2);

            // Algorytm błądzenia losowego
            $change = $tempPrice * (rand(-200, 200) / 10000);
            $tempPrice -= $change; 
        }

        // 4. Wysyłka JSON
        header('Content-Type: application/json');
        echo json_encode([
            'labels' => array_reverse($labels),
            'values' => array_reverse($values)
        ]);
        exit();
    }

    public function logout() {
        // Rozpoczynamy sesję, jeśli nie jest aktywna
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Czyścimy tablicę sesji
        $_SESSION = array();

        // Jeśli używasz ciasteczek sesyjnych, usuwamy je
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Całkowite zniszczenie sesji
        session_destroy();

        // Przekierowanie do logowania
        header("Location: login");
        exit();
    }
}

