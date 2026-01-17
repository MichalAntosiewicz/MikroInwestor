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
}