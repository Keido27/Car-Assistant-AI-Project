<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'wa_thread_id' => $this->wa_thread_id,
            'status' => $this->status,
            'interest_summary' => $this->interest_summary,
            'car' => new CarResource($this->whenLoaded('car')),
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
            ]),
            'last_message_at' => $this->last_message_at,
            'conversations' => ConversationResource::collection($this->whenLoaded('conversations')),
            'created_at' => $this->created_at,
        ];
    }
}
