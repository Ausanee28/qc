# Redis Fast Path Runbook

Use this when you want the QC app to prefer Redis for hot-path cache and queue work while still falling back safely if Redis is unavailable.

## What Changed

- `predis/predis` is installed so the app can use Redis without the `phpredis` PHP extension.
- Cache now defaults to Laravel's `failover` store with `redis -> database -> array`.
- Queue now defaults to Laravel's `failover` connection with `redis -> database -> deferred`.
- A new `qc:warm` Artisan command preloads dashboard, workflow, and performance caches.

## Local Redis On Windows

This repo already includes Redis binaries under:

- `tools/redis/bin/redis-server.exe`
- `tools/redis/bin/redis.windows.conf`
- `tools/redis/bin/redis-cli.exe`

Start Redis in a dedicated terminal:

```powershell
.\tools\redis\bin\redis-server.exe .\tools\redis\bin\redis.windows.conf
```

Confirm Redis is responding:

```powershell
.\tools\redis\bin\redis-cli.exe ping
```

Expected response:

```text
PONG
```

This repo also includes a helper script that starts Redis only if it is not already running:

```powershell
.\tools\redis\start-redis.ps1
```

You can wire this script into Windows Task Scheduler at logon so the QC app keeps its Redis fast path without needing a manual terminal each time.

## Recommended Env

Use these values in `.env` for the fast path:

```dotenv
CACHE_STORE=failover
QUEUE_CONNECTION=failover
SESSION_DRIVER=database
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_CONNECTION=default
REDIS_CACHE_CONNECTION=cache
```

## Warm Hot Caches

After deploy or after clearing caches, run:

```powershell
php artisan qc:warm
```

This warms:

- dashboard summary / primary / secondary payload caches
- receive-job option caches
- execute-test option and pending-job caches
- performance page caches

## Suggested Production Sequence

```powershell
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan qc:warm
```

## Notes

- Session storage is kept on `database` by default here so authentication stays stable even if Redis is restarted.
- Cache and queue will still operate if Redis is down because they now use Laravel failover chains.
