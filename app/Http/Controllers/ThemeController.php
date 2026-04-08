<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', 'in:'.implode(',', array_column(Theme::cases(), 'value'))],
        ]);

        $request->user()->update([
            'theme' => $validated['theme'],
            'automatic_themes' => false,
        ]);

        return response()->json(['ok' => true]);
    }
}
