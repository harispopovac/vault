<?php

namespace Address\AddressModule\Http\Controllers;

use App\Http\Controllers\Controller;
use Address\AddressModule\Http\Requests\StoreAddressRequest;
use Address\AddressModule\Http\Requests\UpdateAddressRequest;
use Address\AddressModule\Models\Address;
use Address\AddressModule\Repositories\AddressesRepository;
use Illuminate\Http\JsonResponse;

class AddressesController extends Controller
{
    public function __construct(
        protected readonly AddressesRepository $addressesRepository
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            $this->addressesRepository->index()
        );
    }

    public function show(Address $address): JsonResponse
    {
        return response()->json(
            $this->addressesRepository->show($address)
        );
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        return response()->json(
            $this->addressesRepository->store($request->validated())
        );
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        return response()->json(
            $this->addressesRepository->update($address, $request->validated())
        );
    }

    public function destroy(Address $address): JsonResponse
    {
        try {
            $deletedAddress = $this->addressesRepository->destroy($address);

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully',
                'data' => $deletedAddress,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete address',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
