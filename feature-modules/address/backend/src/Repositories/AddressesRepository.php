<?php

namespace Address\AddressModule\Repositories;

use Address\AddressModule\Models\Address;
use Address\AddressModule\Transformers\AddressTransformer;
use Exception;
use Illuminate\Support\Facades\DB;

class AddressesRepository
{
    public function index(): array
    {
        $items = Address::query()
            ->orderByDesc('id')
            ->get();

        return fractal($items, new AddressTransformer())->toArray()['data'] ?? [];
    }

    public function show(Address $address): array
    {
        return $address->toArray();
    }

    public function store(array $data): array
    {
        DB::beginTransaction();
        try {
            $address = Address::create($data);
            DB::commit();
            return fractal($address, new AddressTransformer())->toArray()['data'];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Address $address, array $data): array
    {
        DB::beginTransaction();
        try {
            // Ensure we do not null out fields accidentally on update
            $address->fill($data);
            $address->save();
            DB::commit();
            return fractal($address->fresh(), new AddressTransformer())->toArray()['data'];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(Address $address)
    {
        DB::beginTransaction();

        try {
            $address->delete();

            DB::commit();

            return fractal($address, new AddressTransformer())->toArray()['data'];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
