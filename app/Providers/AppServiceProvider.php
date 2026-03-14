<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\DiaryEntry;
use App\Models\EntityRelationship;
use App\Models\Image;
use App\Models\ImportantDate;
use App\Models\Note;
use App\Models\Postit;
use App\Models\Reminder;
use App\Models\Tag;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Relation::enforceMorphMap([
            'diary_entry' => DiaryEntry::class,
            'note' => Note::class,
            'postit' => Postit::class,
            'image' => Image::class,
            'tag' => Tag::class,
            'important_date' => ImportantDate::class,
            'reminder' => Reminder::class,
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
