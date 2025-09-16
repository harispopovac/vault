<?php

namespace Colors\ColorsModule\Transformers;

use Colors\ColorsModule\Models\Color;
use League\Fractal\TransformerAbstract;

class ColorTransformer extends TransformerAbstract
{
    public function transform(Color $color): array
    {
        return [
            'id' => $color->id,
            'uuid' => $color->uuid,
            'name' => $color->name,
            'dark' => [
                'text' => trim($color->dark_text),
                'background' => trim($color->dark_bg),
                'border' => trim($color->dark_border),
            ],
            'light' => [
                'text' => trim($color->light_text),
                'background' => trim($color->light_bg),
                'border' => trim($color->light_border),
            ],
        ];
    }
}
