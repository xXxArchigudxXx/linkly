@echo off
chcp 65001 >nul
echo ========================================
echo   URL Shortener - API Test Script
echo ========================================
echo.

REM Check if service is running
curl -s http://localhost:8080/health >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Service is not running. Run start.bat first.
    pause
    exit /b 1
)

echo [1] Health Check
echo ----------------------------------------
curl -s http://localhost:8080/health
echo.
echo.

echo [2] CORS Preflight Test (OPTIONS)
echo ----------------------------------------
curl -s -X OPTIONS http://localhost:8080/api/v1/links -H "Origin: http://localhost:3000" -H "Access-Control-Request-Method: POST" -H "Access-Control-Request-Headers: Content-Type" -D -
echo.
echo.

echo [3] Register User
echo ----------------------------------------
curl -s -X POST http://localhost:8080/api/v1/auth/register -H "Content-Type: application/json" -d "{\"email\":\"test@example.com\",\"password\":\"password123\"}"
echo.
echo.

echo [4] Login
echo ----------------------------------------
curl -s -X POST http://localhost:8080/api/v1/auth/login -H "Content-Type: application/json" -d "{\"email\":\"test@example.com\",\"password\":\"password123\"}"
echo.
echo.

echo [5] Create Short Link
echo ----------------------------------------
curl -s -X POST http://localhost:8080/api/v1/links -H "Content-Type: application/json" -d "{\"url\":\"https://google.com\"}"
echo.
echo.

echo [6] Get Link Info (replace {code} with actual code from step 5)
echo ----------------------------------------
echo curl -s http://localhost:8080/api/v1/links/{code}
echo.

echo [7] SPA Fallback Test (GET /dashboard)
echo ----------------------------------------
curl -s -I http://localhost:8080/dashboard | findstr "HTTP Content-Type"
echo.
echo [INFO] Should return 200 with text/html for SPA routes
echo.

echo ========================================
echo   Tests completed!
echo ========================================
pause
