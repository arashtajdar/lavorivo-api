<?php
namespace App\Http\Requests\OffDay;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOffDayStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|exists:user_off_days,id',
            'status' => 'required|in:0,1', // 1 = approved, 0 = rejected
        ];
    }
}
