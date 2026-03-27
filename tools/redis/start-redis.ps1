$redisProcess = Get-Process redis-server -ErrorAction SilentlyContinue

if ($redisProcess) {
    exit 0
}

$redisExe = Join-Path $PSScriptRoot 'bin\redis-server.exe'
$redisConfig = Join-Path $PSScriptRoot 'bin\redis.windows.conf'

if (-not (Test-Path $redisExe) -or -not (Test-Path $redisConfig)) {
    throw 'Redis executable or config file is missing.'
}

Start-Process -FilePath $redisExe -ArgumentList $redisConfig -WorkingDirectory (Split-Path $PSScriptRoot -Parent)
