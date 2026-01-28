<?php

require_once 'Repository.php';

class PortfolioRepository extends Repository {

    public function getUserPortfolio(int $userId): array {
        // Pobieramy dane z tabeli user_assets, łącząc z tabelą assets po ID
        $stmt = $this->database->getConnection()->prepare('
            SELECT 
                a.symbol, 
                ua.amount, 
                ua.avg_buy_price 
            FROM public.user_assets ua
            JOIN public.assets a ON ua.id_assets = a.id
            WHERE ua.id_users = :id AND ua.amount > 0
        ');
        $stmt->execute(['id' => $userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}