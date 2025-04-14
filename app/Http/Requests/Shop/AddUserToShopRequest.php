<?php
namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class AddUserToShopRequest extends FormRequest
{
    public function authorize()
    {
        return true; // You can change this logic based on your app requirements
    }

    public function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
        ];
    }
}
