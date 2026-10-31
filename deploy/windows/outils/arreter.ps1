# Arrête le serveur de JY Solutions : uniquement le php.exe qui écoute sur le port de l'application
# (et le « php artisan serve » qui l'a lancé). Aucun autre programme PHP n'est touché.
param([int] $Port = 8765)

$ErrorActionPreference = 'Stop'

$listeners = @(Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
    Select-Object -ExpandProperty OwningProcess -Unique)

if ($listeners.Count -eq 0) {
    Write-Host "JY Solutions n'est pas en marche (rien n'écoute sur le port $Port)."
    exit 0
}

$stopped = 0
foreach ($id in $listeners) {
    $process = Get-CimInstance Win32_Process -Filter "ProcessId = $id"
    if (-not $process -or $process.Name -ne 'php.exe') {
        Write-Warning "Le port $Port est utilisé par $($process.Name) (n° $id) : ce n'est pas JY Solutions, il n'est pas arrêté."
        continue
    }

    $parent = Get-CimInstance Win32_Process -Filter "ProcessId = $($process.ParentProcessId)"
    Stop-Process -Id $id -Force
    $stopped++
    Write-Host "Serveur arrêté (php.exe n° $id)."

    # « php artisan serve --port=8765 » se termine normalement tout seul ; on s'en assure.
    if ($parent -and $parent.Name -eq 'php.exe' -and $parent.CommandLine -match 'artisan"?\s+serve' -and $parent.CommandLine -match "--port=$Port\b") {
        Stop-Process -Id $parent.ProcessId -Force -ErrorAction SilentlyContinue
    }
}

if ($stopped -gt 0) { Write-Host 'JY Solutions est arrêté.' }
