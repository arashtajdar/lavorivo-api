<?php
namespace App\Http\Requests\ShiftSwap;

use Illuminate\Foundation\Http\FormRequest;

class CreateShiftSwapRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'shop_id' => 'required|exists:shops,id',
            'shift_label_id' => 'required|exists:shift_labels,id',
            'shift_date' => 'required|date',
            'requester_id' => 'required|exists:users,id',
            'requested_id' => 'required|exists:users,id',
        ];
    }
}
