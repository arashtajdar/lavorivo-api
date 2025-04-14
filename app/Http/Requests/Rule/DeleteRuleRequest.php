<?php

namespace App\Http\Requests\Rule;

use Illuminate\Foundation\Http\FormRequest;

class DeleteRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:users,id',
            'shop_id' => 'required|exists:shops,id',
            'rule_type' => 'required|string',
            'rule_data' => 'required|array',
        ];
    }
}
