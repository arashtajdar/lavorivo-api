<?php
namespace App\Http\Requests\ShiftSwap;

use Illuminate\Foundation\Http\FormRequest;

class ApproveShiftSwapRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|exists:shift_swap_requests,id',
        ];
    }
}
