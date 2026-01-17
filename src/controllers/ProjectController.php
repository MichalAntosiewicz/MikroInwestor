<?php

require_once 'AppController.php';
require_once __DIR__.'/../services/MarketService.php';

class ProjectController extends AppController {

    public function dashboard() {
        $currentTime = time();
    $cacheDuration = 60; // 60 sekund

    // Sprawdzamy, czy mamy świeże dane w sesji
    if (isset($_SESSION['assets_cache']) && 
        ($currentTime - $_SESSION['assets_timestamp'] < $cacheDuration)) {
        $assets = $_SESSION['assets_cache'];
    } else {
        // Jeśli nie ma lub stare - pobieramy z API
        $marketService = new MarketService();
        $assets = $marketService->getMarketData();

        // Zapisujemy w cache'u
        $_SESSION['assets_cache'] = $assets;
        $_SESSION['assets_timestamp'] = $currentTime;
    }

    // Obliczamy ile sekund zostało do następnego odświeżenia
    $secondsLeft = $cacheDuration - ($currentTime - ($_SESSION['assets_timestamp'] ?? $currentTime));

    $this->render('dashboard', [
        'assets' => $assets,
        'refresh_in' => $secondsLeft
    ]);
    }

    public function market() {
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

    $this->render('market', [
        'assets' => $assets,
        'refresh_in' => $secondsLeft
    ]);
}

    public function history() {
    $test_history = [
        [
            'created_at' => '2025-01-15 14:30',
            'symbol' => 'AAPL',
            'type' => 'BUY',
            'amount' => 2,
            'price' => 175.50
        ],
        [
            'created_at' => '2025-01-14 10:15',
            'symbol' => 'BTC',
            'type' => 'BUY',
            'amount' => 0.01,
            'price' => 42000.00
        ],
        [
            'created_at' => '2025-01-12 18:45',
            'symbol' => 'TSLA',
            'type' => 'SELL',
            'amount' => 10,
            'price' => 210.00
        ]
    ];

    $this->render('history', ['history' => $test_history]);
}

    public function portfolio() {
    // Na potrzeby prezentacji "na pokaz"
        $test_assets = [
            [
                'symbol' => 'AAPL',
                'amount' => 5,
                'avg_buy_price' => 170.00,
                'current_value' => 926.50,
                'profit_loss' => 8.5
            ],
            [
                'symbol' => 'BTC',
                'amount' => 0.02,
                'avg_buy_price' => 40000.00,
                'current_value' => 850.00,
                'profit_loss' => -2.4
            ]
        ];

        $this->render('portfolio', [
            'user_assets' => $test_assets,
            'total_value' => 1776.50
        ]);
    }
}