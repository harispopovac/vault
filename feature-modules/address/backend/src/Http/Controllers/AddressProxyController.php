<?php

namespace Address\AddressModule\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class AddressProxyController extends Controller
{
    protected string $backendUrl = 'http://addressr.ganit.com.au:8080';

    public function getAddresses(Request $request)
    {
        $query = $request->query('q');

        $response = Http::get("{$this->backendUrl}/addresses", ['q' => $query]);
        $data = $response->json();

        if (isset($data['failedValidation']) && $data['failedValidation']) {
            return response()->json(['message' => $data['message']], $response->status());
        }

        $transformedData = array_map(function ($item) {
            return [
                'sla' => $item['sla'] ?? null,
                'id' => $item['pid'] ?? null,
            ];
        }, $data ?? []);

        return response()->json($transformedData, $response->status())
            ->header('Content-Type', 'application/json');
    }

    public function getAddress($id)
    {
        $response = Http::get("{$this->backendUrl}/addresses/{$id}");
        $data = $response->json();

        $transformedData = [
            'address_1' => trim((string) Arr::get($data, 'structured.number.number') . ' ' . (string) Arr::get($data, 'structured.street.name') . ' ' . (string) Arr::get($data, 'structured.street.type.code')),
            'address_2' => null,
            'suburbcity' => Arr::get($data, 'structured.locality.name'),
            'stateprov' => Arr::get($data, 'structured.state.abbreviation'),
            'postcode' => Arr::get($data, 'structured.postcode'),
            'lat' => Arr::get($data, 'geocoding.geocodes.0.latitude'),
            'lng' => Arr::get($data, 'geocoding.geocodes.0.longitude'),
        ];

        return response()->json($transformedData);
    }
}


