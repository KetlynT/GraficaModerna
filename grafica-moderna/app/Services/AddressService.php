<?php

namespace App\Services;

use App\Models\UserAddress;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\InputCleaner;

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
        $cleanData = InputCleaner::clean($data);
        // Se for o primeiro endereço ou marcado como padrão, remove o padrão dos outros
        $isFirst = !UserAddress::where('user_id', $userId)->exists();
        $isDefault = $cleanData['isDefault'] ?? false;

        if ($isDefault || $isFirst) {
            $this->unsetDefaultAddress($userId);
        }

        return UserAddress::create([
            'user_id' => $userId,
            'name' => $cleanData['name'],
            'receiver_name' => $cleanData['receiverName'],
            'zip_code' => $cleanData['zipCode'],
            'street' => $cleanData['street'],
            'number' => $cleanData['number'],
            'complement' => $cleanData['complement'] ?? '',
            'neighborhood' => $cleanData['neighborhood'],
            'city' => $cleanData['city'],
            'state' => $cleanData['state'],
            'reference' => $cleanData['reference'] ?? '',
            'phone_number' => $cleanData['phoneNumber'],
            'is_default' => $isDefault || $isFirst
        ]);
    }

    public function update(string $id, string $userId, array $data)
    {
        $address = $this->getById($id, $userId);
        $cleanData = InputCleaner::clean($data);
        if ($cleanData['isDefault'] ?? false) {
            $this->unsetDefaultAddress($userId);
        }

        // Mapeamento de camelCase (JSON) para snake_case (DB)
        $address->update([
            'name' => $cleanData['name'],
            'receiver_name' => $cleanData['receiverName'],
            'zip_code' => $cleanData['zipCode'],
            'street' => $cleanData['street'],
            'number' => $cleanData['number'],
            'complement' => $cleanData['complement'] ?? '',
            'neighborhood' => $cleanData['neighborhood'],
            'city' => $cleanData['city'],
            'state' => $cleanData['state'],
            'reference' => $cleanData['reference'] ?? '',
            'phone_number' => $cleanData['phoneNumber'],
            'is_default' => $cleanData['isDefault']
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