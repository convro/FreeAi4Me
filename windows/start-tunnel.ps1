# FreeAi4Me - Reverse SSH Tunnel dla Windows
# Tworzy tunel z Windows PC do VPS

param(
    [Parameter(Mandatory=$true)]
    [string]$VpsHost,

    [Parameter(Mandatory=$false)]
    [string]$VpsUser = "root",

    [Parameter(Mandatory=$false)]
    [int]$VpsPort = 22,

    [Parameter(Mandatory=$false)]
    [int]$LocalPort = 1234,  # Port LM Studio (domyslny)

    [Parameter(Mandatory=$false)]
    [int]$RemotePort = 8080,  # Port na VPS gdzie bedzie dostepne API

    [Parameter(Mandatory=$false)]
    [string]$SshKeyPath = "$env:USERPROFILE\.ssh\id_rsa"
)

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "   FreeAi4Me - Reverse SSH Tunnel" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Sprawdz czy LM Studio dziala
Write-Host "[1/4] Sprawdzam czy LM Studio API dziala na porcie $LocalPort..." -ForegroundColor Yellow

try {
    $response = Invoke-WebRequest -Uri "http://localhost:$LocalPort/v1/models" -TimeoutSec 5 -ErrorAction Stop
    Write-Host "      OK! LM Studio API dziala." -ForegroundColor Green
} catch {
    Write-Host "      UWAGA: LM Studio API nie odpowiada na porcie $LocalPort" -ForegroundColor Red
    Write-Host "      Upewnij sie ze:" -ForegroundColor Yellow
    Write-Host "      1. LM Studio jest uruchomione" -ForegroundColor Yellow
    Write-Host "      2. Zaladowany jest model" -ForegroundColor Yellow
    Write-Host "      3. Serwer lokalny jest wlaczony (Local Server w LM Studio)" -ForegroundColor Yellow
    Write-Host ""
    $continue = Read-Host "Czy kontynuowac mimo to? (t/n)"
    if ($continue -ne "t") {
        exit 1
    }
}

# Sprawdz czy SSH jest dostepne
Write-Host "[2/4] Sprawdzam SSH..." -ForegroundColor Yellow

$sshPath = Get-Command ssh -ErrorAction SilentlyContinue
if (-not $sshPath) {
    Write-Host "      SSH nie znalezione! Zainstaluj OpenSSH lub Git for Windows." -ForegroundColor Red
    Write-Host "      Uruchom: .\install-ssh.ps1" -ForegroundColor Yellow
    exit 1
}
Write-Host "      OK! SSH dostepne." -ForegroundColor Green

# Sprawdz klucz SSH
Write-Host "[3/4] Sprawdzam klucz SSH..." -ForegroundColor Yellow

if (-not (Test-Path $SshKeyPath)) {
    Write-Host "      Klucz SSH nie znaleziony: $SshKeyPath" -ForegroundColor Red
    Write-Host "      Generuje nowy klucz..." -ForegroundColor Yellow

    ssh-keygen -t rsa -b 4096 -f $SshKeyPath -N '""'

    Write-Host ""
    Write-Host "      WAZNE: Skopiuj ponizszy klucz publiczny na VPS:" -ForegroundColor Cyan
    Write-Host ""
    Get-Content "$SshKeyPath.pub"
    Write-Host ""
    Write-Host "      Na VPS wykonaj:" -ForegroundColor Yellow
    Write-Host "      echo 'TWOJ_KLUCZ' >> ~/.ssh/authorized_keys" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Nacisnij Enter gdy skopiujesz klucz na VPS"
}
Write-Host "      OK! Klucz SSH istnieje." -ForegroundColor Green

# Uruchom tunel
Write-Host "[4/4] Uruchamiam reverse SSH tunnel..." -ForegroundColor Yellow
Write-Host ""
Write-Host "      Konfiguracja:" -ForegroundColor Cyan
Write-Host "      - VPS: $VpsUser@$VpsHost:$VpsPort" -ForegroundColor White
Write-Host "      - Lokalny port (LM Studio): $LocalPort" -ForegroundColor White
Write-Host "      - Zdalny port (VPS): $RemotePort" -ForegroundColor White
Write-Host ""
Write-Host "      API bedzie dostepne pod:" -ForegroundColor Green
Write-Host "      https://$VpsHost/v1" -ForegroundColor Green
Write-Host ""
Write-Host "      Nacisnij Ctrl+C aby zakonczyc tunel" -ForegroundColor Yellow
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Parametry SSH:
# -R - reverse tunnel (VPS:8080 -> localhost:1234)
# -N - nie wykonuj zdalnych komend
# -T - nie alokuj pseudo-terminala
# -o ServerAliveInterval=60 - keepalive co 60 sekund
# -o ServerAliveCountMax=3 - max 3 nieudane keepalive
# -o ExitOnForwardFailure=yes - zakoncz jesli forwarding nie dziala
# -o StrictHostKeyChecking=accept-new - akceptuj nowe klucze hosta

$sshArgs = @(
    "-R", "127.0.0.1:${RemotePort}:localhost:${LocalPort}",
    "-N",
    "-T",
    "-o", "ServerAliveInterval=60",
    "-o", "ServerAliveCountMax=3",
    "-o", "ExitOnForwardFailure=yes",
    "-o", "StrictHostKeyChecking=accept-new",
    "-i", $SshKeyPath,
    "-p", $VpsPort,
    "${VpsUser}@${VpsHost}"
)

# Petla z automatycznym reconnect
while ($true) {
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Laczenie z $VpsHost..." -ForegroundColor Cyan

    & ssh @sshArgs

    $exitCode = $LASTEXITCODE
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Tunel rozlaczony (kod: $exitCode)" -ForegroundColor Yellow

    Write-Host "Ponowne laczenie za 5 sekund... (Ctrl+C aby zakonczyc)" -ForegroundColor Yellow
    Start-Sleep -Seconds 5
}
