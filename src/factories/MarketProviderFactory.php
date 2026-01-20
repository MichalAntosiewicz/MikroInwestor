<?php

require_once __DIR__.'/../services/MarketService.php';
require_once __DIR__.'/../services/SimulationService.php';

class MarketProviderFactory {
    public static function getProvider(string $mode): MarketServiceInterface {
        // Fabryka teraz po prostu wykonuje zlecenie: "daj mi to, co wybrał użytkownik"
        return ($mode === 'real') ? new MarketService() : new SimulationService();
    }
}