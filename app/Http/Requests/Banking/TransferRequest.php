<?php

namespace App\Http\Requests\Banking;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        if ($this->has('transaction_type')) {
            $this->merge([
                'transaction_type' => strtolower($this->transaction_type),
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
            'source_account_number' => 'required|numeric|different:destination_account_number',
            'destination_account_number' => 'required|numeric',
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'transaction_type' => 'sometimes|string|in:transfer',
        ];
    }
}
