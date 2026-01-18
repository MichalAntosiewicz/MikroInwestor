<?php

class MarketService {
    private $apiKey;

    public function __construct() {
        // Pobieramy klucz bezpiecznie z environment variables
        $this->apiKey = getenv('FINNHUB_API_KEY');
    }

    public function getStockPrice(string $symbol) {
        $url = "https://finnhub.io/api/v1/quote?symbol=$symbol&token=$this->apiKey";
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // Opcjonalnie: wyłącz sprawdzanie SSL jeśli masz problemy lokalnie (tylko dev!)
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
        
        $response = curl_exec($curl);
        
        if(curl_errno($curl)) {
            return ['symbol' => $symbol, 'price' => 0, 'change' => 0, 'error' => curl_error($curl)];
        }
        
        curl_close($curl);
        $data = json_decode($response, true);

        return [
            'symbol' => $symbol,
            'price' => $data['c'] ?? 0,
            'change' => $data['d'] ?? 0
        ];
    }

    public function getMarketData() {
        $symbols = ['AAPL', 'MSFT', 'TSLA', 'AMZN'];
        $results = [];

        foreach ($symbols as $symbol) {
            $results[] = $this->getStockPrice($symbol);
        }

        return $results;
    }

    public function getHistory($symbol, $period) {
        // Używamy $this->apiKey zamiast nieistniejącego $apiKey
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

        // Budujemy poprawny URL
        $url = "https://finnhub.io/api/v1/stock/candle?symbol=$symbol&resolution=$resolution&from=$from&to=$to&token=$token";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Dodaj to dla bezpieczeństwa połączenia
        $response = curl_exec($ch);
        
        if(curl_errno($ch)) {
            error_log('Finnhub Error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        return json_decode($response, true);
    }
}