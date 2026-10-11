<?php

namespace App\Actions;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class SaveAddress
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Customer $customer, array $data, ?Address $address = null): Address
    {
        return DB::transaction(function () use ($customer, $data, $address): Address {
            $lockedCustomer = Customer::query()
                ->whereKey($customer->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAddress = $address === null
                ? null
                : $lockedCustomer->addresses()
                    ->whereKey($address->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

            $data['is_default'] = (bool) ($data['is_default'] ?? false);

            if ($data['is_default']) {
                $otherAddresses = $lockedCustomer->addresses()->where('is_default', true);

                if ($lockedAddress !== null) {
                    $otherAddresses->where('id', '!=', $lockedAddress->getKey());
                }

                $otherAddresses->update(['is_default' => false]);
            }

            if ($lockedAddress === null) {
                return $lockedCustomer->addresses()->create($data);
            }

            $lockedAddress->update($data);

            return $lockedAddress;
        });
    }
}
