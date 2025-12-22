<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('address') && isset($this->address['zipCode'])) {
            $address = $this->address;
            $address['zipCode'] = preg_replace('/\D/', '', $address['zipCode']);
            
            $this->merge(['address' => $address]);
        }
    }

    public function rules(): array
    {
        return [
            'address' => 'required|array',

            'couponCode' => 'nullable|string|max:50|exists:coupons,code',

            'shippingMethod' => 'required|string|max:50',
            
            'address.name'          => 'required|string|max:50',
            'address.receiverName'  => 'required|string|max:100',
            'address.zipCode'       => 'required|string|size:8',
            'address.street'        => 'required|string|max:200',
            'address.number'        => 'required|string|max:20',
            'address.neighborhood'  => 'required|string|max:100',
            'address.city'          => 'required|string|max:100',
            'address.state'         => 'required|string|size:2',
            'address.phoneNumber'   => 'required|string|max:20',
            'address.complement'    => 'nullable|string|max:100',
            'address.reference'     => 'nullable|string|max:200',
        ];
    }
}