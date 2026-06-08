<?php

namespace App\Http\Requests\Banking;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawalRequest extends FormRequest
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
            'account_number' => 'required|numeric',
            'account_type' => 'sometimes|string|in:checking,savings',
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
        ];
    }
}
