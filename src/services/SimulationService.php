<?php



require_once 'MarketServiceInterface.php';

class SimulationService implements MarketServiceInterface {
    public function getMarketData(): array {
        $symbols = [
            'AAPL' => [170, 190],
            'MSFT' => [390, 420],
            'GOOGL'=> [140, 155],
            'AMZN' => [170, 185],
            'TSLA' => [160, 200],
            'NVDA' => [800, 950],
            'META' => [450, 500],
            'NFLX' => [580, 630]
        ];

        $data = [];
        foreach ($symbols as $symbol => $range) {
            $price = rand($range[0] * 100, $range[1] * 100) / 100;
            $change = rand(-500, 500) / 100;

            $data[] = [
                'symbol' => $symbol,
                'price' => $price,
                'change' => $change
            ];
        }

        return $data;
    }
}