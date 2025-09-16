<?php

namespace Colors\ColorsModule\Repositories;

use Colors\ColorsModule\Models\Color;
use Colors\ColorsModule\Transformers\ColorTransformer;
use Illuminate\Support\Str;

class ColorsRepository
{
    protected Color $color;

    public function __construct(Color $color)
    {
        $this->color = $color;
    }

    public function index(): array
    {
        $colors = Color::query()->get();

        return fractal($colors, new ColorTransformer())->toArray();
    }

    public function show(Color $color): array
    {
        return fractal($color, new ColorTransformer())->toArray();
    }

    public function store(array $data): array
    {
        $data['uuid'] = Str::uuid();
        $color = Color::create($data);

        return fractal($color, new ColorTransformer())->toArray();
    }

    public function update(Color $color, array $data): array
    {
        $color->update($data);

        return fractal($color, new ColorTransformer())->toArray();
    }

    public function destroy(Color $color): void
    {
        $color->delete();
    }

    public function getByUuid(string $uuid): ?array
    {
        // Validate UUID format first
        if (empty($uuid) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
            return null;
        }

        $color = Color::where('uuid', $uuid)->first();

        if (!$color) {
            return null;
        }

        return fractal($color, new ColorTransformer())->toArray();
    }

    public function updateByUuid(string $uuid, array $data): ?array
    {
        // Validate UUID format first
        if (empty($uuid) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
            return null;
        }

        $color = Color::where('uuid', $uuid)->first();

        if (!$color) {
            return null;
        }

        $color->update($data);

        return fractal($color, new ColorTransformer())->toArray();
    }
}
