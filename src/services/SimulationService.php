<?php

require_once 'MarketServiceInterface.php';

class SimulationService implements MarketServiceInterface {
    public function getMarketData(): array {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $now = time();
        
        $expireTimestamp = isset($_COOKIE['market_expire_time']) ? (int)$_COOKIE['market_expire_time'] : 0;

        if (isset($_SESSION['market_data_cache']) && $now < $expireTimestamp) {
            return $_SESSION['market_data_cache'];
        }

        $symbols = [
            'AAPL' => [170, 190], 'MSFT' => [390, 420], 'GOOGL'=> [140, 155],
            'AMZN' => [170, 185], 'TSLA' => [160, 200], 'NVDA' => [800, 950],
            'META' => [450, 500], 'NFLX' => [580, 630]
        ];

        $data = [];
        foreach ($symbols as $symbol => $range) {
            $price = rand($range[0] * 100, $range[1] * 100) / 100;
            $change = rand(-500, 500) / 100;
            $data[] = ['symbol' => $symbol, 'price' => $price, 'change' => $change];
        }

        $_SESSION['market_data_cache'] = $data;
        
        if ($expireTimestamp <= $now) {
            $expireTimestamp = $now + 60;
            setcookie('market_expire_time', $expireTimestamp, $expireTimestamp, "/");
        }

        return $data;
    }

    public function getHistory(string $symbol, string $period): array {
        $data = ['c' => [], 't' => []];
        $now = time();
        
        // Określamy ile punktów wygenerować
        $points = match($period) {
            '1D' => 24,
            '1W' => 7,
            '1M' => 30,
            '1Y' => 12,
            '5Y' => 60,
            default => 30
        };

        $interval = match($period) {
            '1D' => 3600,
            '1W', '1M' => 86400,
            '1Y', '5Y' => 86400 * 30,
            default => 86400
        };

        $basePrice = rand(150, 200);
        for ($i = $points; $i >= 0; $i--) {
            $data['t'][] = $now - ($i * $interval);
            // Generujemy lekki trend losowy
            $basePrice += (rand(-500, 500) / 100);
            $data['c'][] = round($basePrice, 2);
        }

        return $data;
    }
}