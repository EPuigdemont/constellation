<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class ConvertGuestUserRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->isGuest() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            ...$this->profileRules($userId),
            'password' => $this->passwordRules(),
            'password_confirmation' => 'required|same:password',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.unique' => __('This username is already taken.'),
            'email.unique' => __('This email address is already registered.'),
        ];
    }
}
