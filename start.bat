@echo off
chcp 65001 >nul
echo ========================================
echo   URL Shortener - Production Mode
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

REM STEP 3: Check frontend build
if not exist public\dist\index.html (
    echo.
    echo [WARN] Frontend not built! public\dist\index.html not found.
    echo.
    echo [INFO] Run build.bat to build frontend for production.
    echo.
    set /p CONTINUE="Continue without frontend? (Y/N): "
    if /i not "%CONTINUE%"=="Y" (
        echo [INFO] Aborted. Run build.bat first.
        pause
        exit /b 1
    )
    echo [WARN] Continuing without frontend...
) else (
    echo [OK] Frontend build found
)

REM STEP 4: Start Docker containers
echo.
echo [INFO] Starting Docker containers...
docker-compose up -d
if errorlevel 1 (
    echo [ERROR] Docker startup failed
    pause
    exit /b 1
)

REM STEP 5: Wait for health check
echo.
echo [INFO] Waiting for services to start (10 seconds)...
timeout /t 10 /nobreak >nul

echo [INFO] Checking service health...
curl -s http://localhost:8080/health >nul 2>&1
if errorlevel 1 (
    echo [WARN] Service not ready yet, wait a few more seconds
) else (
    echo [OK] Service is healthy
)

REM STEP 6: Display URLs
echo.
echo ========================================
echo   Production server started!
echo   URL:     http://localhost:8080
echo   Health:  http://localhost:8080/health
echo ========================================
echo.
pause
