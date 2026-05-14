<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DiaryEntry;
use App\Models\Image;
use App\Models\ImportantDate;
use App\Models\Note;
use App\Models\Postit;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

final class BackfillEncryptionKeys extends Command
{
    protected $signature = 'users:backfill-encryption-keys';

    protected $description = 'Generate per-user DEK and encrypt existing plaintext content rows. Idempotent.';

    public function handle(): int
    {
        $userCount = 0;
        $rowCount = 0;

        User::query()->chunkById(50, function ($users) use (&$userCount, &$rowCount): void {
            foreach ($users as $user) {
                DB::transaction(function () use ($user, &$rowCount): void {
                    if (! is_string($user->encryption_key) || $user->encryption_key === '') {
                        $user->encryption_key = Crypt::encryptString(random_bytes(32));
                        $user->saveQuietly();
                    }

                    $rowCount += $this->encryptColumns($user, DiaryEntry::class, 'diary_entries', ['title', 'body']);
                    $rowCount += $this->encryptColumns($user, Note::class, 'notes', ['title', 'body']);
                    $rowCount += $this->encryptColumns($user, Postit::class, 'postits', ['body']);
                    $rowCount += $this->encryptColumns($user, Reminder::class, 'reminders', ['title', 'body']);
                    $rowCount += $this->encryptColumns($user, Image::class, 'images', ['alt', 'title']);
                    $rowCount += $this->encryptColumns($user, ImportantDate::class, 'important_dates', ['label']);
                });
                $userCount++;
            }
        });

        $this->info("Processed {$userCount} users, re-encrypted {$rowCount} columns.");

        return self::SUCCESS;
    }

    /** @param list<string> $columns */
    private function encryptColumns(User $user, string $modelClass, string $table, array $columns): int
    {
        $encrypter = $user->encrypter();
        $rows = DB::table($table)->where('user_id', $user->id)->get();
        $updated = 0;

        foreach ($rows as $row) {
            $changes = [];
            foreach ($columns as $column) {
                $value = $row->{$column} ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                try {
                    $encrypter->decryptString((string) $value);
                    continue;
                } catch (DecryptException) {
                    $changes[$column] = $encrypter->encryptString((string) $value);
                }
            }
            if ($changes !== []) {
                DB::table($table)->where('id', $row->id)->update($changes);
                $updated++;
            }
        }

        return $updated;
    }
}
