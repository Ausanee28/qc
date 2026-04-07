<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DashboardDataChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    private const TRANSPORT_CHECK_CACHE_KEY = 'dashboard.broadcast.transport.available';
    private const TRANSPORT_CHECK_FAILURE_CACHE_KEY = 'dashboard.broadcast.transport.unavailable';

    public string $updatedAt;

    public function __construct()
    {
        $this->updatedAt = now()->toIso8601String();
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('dashboard.global')];
    }

    public function broadcastAs(): string
    {
        return 'dashboard.updated';
    }

    public static function dispatchSafely(): void
    {
        if (!self::canAttemptBroadcast()) {
            return;
        }

        try {
            event(new self());
        } catch (\Throwable $e) {
            Log::warning('Skipping dashboard broadcast due to transport error.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private static function canAttemptBroadcast(): bool
    {
        $connection = (string) config('broadcasting.default', 'null');

        if ($connection === 'null') {
            return false;
        }

        if ($connection !== 'reverb') {
            return true;
        }

        if (cache()->get(self::TRANSPORT_CHECK_CACHE_KEY, false) === true) {
            return true;
        }

        if (cache()->get(self::TRANSPORT_CHECK_FAILURE_CACHE_KEY, false) === true) {
            return false;
        }

        $options = (array) config('broadcasting.connections.reverb.options', []);
        $host = (string) ($options['host'] ?? '127.0.0.1');
        if (strtolower(trim($host)) === 'localhost') {
            $host = '127.0.0.1';
        }
        $port = (int) ($options['port'] ?? 8080);

        if ($host === '' || $port <= 0) {
            return false;
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, 0.05);

        if ($socket === false) {
            cache()->put(self::TRANSPORT_CHECK_FAILURE_CACHE_KEY, true, now()->addSeconds(15));
            return false;
        }

        fclose($socket);
        cache()->put(self::TRANSPORT_CHECK_CACHE_KEY, true, now()->addSeconds(30));

        return true;
    }
}
