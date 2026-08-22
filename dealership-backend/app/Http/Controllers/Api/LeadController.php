<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadUpdateRequest;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /**
     * GET /api/leads
     * Default sort surfaces the ones needing attention first — this list
     * is what a salesperson checks between customers.
     */
    public function index(Request $request)
    {
        $leads = Lead::query()
            ->with(['car:id,brand,model,year,stock_number', 'assignee:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('assigned_to'), fn ($q) => $q->where('assigned_to', $request->integer('assigned_to')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q');
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
            })
            ->orderByRaw("status = 'needs_handoff' desc") // handoffs float to the top
            ->orderByDesc('last_message_at')
            ->paginate($request->integer('per_page', 20));

        return LeadResource::collection($leads);
    }

    public function show(Lead $lead)
    {
        return new LeadResource($lead->load(['car.photos', 'assignee', 'conversations.user']));
    }

    /**
     * Admin does NOT create leads directly (the bot does, via create_lead).
     * This endpoint only updates status/assignment/notes — e.g. taking over
     * a handoff, reassigning, or marking converted/lost.
     */
    public function update(LeadUpdateRequest $request, Lead $lead)
    {
        $lead->update($request->validated());

        // Taking a lead over from the bot should read cleanly in the thread.
        if ($request->has('status') && $request->input('status') === 'human_handling' && $lead->wasChanged('status')) {
            $lead->conversations()->create([
                'sender' => 'human',
                'user_id' => $request->user()->id,
                'message' => "{$request->user()->name} has taken over this conversation.",
                'sent_at' => now(),
            ]);
        }

        return new LeadResource($lead->fresh(['car', 'assignee']));
    }
}
