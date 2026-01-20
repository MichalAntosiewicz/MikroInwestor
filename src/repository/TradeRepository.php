<?php
require_once 'Repository.php';

class TradeRepository extends Repository {
    public function buy(int $userId, string $symbol, float $amount, float $price): bool {
        $db = $this->database->getConnection();
        
        try {
            $db->beginTransaction();

            // 1. Pobierz lub STWÓRZ asset_id
            $stmt = $db->prepare('SELECT id FROM assets WHERE symbol = :symbol');
            $stmt->execute(['symbol' => $symbol]);
            $asset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$asset) {
                // Jeśli nie ma w bazie (np. NVDA), dodajemy go
                $stmt = $db->prepare('INSERT INTO assets (symbol, name, type) VALUES (?, ?, ?)');
                $stmt->execute([$symbol, $symbol . ' Inc.', 'stock']);
                $assetId = $db->lastInsertId();
            } else {
                $assetId = $asset['id'];
            }

            // 2. Sprawdź i odejmij balans
            $cost = $amount * $price;
            $stmt = $db->prepare('UPDATE users SET balance = balance - :cost WHERE id = :id AND balance >= :cost');
            $stmt->execute(['cost' => $cost, 'id' => $userId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Brak środków na koncie!");
            }

            // 3. Dodaj rekord do TRANSACTIONS
            $stmt = $db->prepare('
                INSERT INTO transactions (user_id, asset_id, type, amount, price_per_unit) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$userId, $assetId, 'BUY', $amount, $price]);

            // 4. UPSERT w PORTFOLIOS
            $stmt = $db->prepare('
                INSERT INTO portfolios (user_id, asset_id, total_amount, avg_buy_price)
                VALUES (:uid, :aid, :amount, :price)
                ON CONFLICT (user_id, asset_id) 
                DO UPDATE SET 
                    avg_buy_price = (portfolios.avg_buy_price * portfolios.total_amount + :price * :amount) / (portfolios.total_amount + :amount),
                    total_amount = portfolios.total_amount + :amount
            ');
            $stmt->execute([
                'uid' => $userId, 
                'aid' => $assetId, 
                'amount' => $amount, 
                'price' => $price
            ]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    public function sell($userId, $symbol, $amount, $price) {
        $db = $this->database->getConnection();
        
        try {
            $db->beginTransaction();

            // 1. Pobierz asset_id dla symbolu
            $stmt = $db->prepare('SELECT id FROM public.assets WHERE symbol = :symbol');
            $stmt->execute(['symbol' => $symbol]);
            $assetId = $stmt->fetchColumn();

            if (!$assetId) {
                throw new Exception("Nie znaleziono aktywa o symbolu: " . $symbol);
            }

            // 2. Sprawdź stan posiadania (rzutujemy na int/float dla pewności)
            $stmt = $db->prepare('
                SELECT total_amount FROM public.portfolios 
                WHERE user_id = :uid AND asset_id = :aid
            ');
            $stmt->execute([
                'uid' => (int)$userId, 
                'aid' => (int)$assetId
            ]);
            $currentAmount = (float)$stmt->fetchColumn();

            if ($currentAmount < (float)$amount) {
                throw new Exception("Niewystarczająca ilość akcji! Masz: $currentAmount, chcesz sprzedać: $amount");
            }

            // 3. Dodaj pieniądze użytkownikowi
            $totalGain = (float)$amount * (float)$price;
            $stmt = $db->prepare('UPDATE public.users SET balance = balance + :gain WHERE id = :id');
            $stmt->execute([
                'gain' => $totalGain, 
                'id' => (int)$userId
            ]);

            // 4. Odejmij akcje z portfela
            $stmt = $db->prepare('
                UPDATE public.portfolios 
                SET total_amount = total_amount - :amt 
                WHERE user_id = :uid AND asset_id = :aid
            ');
            $stmt->execute([
                'amt' => (float)$amount, 
                'uid' => (int)$userId, 
                'aid' => (int)$assetId
            ]);

            // 5. Zapisz transakcję w historii
            $stmt = $db->prepare('
                INSERT INTO public.transactions (user_id, asset_id, type, amount, price_per_unit) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                (int)$userId, 
                (int)$assetId, 
                'SELL', 
                (float)$amount, 
                (float)$price
            ]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            // Wyświetl błąd w logach kontenera php (docker logs mikroinwestor_php)
            error_log("BŁĄD SPRZEDAŻY: " . $e->getMessage());
            return false;
        }
    }

    public function getUserHistory(int $userId): array
    {
        // Używamy połączenia z klasy nadrzędnej Repository
        $stmt = $this->database->getConnection()->prepare("
            SELECT 
                t.created_at, 
                a.symbol, 
                t.type, 
                t.amount, 
                t.price_per_unit as price
            FROM public.transactions t
            JOIN public.assets a ON t.asset_id = a.id
            WHERE t.user_id = :user_id
            ORDER BY t.created_at DESC
        ");

        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}