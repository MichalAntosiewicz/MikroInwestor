<?php

require_once 'Repository.php';

class TradeRepository extends Repository {

    public function buy(int $userId, string $symbol, float $amount, float $price): bool {
        $db = $this->database->getConnection();
        
        try {
            // BINGO A1/A5: Transakcja zapewnia atomowość (pieniądze nie znikną bez zapisu transakcji)
            $db->beginTransaction();

            // 1. Pobierz id_assets (musimy wiedzieć, co kupujemy)
            $stmt = $db->prepare('SELECT id FROM public.assets WHERE symbol = :symbol');
            $stmt->execute(['symbol' => $symbol]);
            $assetId = $stmt->fetchColumn();
            
            if (!$assetId) {
                // Jeśli aktywa nie ma w tabeli assets, dodajemy go (np. nowa spółka z API)
                $stmt = $db->prepare('INSERT INTO public.assets (symbol, name, type) VALUES (?, ?, ?)');
                $stmt->execute([$symbol, $symbol . ' Inc.', 'stock']);
                $assetId = $db->lastInsertId();
            }

            // 2. Oblicz koszt i zaktualizuj balans użytkownika
            $cost = $amount * $price;
            // Warunek balance >= :cost chroni przed debetem na poziomie SQL
            $stmt = $db->prepare('
                UPDATE public.users 
                SET balance = balance - :cost 
                WHERE id = :id AND balance >= :cost
            ');
            $stmt->execute(['cost' => $cost, 'id' => $userId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Niewystarczające środki!");
            }

            // 3. Dodaj rekord do TRANSACTIONS
            // BINGO: Tutaj wkracza Twój TRIGGER w bazie danych, który po tym INSERTcie
            // automatycznie zaktualizuje tabelę public.user_assets (doda ilość i obliczy średnią cenę)
            $stmt = $db->prepare('
                INSERT INTO public.transactions (id_users, id_assets, type, amount, price_per_unit) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$userId, $assetId, 'BUY', $amount, $price]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            // Logowanie błędu (opcjonalnie na obronę)
            error_log("Błąd zakupu: " . $e->getMessage());
            return false;
        }
    }

    public function sell(int $userId, string $symbol, float $amount, float $price): bool {
        $db = $this->database->getConnection();
        
        try {
            $db->beginTransaction();

            // 1. Pobierz asset_id
            $stmt = $db->prepare('SELECT id FROM public.assets WHERE symbol = :symbol');
            $stmt->execute(['symbol' => $symbol]);
            $assetId = $stmt->fetchColumn();

            if (!$assetId) throw new Exception("Nie znaleziono aktywa.");

            // 2. Sprawdź czy użytkownik faktycznie posiada tyle akcji
            // Ważne: Sprawdzamy to w PHP przed wykonaniem sprzedaży
            $stmt = $db->prepare('
                SELECT amount FROM public.user_assets 
                WHERE id_users = :uid AND id_assets = :aid
            ');
            $stmt->execute(['uid' => $userId, 'aid' => $assetId]);
            $currentAmount = (float)$stmt->fetchColumn();

            if ($currentAmount < $amount) {
                throw new Exception("Masz za mało jednostek, aby dokonać sprzedaży.");
            }

            // 3. Dodaj środki do balansu użytkownika
            $totalGain = $amount * $price;
            $stmt = $db->prepare('UPDATE public.users SET balance = balance + :gain WHERE id = :id');
            $stmt->execute(['gain' => $totalGain, 'id' => $userId]);

            // 4. Dodaj wpis do transactions
            // TRIGGER w bazie danych automatycznie ODEJMIE ilość z user_assets po tym INSERTcie
            $stmt = $db->prepare('
                INSERT INTO public.transactions (id_users, id_assets, type, amount, price_per_unit) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$userId, $assetId, 'SELL', $amount, $price]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log("Błąd sprzedaży: " . $e->getMessage());
            return false;
        }
    }

    public function getUserHistory(int $userId): array {
        // BINGO A1: Prepared Statement zapobiega SQL Injection
        $stmt = $this->database->getConnection()->prepare('
            SELECT t.type, t.amount, t.price_per_unit, t.created_at, a.symbol
            FROM public.transactions t
            JOIN public.assets a ON t.id_assets = a.id
            WHERE t.id_users = :id
            ORDER BY t.created_at DESC
        ');
        $stmt->execute(['id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}