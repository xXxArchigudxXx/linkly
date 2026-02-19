@echo off
chcp 65001 >nul
echo ========================================
echo   URL Shortener - Development Mode
echo ========================================
echo.

REM STEP 1: Check .env file
if not exist .env (
    echo [INFO] Creating .env from .env.example...
    copy .env.example .env >nul
    echo [OK] .env file created
) else (
    echo [OK] .env file exists
)

REM STEP 2: Check vendor directory
if not exist vendor (
    echo [INFO] Installing PHP dependencies...
    composer install
    if errorlevel 1 (
        echo [ERROR] Composer install failed
        pause
        exit /b 1
    )
    echo [OK] PHP dependencies installed
) else (
    echo [OK] PHP dependencies already installed
)

REM STEP 3: Start Docker containers
echo.
echo [INFO] Starting Docker containers...
docker-compose up -d
if errorlevel 1 (
    echo [ERROR] Docker startup failed
    pause
    exit /b 1
)

REM STEP 4: Wait for backend health check
echo.
echo [INFO] Waiting for backend (10 seconds)...
timeout /t 10 /nobreak >nul

echo [INFO] Checking backend health...
curl -s http://localhost:8080/health >nul 2>&1
if errorlevel 1 (
    echo [WARN] Backend not ready yet, wait a few more seconds
) else (
    echo [OK] Backend is healthy
)

REM STEP 5: Check Frontend dependencies
echo.
if not exist Frontend\node_modules (
    echo [INFO] Installing Frontend dependencies...
    cd Frontend
    npm install
    if errorlevel 1 (
        echo [ERROR] npm install failed
        cd ..
        pause
        exit /b 1
    )
    cd ..
    echo [OK] Frontend dependencies installed
) else (
    echo [OK] Frontend dependencies already installed
)

REM STEP 6: Start Vite dev server in new window
echo.
echo [INFO] Starting Vite dev server...
start "Vite Dev Server" cmd /k "cd Frontend && npm run dev"

REM STEP 7: Display URLs
echo.
echo ========================================
echo   Development environment started!
echo   Backend API:  http://localhost:8080
echo   Frontend:     http://localhost:3000
echo   Health:       http://localhost:8080/health
echo ========================================
echo.
echo [TIP] Use stop.bat to stop all services
echo.
pause
