@echo off
chcp 65001 >nul
echo ========================================
echo   URL Shortener - Production Build
echo ========================================
echo.

REM STEP 1: Check Frontend dependencies
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

REM STEP 2: Run production build
echo.
echo [INFO] Building frontend for production...
cd Frontend
call npm run build
if errorlevel 1 (
    echo [ERROR] Build failed
    cd ..
    pause
    exit /b 1
)
cd ..

REM STEP 3: Verify build output
echo.
echo [INFO] Verifying build output...
if not exist public\dist\index.html (
    echo [ERROR] Build output not found: public\dist\index.html
    pause
    exit /b 1
)
echo [OK] Build output verified

REM STEP 4: Calculate and display build size
echo.
echo [INFO] Build size:
for /f %%A in ('dir /s public\dist ^| findstr "File(s)"') do (
    echo       %%A bytes
)

REM STEP 5: Display next steps
echo.
echo ========================================
echo   Build completed successfully!
echo   Output: public\dist\
echo ========================================
echo.
echo [NEXT] Run start.bat to launch production mode
echo.
pause
