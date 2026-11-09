<?php

namespace App\Events;

use App\Http\Resources\KomentarResource;
use App\Models\Komentar;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Komentar baru disiarkan ke kanal privat materi (KP-15.1, KP-15.3).
 * ShouldBroadcastNow = langsung dikirim, tanpa antrean.
 */
class KomentarDibuat implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Komentar $komentar) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('materi.'.$this->komentar->materi_id)];
    }

    /**
     * Nama event di sisi frontend (Echo): komentar.dibuat
     */
    public function broadcastAs(): string
    {
        return 'komentar.dibuat';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return (new KomentarResource($this->komentar))->resolve();
    }
}