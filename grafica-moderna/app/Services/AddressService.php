<?php

namespace App\Services;

use App\Models\UserAddress;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddressService
{
    public function getUserAddresses(string $userId)
    {
        return UserAddress::where('user_id', $userId)->get();
    }

    public function getById(string $id, string $userId)
    {
        return UserAddress::where('id', $id)->where('user_id', $userId)->firstOrFail();
    }

    public function create(string $userId, array $data)
    {
        // Se for o primeiro endereço ou marcado como padrão, remove o padrão dos outros
        $isFirst = !UserAddress::where('user_id', $userId)->exists();
        $isDefault = $data['isDefault'] ?? false;

        if ($isDefault || $isFirst) {
            $this->unsetDefaultAddress($userId);
        }

        return UserAddress::create([
            'user_id' => $userId,
            'name' => $data['name'],
            'receiver_name' => $data['receiverName'],
            'zip_code' => $data['zipCode'],
            'street' => $data['street'],
            'number' => $data['number'],
            'complement' => $data['complement'] ?? '',
            'neighborhood' => $data['neighborhood'],
            'city' => $data['city'],
            'state' => $data['state'],
            'reference' => $data['reference'] ?? '',
            'phone_number' => $data['phoneNumber'],
            'is_default' => $isDefault || $isFirst
        ]);
    }

    public function update(string $id, string $userId, array $data)
    {
        $address = $this->getById($id, $userId);
        
        if ($data['isDefault'] ?? false) {
            $this->unsetDefaultAddress($userId);
        }

        // Mapeamento de camelCase (JSON) para snake_case (DB)
        $address->update([
            'name' => $data['name'],
            'receiver_name' => $data['receiverName'],
            'zip_code' => $data['zipCode'],
            'street' => $data['street'],
            'number' => $data['number'],
            'complement' => $data['complement'] ?? '',
            'neighborhood' => $data['neighborhood'],
            'city' => $data['city'],
            'state' => $data['state'],
            'reference' => $data['reference'] ?? '',
            'phone_number' => $data['phoneNumber'],
            'is_default' => $data['isDefault']
        ]);

        return $address;
    }

    public function delete(string $id, string $userId)
    {
        $address = UserAddress::where('id', $id)->where('user_id', $userId)->first();
        if ($address) {
            $address->delete();
        }
    }

    private function unsetDefaultAddress(string $userId)
    {
        UserAddress::where('user_id', $userId)->update(['is_default' => false]);
    }
}