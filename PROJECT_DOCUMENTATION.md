# MikroInwestor - Project Documentation

## Project Overview

**MikroInwestor** is a web-based stock trading simulation application built with PHP, Docker, and PostgreSQL. It allows users to register, log in, view market data, manage their investment portfolio, execute trades, and track their trading history. The application integrates with the Finnhub API to fetch real-time stock prices for popular assets (AAPL, MSFT, TSLA, AMZN).

### Key Features:
- User authentication (login/registration with password hashing)
- Real-time market data integration via Finnhub API
- Portfolio management (buy/sell stocks)
- Trading history tracking
- User dashboard with portfolio overview
- Responsive web interface

### Technology Stack:
- **Backend**: PHP 8.3 (FPM)
- **Web Server**: Nginx 1.17.8
- **Database**: PostgreSQL 15
- **Frontend**: HTML5, CSS3, JavaScript
- **Containerization**: Docker & Docker Compose
- **API Integration**: Finnhub Stock API

---

## Root Level Files

### [index.php](index.php)
The main entry point of the application. This file:
- Starts the PHP session
- Registers and initializes the routing system
- Defines all available routes for the application
- Routes requests to appropriate controllers (SecurityController and ProjectController)
- Handles both GET and POST requests for authentication and application features

### [docker-compose.yaml](docker-compose.yaml)
Docker Compose configuration file that orchestrates three main services:
- **web**: Nginx reverse proxy (port 8080)
- **php**: PHP-FPM application runtime
- **db**: PostgreSQL database with initialization script
- Defines volumes for persistent data storage
- Sets environment variables (Finnhub API key)

### [readme.md](readme.md)
Currently marked as "todo" - intended for user-facing project documentation.

---

## Directory Structure & Components

### [src/](src/) - Backend Application Code
Core PHP backend implementing the MVC pattern and business logic.

#### [src/Routing.php](src/Routing.php)
Handles URL routing and request dispatch:
- Maintains a static route registry
- Maps URLs to controller classes
- Routes GET and POST requests to appropriate controllers
- Throws 404 errors for undefined routes
- Acts as the core dispatcher between URLs and controllers

#### [src/View.php](src/View.php)
Simple view renderer for displaying HTML templates:
- Loads templates from `public/views/` directory
- Supports variable extraction for template context
- Uses output buffering to capture rendered content
- Provides basic error handling for missing templates

---

### [src/controllers/](src/controllers/) - Request Handlers

#### [src/controllers/AppController.php](src/controllers/AppController.php)
Base controller class providing common functionality:
- Extends all other controllers
- Implements the `render()` method for displaying views
- Includes template path handling and output buffering
- Provides helper method `isPost()` to check request method
- Handles template file validation

#### [src/controllers/SecurityController.php](src/controllers/SecurityController.php)
Manages user authentication:
- **login()**: Handles user login with email/password validation
  - Verifies user existence
  - Uses BCRYPT password hashing for security
  - Sets user session on successful authentication
  - Redirects to dashboard after login
- **register()**: Handles new user registration
  - Validates input data (email, username, password)
  - Implements password confirmation check
  - Creates new user accounts in the database

#### [src/controllers/ProjectController.php](src/controllers/ProjectController.php)
Main application controller handling core features:
- **dashboard()**: Displays user's portfolio overview with market data
- **market()**: Shows current market prices and trends
- **history()**: Displays user's trading history
- **portfolio()**: Shows detailed portfolio composition
- **asset()**: Shows individual asset details
- **assetData()**: Returns asset data (likely for AJAX calls)
- **trade()**: Displays trading interface
- **executeTrade()**: Processes buy/sell trade execution
- **logout()**: Handles user logout and session termination
- Implements caching for market data to reduce API calls
- Includes user authentication checks for protected routes

---

### [src/database/](src/database/) - Database Management

#### [src/database/Database.php](src/database/Database.php)
Database connection manager implementing the Singleton pattern:
- Manages single PostgreSQL connection instance
- Connects to `mikroinwestor_db` database
- Credentials: user `user`, password `password`
- Sets PDO to fetch associative arrays and throw exceptions
- Provides `getInstance()` method to access the connection globally
- Centralized error handling for database connection failures

---

### [src/models/](src/models/) - Data Models

#### [src/models/User.php](src/models/User.php)
User data model representing application users:
- **Properties**:
  - `id`: Unique user identifier
  - `email`: User email address
  - `password`: Hashed password
  - `username`: Display name
  - `balance`: Current account balance (default 0.0)
- **Methods**: Getters for all properties
- Immutable design - no setters, values set in constructor

---

### [src/repository/](src/repository/) - Data Access Layer

#### [src/repository/Repository.php](src/repository/Repository.php)
Abstract base repository class:
- Provides access to Database singleton
- All repositories inherit from this class
- Centralizes database connection management

#### [src/repository/UserRepository.php](src/repository/UserRepository.php)
Handles all user-related database operations:
- **getUser(string $email)**: Retrieves user by email
- **getUserById(int $id)**: Retrieves user by ID
- Maps database records to User model objects
- Used by SecurityController for authentication

#### [src/repository/PortfolioRepository.php](src/repository/PortfolioRepository.php)
Manages user portfolio data:
- **getUserPortfolio(int $userId)**: Retrieves user's asset holdings
- Returns array of holdings with:
  - `symbol`: Stock symbol (AAPL, MSFT, etc.)
  - `amount`: Number of shares owned
  - `avg_buy_price`: Average purchase price per share
- Used by ProjectController for displaying portfolio information

#### [src/repository/TradeRepository.php](src/repository/TradeRepository.php)
Handles trading operations:
- **buy($userId, $symbol, $amount, $price)**: Executes stock purchase
  - Verifies user has sufficient balance
  - Updates user balance
  - Updates portfolio with new holdings
  - Records trade in history
  - Uses database transactions for atomicity
- Manages UPSERT operations for portfolio updates
- Handles average price calculations for multiple buys

---

### [src/services/](src/services/) - Business Logic

#### [src/services/MarketService.php](src/services/MarketService.php)
Manages integration with Finnhub stock API:
- **getStockPrice(string $symbol)**: Fetches current price for single stock
  - Returns: symbol, price, price change percentage
  - Uses CURL for HTTP requests
  - Returns error information if API call fails
- **getMarketData()**: Gets prices for multiple stocks (AAPL, MSFT, TSLA, AMZN)
  - Returns array of stock data for dashboard
- **getHistory($symbol, $period)**: Fetches historical price data
  - Useful for charting and analysis
- Securely retrieves API key from environment variables
- Handles API request timeouts and failures

---

### [public/](public/) - Frontend Files

Static files served to browsers including HTML templates, styles, and JavaScript.

#### [public/views/](public/views/) - HTML Templates
Contains all user-facing HTML templates rendered by controllers:

- **login.html**: User login form
  - Email and password fields
  - Error message display
  - Link to registration page

- **register.html**: New user registration form
  - Email, username, and password fields
  - Password confirmation
  - Login link for existing users

- **dashboard.html**: Main application dashboard
  - Portfolio summary and balance display
  - Market data with price updates
  - Navigation to other features
  - Cache timer for real-time data

- **portfolio.html**: Detailed portfolio view
  - List of owned assets with quantities
  - Average purchase prices
  - Current values and P&L calculations
  - Ability to manage holdings

- **market.html**: Market data display
  - Current prices for tracked assets
  - Price changes and percentages
  - Market trends visualization

- **trade.html**: Trading interface
  - Stock symbol selection
  - Buy/Sell order entry
  - Quantity and price inputs
  - Order confirmation

- **history.html**: Trading history log
  - List of past transactions
  - Buy/sell indicators
  - Dates, quantities, and prices
  - Historical performance tracking

- **asset_details.html**: Individual asset page
  - Detailed information about specific stock
  - Historical price chart
  - Technical indicators
  - Buy/sell quick links

- **user-management.html**: User administration page
  - User account management (if admin)
  - Account settings and preferences
  - Balance adjustments for testing

#### [public/styles/](public/styles/) - CSS Stylesheets
Application styling divided into modular CSS files:

- **main.css**: Global styles and base styling
  - Typography, colors, and layout fundamentals
  - Default element styles

- **dashboard.css**: Dashboard-specific styling
  - Portfolio widget layout
  - Balance display formatting
  - Market data table styles

- **login.css**: Authentication page styling
  - Form layouts for login/register
  - Input field styling
  - Error message presentation

- **navbar.css**: Navigation bar styling
  - Header and menu styling
  - Navigation link appearance
  - Logo and branding area

- **tables.css**: Data table styling
  - Portfolio and history table formatting
  - Sortable column headers
  - Responsive table layouts

#### [public/scripts/](public/scripts/) - JavaScript Files
Client-side functionality and interactivity:

- **menu.js**: Navigation menu handling
  - Mobile menu toggle
  - Active link highlighting
  - Menu animation and transitions

- **timer.js**: Real-time data refresh timer
  - Countdown display for market data cache
  - Auto-refresh trigger when cache expires
  - Visual feedback for data freshness

---

### [docker/](docker/) - Containerization Configuration

#### [docker/nginx/Dockerfile](docker/nginx/Dockerfile)
Nginx web server container:
- Based on `nginx:1.17.8-alpine` (lightweight Alpine Linux)
- Copies nginx configuration
- Mounts application files in `/app/` directory

#### [docker/nginx/nginx.conf](docker/nginx/nginx.conf)
Nginx server configuration:
- Listens on port 80
- Sets document root to `/app/` (mounted volume)
- Configures `index.php` as default document
- URL rewriting: routes all requests through `index.php` (URL-friendly routing)
- PHP FastCGI proxying to PHP-FPM container on port 9000
- Passes query strings through rewriting

#### [docker/php/Dockerfile](docker/php/Dockerfile)
PHP application runtime container:
- Based on `php:8.3-fpm-alpine` (lightweight Alpine Linux)
- Includes build dependencies for compiling extensions
- Installed PHP Extensions:
  - `opcache`: Bytecode caching for performance
  - `zip`: ZIP file handling
  - `gd`: Image processing
  - `bcmath`: Arbitrary precision mathematics
  - `pgsql`: PostgreSQL driver (legacy)
  - `pdo_pgsql`: PostgreSQL PDO driver
  - `curl`: HTTP client for API calls
- Includes runtime dependencies: curl, PostgreSQL libs, image libraries
- Runs as FPM (FastCGI Process Manager) for Nginx integration

#### [docker/db/Dockerfile](docker/db/Dockerfile)
PostgreSQL database container:
- Based on `postgres:15-alpine`
- Minimal configuration - relies on docker-compose for environment variables
- Uses mounted `init.sql` for database schema initialization

#### [docker/db/init.sql](docker/db/init.sql)
Database initialization script (referenced but contents not shown):
- Creates initial database schema
- Sets up tables: users, portfolios, assets, trades
- Defines relationships and constraints
- Initializes stock symbol reference data

---

## Architecture & Flow

### Request Flow
1. **Request arrives** at Nginx (port 8080)
2. **Nginx** routes to PHP-FPM via FastCGI
3. **index.php** receives request, starts session
4. **Routing system** matches URL to controller
5. **Controller** executes business logic
6. **Repository layer** handles database access
7. **Services** (MarketService) fetch external data
8. **View** renders HTML template with data
9. **Response** returned through Nginx to browser

### Authentication Flow
1. User submits login form
2. SecurityController validates credentials
3. Password verified against BCRYPT hash
4. User ID stored in `$_SESSION['user_id']`
5. Subsequent requests use session ID for authorization
6. Protected routes check session before execution

### Trading Flow
1. User selects stock and quantity on trade.html
2. executeTrade() method receives POST request
3. TradeRepository begins database transaction
4. Validates user balance is sufficient
5. Updates user balance
6. Updates/inserts portfolio record (UPSERT)
7. Records trade in history table
8. Transaction commits or rolls back on error

### Data Caching Strategy
- Market data cached in `$_SESSION` for 60 seconds
- Reduces API calls to Finnhub
- Timer shows cache freshness to user
- Auto-refreshes when cache expires

---

## Security Considerations

- **Password Hashing**: BCRYPT used for password storage (verified via `password_verify()`)
- **Session Management**: User authentication via `$_SESSION['user_id']`
- **SQL Injection Prevention**: Prepared statements with parameter binding throughout
- **Database Transactions**: Trade operations wrapped in transactions for data consistency
- **API Key Management**: Finnhub API key stored in environment variables (not in code)
- **Input Validation**: Form inputs validated in controllers before processing

---

## Development & Deployment

### Local Development
```bash
# Start services
docker-compose up -d

# Access application
http://localhost:8080

# View logs
docker-compose logs -f php
```

### Environment Setup
Required environment variable:
- `FINNHUB_API_KEY`: Token for Finnhub API access (set in docker-compose.yaml)

### Database Access
- Host: `db` (via Docker network)
- Database: `mikroinwestor_db`
- User: `user`
- Password: `password`
- Port: 5432 (5435 on host machine)

---

## File Summary Table

| File/Folder | Type | Purpose |
|---|---|---|
| index.php | PHP | Application entry point and routing |
| docker-compose.yaml | Config | Service orchestration |
| src/ | Directory | Backend application code |
| src/controllers/ | Directory | Request handlers and logic |
| src/models/ | Directory | Data models |
| src/repository/ | Directory | Database access layer |
| src/services/ | Directory | Business logic services |
| src/database/ | Directory | Database connection management |
| public/ | Directory | Frontend files |
| public/views/ | Directory | HTML templates |
| public/styles/ | Directory | CSS stylesheets |
| public/scripts/ | Directory | JavaScript files |
| docker/ | Directory | Container configurations |

