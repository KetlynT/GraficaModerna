$backendPath = Get-Location
$files = Get-ChildItem -Path $backendPath -Recurse -Include "*.cs" | Where-Object { $_.FullName -notmatch '\\(obj|bin)\\' -and $_.Name -notmatch '\.g\.cs$' }

$count = 0
foreach ($file in $files) {
    try {
        $content = Get-Content -Path $file.FullName -Raw
        $before = $content.Length
        
        $content = $content -replace '(?m)^\s*//.*$', ''
        $content = $content -replace '(?m)(\s+)//(.*)$', '$1'
        
        $after = $content.Length
        Set-Content -Path $file.FullName -Value $content -NoNewline
        
        $count++
        Write-Host "[$count] OK: $($file.Name) (-$($before - $after) bytes)"
    }
    catch {
        Write-Host "ERR: $($file.Name): $($_)"
    }
}
Write-Host "`nTotal de arquivos processados: $count"
