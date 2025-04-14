<?php
namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'subscription_id' => 'required|exists:subscriptions,id'
        ];
    }
}
