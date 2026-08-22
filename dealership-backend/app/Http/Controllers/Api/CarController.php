<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarRequest;
use App\Http\Resources\CarResource;
use App\Models\Car;
use App\Models\CarPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CarController extends Controller
{
    /**
     * GET /api/cars
     * Powers both the admin inventory table and can be reused directly by
     * the bot's search_inventory tool later (same Car::matching scope).
     */
    public function index(Request $request)
    {
        // Admin listing shows every status by default (unlike the bot's search_inventory
        // tool, which defaults to "ready" — see Car::scopeMatching). Pass status=ready
        // explicitly from the frontend if you only want to see sellable stock.
        $cars = Car::query()
            ->with('photos')
            ->when($request->filled('brand'), fn ($q) => $q->where('brand', 'like', '%' . $request->string('brand') . '%'))
            ->when($request->filled('model'), fn ($q) => $q->where('model', 'like', '%' . $request->string('model') . '%'))
            ->when($request->filled('year_min'), fn ($q) => $q->where('year', '>=', $request->integer('year_min')))
            ->when($request->filled('year_max'), fn ($q) => $q->where('year', '<=', $request->integer('year_max')))
            ->when($request->filled('price_max'), fn ($q) => $q->where('price', '<=', $request->integer('price_max')))
            ->when($request->filled('transmission'), fn ($q) => $q->where('transmission', $request->string('transmission')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q');
                $q->where(function ($sub) use ($term) {
                    $sub->where('brand', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%")
                        ->orWhere('stock_number', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return CarResource::collection($cars);
    }

    public function store(CarRequest $request)
    {
        $car = Car::create($request->validated());

        return new CarResource($car->load('photos'));
    }

    public function show(Car $car)
    {
        return new CarResource($car->load('photos'));
    }

    public function update(CarRequest $request, Car $car)
    {
        $car->update($request->validated());

        return new CarResource($car->load('photos'));
    }

    /**
     * Soft delete — we keep sold/removed cars around for reporting and so the
     * bot's conversation history still makes sense for old leads.
     */
    public function destroy(Car $car)
    {
        $car->delete();

        return response()->noContent();
    }

    /**
     * POST /api/cars/{car}/photos — multipart upload, stored on the
     * S3-compatible disk (R2/Spaces) configured in filesystems.php.
     */
    public function uploadPhoto(Request $request, Car $car)
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:8192'], // 8MB
        ]);

        $path = $request->file('photo')->store("cars/{$car->id}", config('filesystems.default'));

        $photo = $car->photos()->create([
            'path' => $path,
            'sort_order' => $car->photos()->max('sort_order') + 1,
            'is_cover' => $car->photos()->count() === 0,
        ]);

        return response()->json(['id' => $photo->id, 'url' => $photo->url], 201);
    }

    public function deletePhoto(Car $car, CarPhoto $photo)
    {
        abort_unless($photo->car_id === $car->id, 404);

        \Storage::disk(config('filesystems.default'))->delete($photo->path);
        $photo->delete();

        return response()->noContent();
    }
}
