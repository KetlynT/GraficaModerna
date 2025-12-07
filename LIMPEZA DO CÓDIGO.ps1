# Caminhos das pastas
$rootPath = Get-Location
$backendPath = Join-Path $rootPath "backend"
$frontendPath = Join-Path $rootPath "frontend"

Write-Host "🧹 Limpando Backend..."
$backendFiles = Get-ChildItem -Path $backendPath -Recurse -Include "*.cs" `
    | Where-Object { $_.FullName -notmatch '\\(obj|bin)\\' -and $_.Name -notmatch '\.g\.cs$' }

$backendCount = 0
foreach ($file in $backendFiles) {
    try {
        $content = Get-Content -Path $file.FullName -Raw
        $before = $content.Length

        # Remove comentários de linha
        $content = $content -replace '(?m)^\s*//.*$', ''
        # Comentário no final da linha
        $content = $content -replace '(?m)(\s+)//(.*)$', '$1'

        $after = $content.Length
        Set-Content -Path $file.FullName -Value $content -NoNewline

        $backendCount++
        Write-Host "[BACK] OK: $($file.Name) (-$($before - $after) bytes)"
    }
    catch {
        Write-Host "[ERR] BACK: $($file.Name): $($_)"
    }
}

Write-Host "`n--- Backend finalizado: $backendCount arquivos. ---`n"


Write-Host "🧹 Limpando Frontend..."
$frontendFiles = Get-ChildItem -Path $frontendPath -Recurse -Include "*.js","*.ts","*.jsx","*.tsx"

$frontendCount = 0
foreach ($file in $frontendFiles) {
    try {
        $content = Get-Content -Path $file.FullName -Raw
        $before = $content.Length

        # Remove comentários de linha: //
        $content = $content -replace '(?m)^\s*//.*$', ''
        $content = $content -replace '(?m)(\s+)//(.*)$', '$1'

        # Remove comentários de bloco: /* ... */
        $content = $content -replace '/\*[\s\S]*?\*/', ''

        $after = $content.Length
        Set-Content -Path $file.FullName -Value $content -NoNewline

        $frontendCount++
        Write-Host "[FRONT] OK: $($file.Name) (-$($before - $after) bytes)"
    }
    catch {
        Write-Host "[ERR] FRONT: $($file.Name): $($_)"
    }
}

Write-Host "`n--- Frontend finalizado: $frontendCount arquivos. ---`n"

Write-Host "`nTOTAL DE ARQUIVOS ALTERADOS:"
Write-Host "Backend:  $backendCount"
Write-Host "Frontend: $frontendCount"
Write-Host "TOTAL: $( $backendCount + $frontendCount )"
