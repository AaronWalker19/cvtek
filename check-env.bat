@echo off
echo.
echo ===============================================
echo   CVTEK Development Environment Check
echo ===============================================
echo.

echo [1/4] Checking Node API Server (port 5000)...
curl -s http://localhost:5000/api/health > nul
if %ERRORLEVEL% EQU 0 (
    echo   ✓ Node API Server is running on port 5000
) else (
    echo   ✗ Node API Server is NOT running on port 5000
    echo   ACTION: Start the server with: npm start
    goto :END
)

echo.
echo [2/4] Checking React Dev Server (port 3000)...
curl -s http://localhost:3000 > nul
if %ERRORLEVEL% EQU 0 (
    echo   ✓ React Dev Server is running on port 3000
) else (
    echo   ✗ React Dev Server is NOT running on port 3000
    echo   ACTION: Start React with: cd client && npm start
    goto :END
)

echo.
echo [3/4] Testing setupProxy (should redirect to port 5000)...
for /f "tokens=1" %%A in ('curl -s -w "%%{http_code}" http://localhost:3000/api/health -o nul') do set HTTP_CODE=%%A
if %HTTP_CODE% EQU 200 (
    echo   ✓ setupProxy is working correctly
) else (
    echo   ✗ setupProxy returned HTTP %HTTP_CODE%
    echo   This might mean the request was not proxied
)

echo.
echo [4/4] Direct API test...
curl -s http://localhost:5000/api/health | findstr /C:"status" > nul
if %ERRORLEVEL% EQU 0 (
    echo   ✓ API is responding with valid JSON
) else (
    echo   ✗ API is not responding with valid JSON
)

echo.
echo ===============================================
echo   ✓ All systems operational!
echo ===============================================
echo.
echo 🌐 Frontend: http://localhost:3000
echo 🔗 Backend:  http://localhost:5000
echo.

:END
pause
