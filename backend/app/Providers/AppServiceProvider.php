<?php

namespace App\Providers;

use App\Models\User;
use App\Notifications\ExpoPush\ExpoPushTransport;
use App\Notifications\ExpoPush\FakeExpoPushTransport;
use App\Notifications\ExpoPush\HttpExpoPushTransport;
use App\Policies\DatabaseNotificationPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ExpoPushTransport::class, function (): ExpoPushTransport {
            if (app()->environment('testing')) {
                return new FakeExpoPushTransport;
            }

            return new HttpExpoPushTransport(
                http: app(Factory::class),
                baseUrl: Config::string('services.expo_push.base_url'),
                accessToken: Config::string('services.expo_push.access_token'),
                timeout: Config::integer('services.expo_push.timeout'),
                connectTimeout: Config::integer('services.expo_push.connect_timeout'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureMorphMap();
        $this->configureDefaults();
        Gate::policy(DatabaseNotification::class, DatabaseNotificationPolicy::class);

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('register', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('invitations', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('messages', function (Request $request): Limit {
            $user = $request->user();

            return Limit::perMinute(30)->by(
                $user instanceof User ? (string) $user->id : ($request->ip() ?? 'unknown'),
            );
        });
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

    /**
     * Require explicit aliases before polymorphic relations persist model names.
     *
     * @return array<string, class-string<Model>>
     */
    protected function morphMap(): array
    {
        return [
            'user' => User::class,
        ];
    }

    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap($this->morphMap());
    }
}
