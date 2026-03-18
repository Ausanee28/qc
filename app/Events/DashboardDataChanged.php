<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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
}
