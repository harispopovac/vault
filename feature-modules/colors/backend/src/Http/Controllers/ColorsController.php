<?php

namespace Colors\ColorsModule\Http\Controllers;

use App\Http\Controllers\Controller;
use Colors\ColorsModule\Http\Requests\StoreColorRequest;
use Colors\ColorsModule\Http\Requests\UpdateColorRequest;
use Colors\ColorsModule\Models\Color;
use Colors\ColorsModule\Repositories\ColorsRepository;
use Illuminate\Http\JsonResponse;

class ColorsController extends Controller
{
    public function __construct(
        protected readonly ColorsRepository $colorsRepository
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            $this->colorsRepository->index()
        );
    }

    public function show(Color $color): JsonResponse
    {
        return response()->json(
            $this->colorsRepository->show($color)
        );
    }

    public function store(StoreColorRequest $request): JsonResponse
    {
        return response()->json(
            $this->colorsRepository->store($request->validated())
        );
    }

    public function update(UpdateColorRequest $request, Color $color): JsonResponse
    {
        return response()->json(
            $this->colorsRepository->update($color, $request->validated())
        );
    }

    public function destroy(Color $color): JsonResponse
    {
        $this->colorsRepository->destroy($color);

        return response()->json([
            'message' => 'Color deleted successfully'
        ]);
    }

    public function getByUuid(string $uuid): JsonResponse
    {
        $result = $this->colorsRepository->getByUuid($uuid);
        
        if ($result === null) {
            return response()->json([
                'message' => 'Color not found'
            ], 404);
        }

        return response()->json($result);
    }

    public function updateByUuid(UpdateColorRequest $request, string $uuid): JsonResponse
    {
        $result = $this->colorsRepository->updateByUuid($uuid, $request->validated());
        
        if ($result === null) {
            return response()->json([
                'message' => 'Color not found'
            ], 404);
        }

        return response()->json($result);
    }
}
