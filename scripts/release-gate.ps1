param(
    [int]$Headers = 50000,
    [int]$DetailsPerHeader = 4,
    [int]$Chunk = 1000,
    [int]$WindowDays = 30,
    [int]$AggregateDays = 120
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot

function Invoke-Step {
    param(
        [Parameter(Mandatory = $true)][string]$Command,
        [Parameter(Mandatory = $true)][string]$Label
    )

    Write-Host ""
    Write-Host "== $Label =="
    cmd /c $Command | Out-Host

    if ($LASTEXITCODE -ne 0) {
        throw "Command failed: $Command (exit $LASTEXITCODE)"
    }
}

Push-Location $root
try {
    Invoke-Step -Label "Build assets" -Command "npm run build"
    Invoke-Step -Label "Redis health" -Command "php artisan qc:redis-health --fail-on-error"
    Invoke-Step -Label "Refresh aggregates" -Command "php artisan qc:aggregate-metrics --days=$AggregateDays"
    Invoke-Step -Label "Warm hot caches" -Command "php artisan qc:warm"
    Invoke-Step -Label "Scale benchmark gate" -Command "php artisan qc:scale-benchmark --headers=$Headers --details-per-header=$DetailsPerHeader --chunk=$Chunk --window-days=$WindowDays"

    Write-Host ""
    Write-Host "Release gate passed."
}
finally {
    Pop-Location
}

