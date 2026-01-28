<?php

require_once 'MarketServiceInterface.php';

class MarketService implements MarketServiceInterface{
    private $apiKey;

    public function __construct() {
        $this->apiKey = getenv('FINNHUB_API_KEY');
    }

    public function getStockPrice(string $symbol) {
        $url = "https://finnhub.io/api/v1/quote?symbol=$symbol&token=$this->apiKey";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
        $response = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($response, true);
        return [
            'symbol' => $symbol,
            'price' => round($data['c'] ?? 0, 2),
            'change' => round($data['d'] ?? 0, 2)
        ];
    }

    public function getMarketData() : array {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $now = time();
        $expireTimestamp = isset($_COOKIE['market_expire_time']) ? (int)$_COOKIE['market_expire_time'] : 0;

        // BINGO: Cache zsynchronizowany z licznikiem JS
        if (isset($_SESSION['real_market_cache']) && $now < $expireTimestamp) {
            return $_SESSION['real_market_cache'];
        }

        $symbols = ['AAPL', 'MSFT', 'TSLA', 'AMZN'];
        $results = [];
        foreach ($symbols as $symbol) {
            $results[] = $this->getStockPrice($symbol);
        }

        $_SESSION['real_market_cache'] = $results;
        
        if ($expireTimestamp <= $now) {
            $expireTimestamp = $now + 60;
            setcookie('market_expire_time', $expireTimestamp, $expireTimestamp, "/");
        }

        return $results;
    }

    public function getHistory(string $symbol, string $period): array {
        $token = $this->apiKey; 
        
        $to = time();
        $from = match($period) {
            '1W' => strtotime("-1 week"),
            '1M' => strtotime("-1 month"),
            '3M' => strtotime("-3 month"),
            '1Y' => strtotime("-1 year"),
            '5Y' => strtotime("-5 years"),
            default => strtotime("-1 month")
        };

        $resolution = ($period === '5Y') ? 'W' : 'D';

        $url = "https://finnhub.io/api/v1/stock/candle?symbol=$symbol&resolution=$resolution&from=$from&to=$to&token=$token";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        
        if(curl_errno($ch)) {
            error_log('Finnhub Error: ' . curl_error($ch));
            return []; // Zwróć pustą tablicę przy błędzie
        }
        
        curl_close($ch);

        $result = json_decode($response, true);
        
        // Upewnij się, że zawsze zwracasz tablicę (wymóg interfejsu)
        return is_array($result) ? $result : [];
    }
}