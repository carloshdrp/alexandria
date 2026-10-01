<?php

namespace App\Acesso\Application\Providers;

use App\Acesso\Application\Actions\CreateNewUser;
use App\Acesso\Application\Actions\ResetUserPassword;
use App\Acesso\Domain\Enums\UsuarioSituacao;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;

class AcessoServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../Interface/Views', 'acesso');

        Livewire::addNamespace(
            namespace: 'acesso',
            classNamespace: 'App\\Acesso\\Application\\Livewire',
            classPath: __DIR__.'/../Livewire',
            classViewPath: __DIR__.'/../../Interface/Views/livewire',
        );

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => view('acesso::auth.login'));
        Fortify::registerView(fn () => view('acesso::auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('acesso::auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('acesso::auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('acesso::auth.verify-email'));

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', $request->string('email')->lower()->toString())->first();

            if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
                return null;
            }

            return $user->situacao === UsuarioSituacao::Bloqueado ? null : $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
