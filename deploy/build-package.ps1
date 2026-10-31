<#
.SYNOPSIS
    Prépare le paquet d'installation Windows de JY Solutions Gestion dans dist\JYSolutions\.

.DESCRIPTION
    - code de l'application (fichiers suivis par git, modifications non commitées comprises),
      sans tests, docs, node_modules, .git, ni le .env et la base de ce PC ;
    - vendor installé avec composer install --no-dev --optimize-autoloader ;
    - assets construits (vite build) dans public\build ;
    - .env.production, scripts Windows (deploy\windows) et dossier php\ vide avec son README et son php.ini.
    Ensuite : voir deploy\INSTALL-PC-PAPA.md.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File deploy\build-package.ps1
    powershell -ExecutionPolicy Bypass -File deploy\build-package.ps1 -Zip
#>
[CmdletBinding()]
param(
    # Crée aussi dist\JYSolutions-AAAA-MM-JJ.zip (pratique pour copier sur une clé USB).
    [switch] $Zip
)

$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$root = Split-Path -Parent $PSScriptRoot
$dist = Join-Path $root 'dist'
$out = Join-Path $dist 'JYSolutions'
$windows = Join-Path $PSScriptRoot 'windows'

function Write-Step([string] $text) { Write-Host "`n== $text" -ForegroundColor Cyan }

# Lance un programme et s'arrête s'il échoue.
function Invoke-Tool([string] $exe, [string[]] $arguments, [string] $directory) {
    Push-Location $directory
    try {
        & $exe @arguments
        if ($LASTEXITCODE -ne 0) { throw "$exe $($arguments -join ' ') a échoué (code $LASTEXITCODE)." }
    } finally {
        Pop-Location
    }
}

# Écrit un fichier texte avec fins de ligne Windows (CRLF) dans l'encodage attendu par Windows :
# .bat / .ini / .txt : UTF-8 sans BOM ; .ps1 : UTF-8 avec BOM (PowerShell 5.1) ; .vbs : UTF-16 (Windows Script Host).
function Write-WindowsText([string] $source, [string] $target) {
    $text = [System.IO.File]::ReadAllText($source, [System.Text.Encoding]::UTF8)
    $text = $text -replace "`r?`n", "`r`n"
    $encoding = switch ([System.IO.Path]::GetExtension($target).ToLower()) {
        '.ps1' { New-Object System.Text.UTF8Encoding $true }
        '.vbs' { New-Object System.Text.UnicodeEncoding $false, $true }
        default { New-Object System.Text.UTF8Encoding $false }
    }
    New-Item -ItemType Directory -Force -Path (Split-Path -Parent $target) | Out-Null
    [System.IO.File]::WriteAllText($target, $text, $encoding)
}

Write-Step 'Vérifications'
foreach ($tool in 'git', 'php', 'composer', 'node') {
    if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) { throw "$tool est introuvable sur ce PC." }
}
if (-not (Test-Path (Join-Path $root 'node_modules\vite'))) { throw 'node_modules manque : lancer « npm install » une fois.' }
$dirty = git -C $root status --porcelain
if ($dirty) { Write-Warning 'Des modifications ne sont pas commitées : elles sont incluses telles quelles dans le paquet.' }

Write-Step "Copie du code dans $out"
if (Test-Path $out) { Remove-Item -LiteralPath $out -Recurse -Force }
New-Item -ItemType Directory -Force -Path $out | Out-Null

# Fichiers suivis par git et nouveaux fichiers non ignorés : .env, database.sqlite, vendor, node_modules,
# public\build et storage\* sont ignorés par .gitignore, donc jamais copiés.
$excluded = '^(tests|docs|deploy|dist|\.github|node_modules)/|^(\.gitignore|\.gitattributes|\.editorconfig|\.env\.example|phpunit\.xml|package\.json|package-lock\.json|vite\.config\.js|tailwind\.config\.js|postcss\.config\.js|README\.md|CLAUDE\.md)$'
$files = git -C $root -c core.quotepath=off ls-files --cached --others --exclude-standard
if ($LASTEXITCODE -ne 0) { throw 'git ls-files a échoué.' }
$count = 0
foreach ($file in $files) {
    if ($file -match $excluded) { continue }
    $source = Join-Path $root $file
    if (-not (Test-Path -LiteralPath $source -PathType Leaf)) { continue }   # supprimé mais pas encore commité
    $target = Join-Path $out $file
    New-Item -ItemType Directory -Force -Path (Split-Path -Parent $target) | Out-Null
    Copy-Item -LiteralPath $source -Destination $target
    $count++
}
Write-Host "  $count fichiers"

Write-Step 'Dépendances PHP : composer install --no-dev --optimize-autoloader'
Invoke-Tool 'composer' @('install', '--no-dev', '--optimize-autoloader', '--no-interaction', '--no-progress', '--prefer-dist') $out

Write-Step 'Assets : vite build'
# node + vite directement : « npm run build » peut rester bloqué sur ce PC (voir CLAUDE.md).
Invoke-Tool 'node' @('node_modules/vite/bin/vite.js', 'build') $root
Copy-Item -LiteralPath (Join-Path $root 'public\build') -Destination (Join-Path $out 'public\build') -Recurse

Write-Step 'Réglages de production : .env.production'
$envProduction = @'
APP_NAME="JY Solutions Gestion"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://127.0.0.1:8765
APP_TIMEZONE=Africa/Casablanca

APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_FR

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=daily
LOG_DAILY_DAYS=30
LOG_LEVEL=warning

DB_CONNECTION=sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=480
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=database
MAIL_MAILER=log

# install.bat ajoute ici APP_KEY et BACKUP_PATH (dossier des sauvegardes de backup.bat).

'@
[System.IO.File]::WriteAllText((Join-Path $out '.env.production'), ($envProduction -replace "`r?`n", "`r`n"), (New-Object System.Text.UTF8Encoding $false))

Write-Step 'Scripts Windows et dossier php\'
foreach ($file in Get-ChildItem -LiteralPath $windows -Recurse -File) {
    $relative = $file.FullName.Substring($windows.Length + 1)
    $target = Join-Path $out $relative
    if ($file.Extension -in '.bat', '.ps1', '.vbs', '.txt', '.ini', '.php') {
        Write-WindowsText $file.FullName $target
    } else {
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $target) | Out-Null
        Copy-Item -LiteralPath $file.FullName -Destination $target
    }
    Write-Host "  $relative"
}

$commit = (git -C $root rev-parse --short HEAD).Trim()
$suffix = if ($dirty) { ' + modifications non commitées' } else { '' }
$version = "$(Get-Date -Format 'dd/MM/yyyy HH:mm') - commit $commit$suffix"
[System.IO.File]::WriteAllText((Join-Path $out 'VERSION.txt'), "$version`r`n", (New-Object System.Text.UTF8Encoding $false))

Write-Step 'Contrôles'
$mustExist = 'artisan', 'vendor\autoload.php', 'public\build\manifest.json', '.env.production', 'install.bat',
    'JY Solutions.vbs', 'stop.bat', 'backup.bat', 'install-backup-task.bat', 'update.bat', 'php\README.txt', 'php\php.ini'
foreach ($path in $mustExist) {
    if (-not (Test-Path -LiteralPath (Join-Path $out $path))) { throw "Manquant dans le paquet : $path" }
}
$mustNotExist = '.env', 'database\database.sqlite', 'node_modules', 'tests', '.git', 'vendor\pestphp', 'vendor\fakerphp', 'php\php.exe'
foreach ($path in $mustNotExist) {
    if (Test-Path -LiteralPath (Join-Path $out $path)) { throw "Ne doit pas être dans le paquet : $path" }
}
$size = (Get-ChildItem -LiteralPath $out -Recurse -File -Force | Measure-Object -Property Length -Sum).Sum / 1MB
Write-Host ("  Paquet : {0:N0} Mo, version {1}" -f $size, $version)

if ($Zip) {
    Write-Step 'Archive zip'
    $zipPath = Join-Path $dist ("JYSolutions-{0}.zip" -f (Get-Date -Format 'yyyy-MM-dd'))
    if (Test-Path $zipPath) { Remove-Item -LiteralPath $zipPath -Force }
    Compress-Archive -Path $out -DestinationPath $zipPath
    Write-Host "  $zipPath"
}

Write-Host "`nPaquet prêt : $out" -ForegroundColor Green
Write-Host 'Suite : deploy\INSTALL-PC-PAPA.md'
