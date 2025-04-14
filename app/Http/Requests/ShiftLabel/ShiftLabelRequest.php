<?php
namespace App\Http\Requests\ShiftLabel;

use Illuminate\Foundation\Http\FormRequest;

class ShiftLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // You can modify this if you want to add authorization logic
    }

    public function rules(): array
    {
        return [
            'shop_id' => 'required|exists:shops,id',
            'label' => 'required|string|max:255',
            'default_duration_minutes' => 'nullable|integer|min:1',
            'applicable_days' => 'nullable|array',
        ];
    }
}
