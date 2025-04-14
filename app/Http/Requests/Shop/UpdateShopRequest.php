<?php
namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopRequest extends FormRequest
{
    public function authorize()
    {
        return true; // You can change this logic based on your app requirements
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
        ];
    }
}
