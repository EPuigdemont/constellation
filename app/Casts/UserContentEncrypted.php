<?php

declare(strict_types=1);

namespace App\Casts;

use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/** @implements CastsAttributes<string|null, string|null> */
final class UserContentEncrypted implements CastsAttributes
{
    /** @param array<string, mixed> $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return $this->resolveUser($model)->encrypter()->decryptString((string) $value);
        } catch (DecryptException) {
            return (string) $value;
        }
    }

    /** @param array<string, mixed> $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->resolveUser($model)->encrypter()->encryptString((string) $value);
    }

    private function resolveUser(Model $model): User
    {
        $user = $model->getAttribute('user');

        if (! $user instanceof User) {
            throw new RuntimeException(
                'Cannot resolve owning user for '.$model::class.' to (de)crypt content.'
            );
        }

        return $user;
    }
}
