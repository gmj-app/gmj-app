<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinishGameRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['score' => 'required|integer|min:0|max:10000000', 'distance' => 'required|numeric|min:0|max:10000000', 'duration_ms' => 'required|integer|min:1|max:1200000', 'collectible_count' => 'required|integer|min:0|max:10000', 'powerup_pickup_count' => 'required|integer|min:0|max:1000', 'powerup_use_count' => 'required|integer|min:0|max:1000', 'maximum_speed_tier' => 'required|integer|min:1|max:20', 'client_version' => 'required|string|max:64', 'events' => 'nullable|array|max:120', 'events.*.t' => 'required_with:events|integer|min:0|max:1200000', 'events.*.e' => 'required_with:events|string|max:24'];
    }
}
