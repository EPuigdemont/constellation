<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class SetGuestLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|In>>
     */
    public function rules(): array
    {
        /** @var array<int, string> $supportedLocales */
        $supportedLocales = config('app.supported_locales', ['en']);

        return [
            'locale' => ['required', 'string', Rule::in($supportedLocales)],
        ];
    }
}
