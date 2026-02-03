# FreeAi4Me - Instalacja OpenSSH na Windows

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "   FreeAi4Me - Instalacja OpenSSH" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Sprawdz czy uruchomiono jako administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "UWAGA: Ten skrypt wymaga uprawnien administratora!" -ForegroundColor Red
    Write-Host "Kliknij prawym na PowerShell -> Uruchom jako administrator" -ForegroundColor Yellow
    exit 1
}

# Sprawdz czy SSH juz jest zainstalowane
$sshClient = Get-WindowsCapability -Online | Where-Object Name -like 'OpenSSH.Client*'

if ($sshClient.State -eq "Installed") {
    Write-Host "OpenSSH Client juz zainstalowany!" -ForegroundColor Green
} else {
    Write-Host "Instaluje OpenSSH Client..." -ForegroundColor Yellow
    Add-WindowsCapability -Online -Name OpenSSH.Client~~~~0.0.1.0
    Write-Host "OpenSSH Client zainstalowany!" -ForegroundColor Green
}

# Sprawdz ponownie
$sshPath = Get-Command ssh -ErrorAction SilentlyContinue
if ($sshPath) {
    Write-Host ""
    Write-Host "SSH jest dostepne: $($sshPath.Source)" -ForegroundColor Green
    Write-Host ""
    Write-Host "Mozesz teraz uruchomic: .\start-tunnel.ps1 -VpsHost twoja-domena.pl" -ForegroundColor Cyan
} else {
    Write-Host ""
    Write-Host "SSH nie zostalo znalezione. Sprobuj:" -ForegroundColor Red
    Write-Host "1. Zainstaluj Git for Windows (zawiera SSH)" -ForegroundColor Yellow
    Write-Host "   https://git-scm.com/download/win" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "2. Lub zainstaluj OpenSSH rucznie:" -ForegroundColor Yellow
    Write-Host "   Ustawienia -> Aplikacje -> Funkcje opcjonalne -> Dodaj funkcje -> OpenSSH Client" -ForegroundColor Yellow
}
