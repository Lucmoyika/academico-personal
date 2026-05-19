<?php

namespace App\Filament\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (method_exists($user, 'isStudent') && $user->isStudent()) {
            session()->regenerate();

            return new class implements LoginResponse
            {
                public function toResponse($request)
                {
                    return redirect()->route('student.dashboard');
                }
            };
        }

        if (($user instanceof FilamentUser) && (! $user->canAccessPanel(Filament::getCurrentPanel()))) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function getSubheading(): string|Htmlable|null
    {
        $before = __('filament-panels::auth/pages/login.actions.register.before');
        $label = __('filament-panels::auth/pages/login.actions.register.label');

        // If translations are missing, they will return the key string.
        // Provide human-friendly fallbacks to avoid showing raw keys in the UI.
        if ($before === 'filament-panels::auth/pages/login.actions.register.before') {
            $before = __('Don\'t have an account?');
        }

        if ($label === 'filament-panels::auth/pages/login.actions.register.label') {
            $label = __('Register');
        }

        return new HtmlString($before.' '.$this->registerAction()->label($label)->toHtml());
    }

    public function registerAction(): Action
    {
        return Action::make('register')
            ->link()
            ->label(__('filament-panels::auth/pages/login.actions.register.label'))
            ->url(route('register'));
    }

    public function getView(): string
    {
        if (View::exists('filament.auth.login')) {
            return 'filament.auth.login';
        }

        return parent::getView();
    }

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        if (View::exists('filament.auth.login')) {
            return MaxWidth::FiveExtraLarge;
        }

        return parent::getMaxContentWidth();
    }
}
