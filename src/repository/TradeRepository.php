<?php
require_once 'Repository.php';

class TradeRepository extends Repository {
    public function buy($userId, $symbol, $amount, $price) {
        $db = $this->database->getConnection();
        
        try {
            $db->beginTransaction();

            // 1. Musimy pobrać asset_id na podstawie symbolu (np. AAPL -> 3)
            $stmt = $db->prepare('SELECT id FROM public.assets WHERE symbol = :symbol');
            $stmt->execute(['symbol' => $symbol]);
            $asset = $stmt->fetch();
            
            if (!$asset) {
                throw new Exception("Nie znaleziono aktywa o symbolu: " . $symbol);
            }
            $assetId = $asset['id'];
            $totalCost = $amount * $price;

            // 2. Odejmij pieniądze użytkownikowi
            $stmt = $db->prepare('UPDATE public.users SET balance = balance - :cost WHERE id = :id AND balance >= :cost');
            $stmt->execute(['cost' => $totalCost, 'id' => $userId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Brak środków na koncie!");
            }

            // 3. Dodaj do portfela (total_amount i avg_buy_price)
            // Używamy UPSERT (INSERT ... ON CONFLICT)
            $stmt = $db->prepare('
                INSERT INTO public.portfolios (user_id, asset_id, total_amount, avg_buy_price)
                VALUES (:uid, :aid, :amt, :price)
                ON CONFLICT (user_id, asset_id) 
                DO UPDATE SET 
                    avg_buy_price = (public.portfolios.total_amount * public.portfolios.avg_buy_price + (:amt * :price)) 
                                    / (public.portfolios.total_amount + :amt),
                    total_amount = public.portfolios.total_amount + :amt
            ');
            
            $stmt->execute([
                'uid' => $userId, 
                'aid' => $assetId, 
                'amt' => $amount, 
                'price' => $price
            ]);

            // 4. Zapisz w historii transakcji
            $stmt = $db->prepare('
                INSERT INTO public.transactions (user_id, asset_id, type, amount, price_per_unit) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$userId, $assetId, 'BUY', $amount, $price]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            // Logowanie błędu, żebyś widział co poszło nie tak
            error_log("Błąd zakupu: " . $e->getMessage());
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