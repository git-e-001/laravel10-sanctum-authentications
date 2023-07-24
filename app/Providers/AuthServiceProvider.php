<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
        // change verify email url
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $url = config('app.frontend_url') . '/auth/verify-email?queryURL=';
            $verification = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(config('auth.passwords.users.expire', 60)),
                [
                    'user' => $notifiable->getKey(),
                ]
            );

            return $url . urlencode($verification);
        });

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject(trans('mail.verify_email_subject'))
                ->line(trans('mail.verify_email_line1'))
                ->action(trans('mail.verify_email_action'), $url)
                ->line(trans('mail.verify_email_line2'));
        });

        // // change reset password url
        // ResetPassword::createUrlUsing(function ($user, string $token) {
        //     $url = config('app.url') . '/auth/reset-password/';

        //     return $url . $token . '?email=' . $user->email;
        // });

        ResetPassword::toMailUsing(function ($user, string $token) {
            $url = config('app.frontend_url') . '/auth/reset-password/';

            return (new MailMessage)
                ->subject(trans('mail.reset_password_subject'))
                ->line(trans('mail.reset_password_line1'))
                ->action(trans('mail.reset_password_action'), $url . $token . '?email=' . $user->email)
                ->line(trans('mail.reset_password_line2') . trans(':count minutes.', ['count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire')]))
                ->line(trans('mail.reset_password_line3'));
        });
    }
}
