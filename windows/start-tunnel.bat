@echo off
chcp 65001 > nul
title FreeAi4Me - SSH Tunnel

echo ============================================
echo    FreeAi4Me - Reverse SSH Tunnel
echo ============================================
echo.

REM === EDYTUJ TUTAJ SWOJE DANE ===
set VPS_HOST=WPISZ_SWOJA_DOMENE
set VPS_USER=root
REM ================================

REM Sprawdz czy domena jest ustawiona
if "%VPS_HOST%"=="WPISZ_SWOJA_DOMENE" (
    echo Podaj adres VPS ^(np. domena.pl^):
    set /p VPS_HOST="> "
    echo.
    echo Podaj nazwe uzytkownika SSH ^(domyslnie root^):
    set /p VPS_USER_INPUT="> "
    if not "%VPS_USER_INPUT%"=="" set VPS_USER=%VPS_USER_INPUT%
)

set LOCAL_PORT=1234
set REMOTE_PORT=9234

echo.
echo Konfiguracja:
echo   VPS: %VPS_USER%@%VPS_HOST%
echo   LM Studio port: %LOCAL_PORT%
echo   Tunel port: %REMOTE_PORT%
echo.
echo Po polaczeniu API bedzie na: https://%VPS_HOST%/v1
echo.
echo Ctrl+C aby zakonczyc
echo ============================================
echo.

:loop
echo [%time%] Lacze z %VPS_HOST%...

ssh -R 127.0.0.1:%REMOTE_PORT%:localhost:%LOCAL_PORT% -N -T -o ServerAliveInterval=60 -o ServerAliveCountMax=3 -o ExitOnForwardFailure=yes -o StrictHostKeyChecking=accept-new %VPS_USER%@%VPS_HOST%

echo [%time%] Rozlaczono. Ponowne laczenie za 5s...
timeout /t 5 /nobreak > nul
goto loop
