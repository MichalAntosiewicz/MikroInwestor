-- Czyścimy wszystko, żeby nie było konfliktów
DROP VIEW IF EXISTS v_user_portfolio_report CASCADE;
DROP VIEW IF EXISTS v_transaction_history CASCADE;
DROP TABLE IF EXISTS public.user_assets CASCADE;
DROP TABLE IF EXISTS public.transactions CASCADE;
DROP TABLE IF EXISTS public.assets CASCADE;
DROP TABLE IF EXISTS public.users CASCADE;
DROP TABLE IF EXISTS public.user_details CASCADE;

-- 1. Szczegóły użytkownika
CREATE TABLE public.user_details (
    id SERIAL PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Użytkownicy (balance = numer albumu)
CREATE TABLE public.users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    balance DECIMAL(15, 2) DEFAULT 151401.00,
    role VARCHAR(20) DEFAULT 'user',
    market_mode VARCHAR(20) DEFAULT 'simulated',
    id_user_details INTEGER UNIQUE,
    CONSTRAINT fk_details FOREIGN KEY (id_user_details) REFERENCES public.user_details(id) ON DELETE CASCADE
);

-- 3. Aktywa (akcje/krypto)
CREATE TABLE public.assets (
    id SERIAL PRIMARY KEY,
    symbol VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100),
    type VARCHAR(20),
    current_price DECIMAL(15, 4) NOT NULL DEFAULT 1.00
);

-- 4. Transakcje
CREATE TABLE public.transactions (
    id SERIAL PRIMARY KEY,
    id_users INTEGER NOT NULL,
    id_assets INTEGER NOT NULL,
    type VARCHAR(10) CHECK (type IN ('BUY', 'SELL')),
    amount DECIMAL(15, 8) NOT NULL,
    price_per_unit DECIMAL(15, 4) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user FOREIGN KEY (id_users) REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT fk_asset FOREIGN KEY (id_assets) REFERENCES public.assets(id)
);

-- 5. Portfel (NAPRAWIONA STRUKTURA)
CREATE TABLE public.user_assets (
    id_users INTEGER NOT NULL,
    id_assets INTEGER NOT NULL,
    amount DECIMAL(15, 8) DEFAULT 0,
    avg_buy_price DECIMAL(15, 4) DEFAULT 0, -- TO TEGO BRAKOWAŁO
    PRIMARY KEY (id_users, id_assets),
    FOREIGN KEY (id_users) REFERENCES public.users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_assets) REFERENCES public.assets(id) ON DELETE CASCADE
);

-- 6. Trigger do automatycznej aktualizacji portfela
CREATE OR REPLACE FUNCTION update_portfolio()
RETURNS TRIGGER AS $$
BEGIN
    IF (NEW.type = 'BUY') THEN
        INSERT INTO public.user_assets (id_users, id_assets, amount, avg_buy_price)
        VALUES (NEW.id_users, NEW.id_assets, NEW.amount, NEW.price_per_unit)
        ON CONFLICT (id_users, id_assets) DO UPDATE SET
            avg_buy_price = ((public.user_assets.amount * public.user_assets.avg_buy_price) + (NEW.amount * NEW.price_per_unit)) / (public.user_assets.amount + NEW.amount),
            amount = public.user_assets.amount + NEW.amount;
    ELSIF (NEW.type = 'SELL') THEN
        UPDATE public.user_assets SET amount = amount - NEW.amount
        WHERE id_users = NEW.id_users AND id_assets = NEW.id_assets;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_after_transaction
AFTER INSERT ON public.transactions
FOR EACH ROW EXECUTE FUNCTION update_portfolio();

-- Przykładowe dane
INSERT INTO assets (symbol, name, type, current_price) VALUES 
('BTC', 'Bitcoin', 'simulated', 42000.00),
('AAPL', 'Apple Inc.', 'real', 185.00);