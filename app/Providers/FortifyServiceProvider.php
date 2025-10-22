<?php

namespace OGame\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use OGame\Actions\Fortify\CreateNewUser;
use OGame\Actions\Fortify\ResetUserPassword;
use OGame\Actions\Fortify\UpdateUserPassword;
use OGame\Actions\Fortify\UpdateUserProfileInformation;

class FortifyServiceProvider extends ServiceProvider
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
        // Ensure Fortify uses 'username' instead of the default 'email'
        Fortify::username('username');

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());
            return Limit::perMinute(20)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(20)->by($request->session()->get('login.id'));
        });

        // Views
        Fortify::loginView(function () {
            return view('outgame.login');
        });

        // Re-enable the Register / Forgot Password / Reset Password views
        Fortify::registerView(function () {
            return view('auth.register'); // make sure resources/views/auth/register.blade.php exists
        });

        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.forgot-password'); // optional: create this blade if you use password resets
        });

        Fortify::resetPasswordView(function () {
            return view('auth.reset-password'); // optional: create this blade if you use password resets
        });
    }
}
