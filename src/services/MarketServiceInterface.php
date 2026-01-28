<?php

interface MarketServiceInterface {
    public function getMarketData(): array;
    public function getHistory(string $symbol, string $period): array;
}