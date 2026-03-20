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

        $options = (array) config('broadcasting.connections.reverb.options', []);
        $host = (string) ($options['host'] ?? '127.0.0.1');
        $port = (int) ($options['port'] ?? 8080);

        if ($host === '' || $port <= 0) {
            return false;
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, 0.05);

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }
}
