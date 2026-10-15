<?php

namespace App\Http\Resources\Mobile;

use App\Services\TicketChatService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'sender' => [
                'id' => $this->sender_id,
                'name' => $this->sender?->name ?? 'Unknown',
                'role' => $this->sender?->role?->role_name,
                'initials' => TicketChatService::initials($this->sender?->name),
            ],
            'is_me' => $this->sender_id === $request->user()->id,
            'is_read' => (bool) $this->is_read,
            'attachments' => AttachmentResource::collection($this->attachments),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
