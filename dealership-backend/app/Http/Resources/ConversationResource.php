<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender' => $this->sender,
            'user' => $this->when($this->user_id, fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'message' => $this->message,
            'sent_at' => $this->sent_at,
        ];
    }
}
