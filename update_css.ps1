$html = Get-Content 'public/preview.html' -Raw
$cssMatch = [regex]::Match($html, '(?s)<style>(.*?)</style>')
if ($cssMatch.Success) {
    $css = $cssMatch.Groups[1].Value.Trim()
    $css = $css -replace '\.page \{\s*display: none\s*\}', '.page { display: block }'
    $css = $css -replace '\.page\.show \{\s*display: block\s*\}', ''
    
    $appCss = Get-Content 'resources/css/app.css' -Raw
    $topPartMatch = [regex]::Match($appCss, '(?s)^.*?overflow:\s*hidden;\s*\}')
    if ($topPartMatch.Success) {
        $topPart = $topPartMatch.Value
        $finalCss = $topPart + "`n`n" + $css + "`n}`n"
        Set-Content -Path 'resources/css/app.css' -Value $finalCss -Encoding UTF8
        Write-Output "Successfully updated app.css"
    } else {
        Write-Output "Could not find top part of app.css"
    }
} else {
    Write-Output "Could not find style block"
}
