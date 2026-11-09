<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Komentar dihapus, disiarkan ke kanal privat materi (KP-15.2).
 * Data hanya berisi id, agar peramban lain menghapus komentar tersebut dari daftar.
 */
class KomentarDihapus implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $materiId,
        public int $komentarId,
        public ?int $parentId = null,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('materi.'.$this->materiId)];
    }

    public function broadcastAs(): string
    {
        return 'komentar.dihapus';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->komentarId,
            'parent_id' => $this->parentId,
        ];
    }
}