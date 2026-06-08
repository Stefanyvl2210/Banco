<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        if ($this->has('account_type')) {
            $this->merge([
                'account_type' => strtolower($this->account_type),
            ]);
        }
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'account_type' => 'required|string|in:checking,savings',
        ];
    }
}
