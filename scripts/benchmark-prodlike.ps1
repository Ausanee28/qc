param(
    [int]$Runs = 5
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$timestamp = Get-Date -Format "yyyy-MM-dd-HHmmss"
$outDir = Join-Path $root "storage\app\performance"
$outFile = Join-Path $outDir "profile-prodlike-$timestamp.txt"

Write-Host "== QC prod-like benchmark =="
Write-Host "Project: $root"
Write-Host "Runs per route: $Runs"

function Invoke-Step {
    param(
        [Parameter(Mandatory = $true)][string]$Command,
        [Parameter(Mandatory = $false)][string]$Label = ""
    )

    if ($Label -ne "") {
        Write-Host $Label
    }

    cmd /c $Command | Out-Host

    if ($LASTEXITCODE -ne 0) {
        throw "Command failed: $Command (exit $LASTEXITCODE)"
    }
}

if (-not (Test-Path $outDir)) {
    New-Item -ItemType Directory -Path $outDir -Force | Out-Null
}

Push-Location $root
try {
    Write-Host "`n[1/5] Clearing framework caches..."
    Invoke-Step -Command "php artisan optimize:clear"

    Write-Host "`n[2/5] Building front-end assets..."
    try {
        Invoke-Step -Command "npm run build"
    } catch {
        Write-Warning "Build step failed in this shell context. Continuing with the latest built assets."
    }

    Write-Host "`n[3/5] Enabling production caches (config/routes/events + optional views)..."
    Invoke-Step -Command "php artisan config:cache"
    Invoke-Step -Command "php artisan event:cache"
    try {
        Invoke-Step -Command "php artisan route:cache"
    } catch {
        Write-Warning "route:cache failed. Continuing with config/events cache only."
    }

    try {
        Invoke-Step -Command "php artisan view:cache"
    } catch {
        Write-Warning "view:cache failed. Continuing with config/routes/events cache only."
    }

    Write-Host "`n[4/5] Running route performance profile..."
    $profileOutput = cmd /c "php artisan qc:profile --runs=$Runs" 2>&1
    $profileOutput | Out-Host

    Write-Host "`n[5/5] Writing report -> $outFile"
    @(
        "Generated: $(Get-Date -Format s)"
        "Runs: $Runs"
        ""
        $profileOutput
    ) | Out-File -FilePath $outFile -Encoding UTF8

    Write-Host "`nDone."
    Write-Host "Report: $outFile"
}
finally {
    Pop-Location
}
