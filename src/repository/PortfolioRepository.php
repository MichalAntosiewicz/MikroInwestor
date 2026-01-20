<?php

require_once 'Repository.php';

class PortfolioRepository extends Repository {

    public function getUserPortfolio(int $userId): array {
        $stmt = $this->database->getConnection()->prepare('
            SELECT a.symbol, p.total_amount as amount, p.avg_buy_price 
            FROM portfolios p
            JOIN assets a ON p.asset_id = a.id
            WHERE p.user_id = :id AND p.total_amount > 0
        ');
        $stmt->execute(['id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}