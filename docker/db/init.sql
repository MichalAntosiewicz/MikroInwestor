DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS portfolios;
DROP TABLE IF EXISTS asset_prices;
DROP TABLE IF EXISTS assets;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    balance DECIMAL(15, 2) DEFAULT 151401.00, -- Twoje saldo początkowe (album)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE assets (
    id SERIAL PRIMARY KEY,
    symbol VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(20) NOT NULL
);

CREATE TABLE asset_prices (
    id SERIAL PRIMARY KEY,
    asset_id INTEGER REFERENCES assets(id) ON DELETE CASCADE,
    price DECIMAL(15, 2) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transactions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    asset_id INTEGER REFERENCES assets(id) ON DELETE CASCADE,
    type VARCHAR(4) CHECK (type IN ('BUY', 'SELL')),
    amount DECIMAL(18, 8) NOT NULL,
    price_per_unit DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE portfolios (
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    asset_id INTEGER REFERENCES assets(id) ON DELETE CASCADE,
    total_amount DECIMAL(18, 8) DEFAULT 0,
    avg_buy_price DECIMAL(15, 2) DEFAULT 0,
    PRIMARY KEY (user_id, asset_id)
);

INSERT INTO assets (symbol, name, type) VALUES 
('BTC', 'Bitcoin', 'crypto'),
('ETH', 'Ethereum', 'crypto'),
('AAPL', 'Apple Inc.', 'stock'),
('TSLA', 'Tesla Inc.', 'stock');

-- Hasło dla admina to: admin123 (zahashowane)
INSERT INTO users (username, email, password, balance) 
VALUES ('admin', 'admin@mikro.pl', '$2y$10$fV/Jm8/Vp5C.M8lA2.H/UeN1P.S7.uN/qP5eW8.L3e.6V8.L3e.6V', 151401.00);