# Étapes d'installation appelées par install.bat.
#   -Etape php        écrit le chemin complet de php\ext dans php\php.ini
#   -Etape raccourci  convertit public\logo.png en icône et crée le raccourci « JY Solutions » sur le bureau
param(
    [Parameter(Mandatory = $true)]
    [ValidateSet('php', 'raccourci')]
    [string] $Etape
)

$ErrorActionPreference = 'Stop'
$app = Split-Path -Parent $PSScriptRoot

# Icône Windows (.ico) à partir d'un PNG : l'image est centrée sur un carré transparent,
# en plusieurs tailles, chacune enregistrée en PNG dans le fichier .ico (format accepté depuis Windows Vista).
function ConvertTo-Ico([string] $png, [string] $ico) {
    Add-Type -AssemblyName System.Drawing
    $source = [System.Drawing.Image]::FromFile($png)
    try {
        $images = foreach ($size in 256, 64, 48, 32, 16) {
            $bitmap = New-Object System.Drawing.Bitmap $size, $size
            $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
            $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
            $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
            $graphics.Clear([System.Drawing.Color]::Transparent)
            $scale = [Math]::Min($size / $source.Width, $size / $source.Height)
            $w = [int][Math]::Round($source.Width * $scale)
            $h = [int][Math]::Round($source.Height * $scale)
            $graphics.DrawImage($source, [int](($size - $w) / 2), [int](($size - $h) / 2), $w, $h)
            $graphics.Dispose()
            $stream = New-Object System.IO.MemoryStream
            $bitmap.Save($stream, [System.Drawing.Imaging.ImageFormat]::Png)
            $bitmap.Dispose()
            , @($size, $stream.ToArray())
        }
    } finally {
        $source.Dispose()
    }

    $out = New-Object System.IO.MemoryStream
    $writer = New-Object System.IO.BinaryWriter $out
    $writer.Write([UInt16] 0)              # réservé
    $writer.Write([UInt16] 1)              # type : icône
    $writer.Write([UInt16] $images.Count)
    $offset = 6 + 16 * $images.Count
    foreach ($image in $images) {
        $size = $image[0]; $bytes = $image[1]
        $side = if ($size -ge 256) { 0 } else { $size }   # 0 = 256 pixels
        $writer.Write([Byte] $side); $writer.Write([Byte] $side)
        $writer.Write([Byte] 0); $writer.Write([Byte] 0)  # couleurs, réservé
        $writer.Write([UInt16] 1); $writer.Write([UInt16] 32)
        $writer.Write([UInt32] $bytes.Length); $writer.Write([UInt32] $offset)
        $offset += $bytes.Length
    }
    foreach ($image in $images) { $writer.Write([byte[]] $image[1]) }
    $writer.Flush()
    [System.IO.File]::WriteAllBytes($ico, $out.ToArray())
    $writer.Dispose()
}

switch ($Etape) {
    'php' {
        $ini = Join-Path $app 'php\php.ini'
        if (-not (Test-Path -LiteralPath $ini)) { throw "Fichier introuvable : $ini" }
        $ext = Join-Path $app 'php\ext'
        $text = [System.IO.File]::ReadAllText($ini)
        $text = [regex]::Replace($text, '(?m)^extension_dir[ \t]*=[^\r\n]*', { param($m) "extension_dir = `"$ext`"" })
        [System.IO.File]::WriteAllText($ini, $text, (New-Object System.Text.UTF8Encoding $false))
        Write-Host "  php.ini : extension_dir = $ext"
    }

    'raccourci' {
        $ico = Join-Path $app 'outils\jysolutions.ico'
        ConvertTo-Ico (Join-Path $app 'public\logo.png') $ico

        $desktop = [Environment]::GetFolderPath('Desktop')
        $link = Join-Path $desktop 'JY Solutions.lnk'
        $shell = New-Object -ComObject WScript.Shell
        $shortcut = $shell.CreateShortcut($link)
        $shortcut.TargetPath = Join-Path $env:WINDIR 'System32\wscript.exe'
        $shortcut.Arguments = '"' + (Join-Path $app 'JY Solutions.vbs') + '"'
        $shortcut.WorkingDirectory = $app
        $shortcut.IconLocation = "$ico,0"
        $shortcut.Description = 'Devis et factures JY Solutions'
        $shortcut.Save()
        Write-Host "  Raccourci créé : $link"
    }
}
