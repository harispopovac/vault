<?php

namespace App\Http\Controllers;

use App\Transformers\Timezone\TimezonesTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TimezonesController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            fractal(DB::table('timezones')->get(), new TimezonesTransformer())->toArray()['data']
        );
    }
}
