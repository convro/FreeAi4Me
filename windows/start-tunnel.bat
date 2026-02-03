@echo off
REM FreeAi4Me - Prosty starter tunelu
REM Edytuj ponizsze zmienne przed uruchomieniem!

set VPS_HOST=twoja-domena.pl
set VPS_USER=root
set VPS_PORT=22
set LOCAL_PORT=1234
set REMOTE_PORT=8080

echo ============================================
echo    FreeAi4Me - Reverse SSH Tunnel
echo ============================================
echo.
echo Konfiguracja:
echo   VPS: %VPS_USER%@%VPS_HOST%:%VPS_PORT%
echo   Lokalny port (LM Studio): %LOCAL_PORT%
echo   Zdalny port (VPS): %REMOTE_PORT%
echo.
echo API bedzie dostepne pod: https://%VPS_HOST%/v1
echo.
echo Nacisnij Ctrl+C aby zakonczyc
echo ============================================
echo.

:loop
echo [%time%] Laczenie z %VPS_HOST%...

ssh -R 127.0.0.1:%REMOTE_PORT%:localhost:%LOCAL_PORT% ^
    -N -T ^
    -o ServerAliveInterval=60 ^
    -o ServerAliveCountMax=3 ^
    -o ExitOnForwardFailure=yes ^
    -o StrictHostKeyChecking=accept-new ^
    -p %VPS_PORT% ^
    %VPS_USER%@%VPS_HOST%

echo [%time%] Tunel rozlaczony. Ponowne laczenie za 5 sekund...
timeout /t 5 /nobreak > nul
goto loop
