<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Limpeza do CEP dentro do objeto address, se existir
        if ($this->has('address') && isset($this->address['zipCode'])) {
            $address = $this->address;
            $address['zipCode'] = preg_replace('/\D/', '', $address['zipCode']);
            
            $this->merge(['address' => $address]);
        }
    }

    public function rules(): array
    {
        return [
            // --- CheckoutDto Properties ---
            
            // [Required] public AddressDto Address
            'address' => 'required|array',

            // [StringLength(50)] public string? CouponCode
            'couponCode' => 'nullable|string|max:50|exists:coupons,code',

            // [Required, StringLength(50)] public string ShippingMethod
            'shippingMethod' => 'required|string|max:50',


            // --- AddressDto Validation (Nested) ---
            // Replicando AddressDTOs.cs para garantir a integridade do objeto aninhado
            
            'address.name'          => 'required|string|max:50',      // Nome do endereço (Casa, etc)
            'address.receiverName'  => 'required|string|max:100',
            'address.zipCode'       => 'required|string|size:8',      // Após limpeza
            'address.street'        => 'required|string|max:200',
            'address.number'        => 'required|string|max:20',
            'address.neighborhood'  => 'required|string|max:100',
            'address.city'          => 'required|string|max:100',
            'address.state'         => 'required|string|size:2',      // UF
            'address.phoneNumber'   => 'required|string|max:20',
            'address.complement'    => 'nullable|string|max:100',
            'address.reference'     => 'nullable|string|max:200',
        ];
    }
}