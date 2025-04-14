<?php
namespace App\Http\Requests\OffDay;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOffDayRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'off_date' => 'required|date',
            'reason' => 'nullable|string',
        ];
    }
}
