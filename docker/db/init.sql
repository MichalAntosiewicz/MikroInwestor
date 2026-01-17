-- Usuwamy tabele jeśli istnieją (czyszczenie)
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS portfolios;
DROP TABLE IF EXISTS asset_prices;
DROP TABLE IF EXISTS assets;
DROP TABLE IF EXISTS users;

-- Tabela Użytkowników
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    balance DECIMAL(15, 2) DEFAULT 10000.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela Aktywów (Akcje/Krypto)
CREATE TABLE assets (
    id SERIAL PRIMARY KEY,
    symbol VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(20) NOT NULL -- 'stock' lub 'crypto'
);

-- Tabela Cen (Historia i aktualne)
CREATE TABLE asset_prices (
    id SERIAL PRIMARY KEY,
    asset_id INTEGER REFERENCES assets(id) ON DELETE CASCADE,
    price DECIMAL(15, 2) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela Transakcji
CREATE TABLE transactions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    asset_id INTEGER REFERENCES assets(id) ON DELETE CASCADE,
    type VARCHAR(4) CHECK (type IN ('BUY', 'SELL')),
    amount DECIMAL(18, 8) NOT NULL,
    price_per_unit DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela Portfela (Aktualny stan posiadania)
CREATE TABLE portfolios (
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    asset_id INTEGER REFERENCES assets(id) ON DELETE CASCADE,
    total_amount DECIMAL(18, 8) DEFAULT 0,
    PRIMARY KEY (user_id, asset_id)
);

-- Dane startowe
INSERT INTO users (username, email, password, balance) 
VALUES ('admin', 'admin@mikro.pl', '$2y$10$fV/Jm8/Vp5C.M8lA2.H/UeN1P.S7.uN/qP5eW8.L3e.6V8.L3e.6V', 10000.00);

INSERT INTO assets (symbol, name, type) VALUES 
('BTC', 'Bitcoin', 'crypto'),
('ETH', 'Ethereum', 'crypto'),
('AAPL', 'Apple Inc.', 'stock'),
('TSLA', 'Tesla Inc.', 'stock');

INSERT INTO asset_prices (asset_id, price) VALUES 
(1, 42000.00), (2, 2200.00), (3, 185.50), (4, 215.10);