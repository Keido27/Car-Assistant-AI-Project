<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stock_number' => $this->stock_number,
            'brand' => $this->brand,
            'model' => $this->model,
            'variant' => $this->variant,
            'year' => $this->year,
            'price' => $this->price,
            'mileage' => $this->mileage,
            'transmission' => $this->transmission,
            'fuel_type' => $this->fuel_type,
            'condition_notes' => $this->condition_notes,
            'color' => $this->color,
            'plate_region' => $this->plate_region,
            'status' => $this->status,
            'cover_photo_url' => $this->photos->firstWhere('is_cover', true)?->url
                ?? $this->photos->first()?->url,
            'photos' => CarPhotoResource::collection($this->whenLoaded('photos')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
