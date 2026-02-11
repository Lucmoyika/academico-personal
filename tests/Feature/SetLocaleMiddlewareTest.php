<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class SetLocaleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function dispatchMiddleware(Request $request): void
    {
        $middleware = new SetLocale;
        $middleware->handle($request, function () {
            return response('ok');
        });
    }

    public function test_authenticated_user_locale_is_applied(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->actingAs($user);

        $request = Request::create('/test');
        $request->setLaravelSession(app('session.store'));

        $this->dispatchMiddleware($request);

        $this->assertEquals('fr', App::getLocale());
    }

    public function test_session_locale_used_for_guest(): void
    {
        $session = app('session.store');
        $session->put('locale', 'es');

        $request = Request::create('/test');
        $request->setLaravelSession($session);

        $this->dispatchMiddleware($request);

        $this->assertEquals('es', App::getLocale());
    }

    public function test_defaults_to_config_locale(): void
    {
        config()->set('app.locale', 'en');

        $request = Request::create('/test');
        $request->setLaravelSession(app('session.store'));

        $this->dispatchMiddleware($request);

        $this->assertEquals('en', App::getLocale());
    }

    public function test_user_locale_overrides_session(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->actingAs($user);

        $session = app('session.store');
        $session->put('locale', 'es');

        $request = Request::create('/test');
        $request->setLaravelSession($session);

        $this->dispatchMiddleware($request);

        $this->assertEquals('fr', App::getLocale());
    }

    public function test_carbon_locale_is_set(): void
    {
        $user = User::factory()->create(['locale' => 'es']);

        $this->actingAs($user);

        $request = Request::create('/test');
        $request->setLaravelSession(app('session.store'));

        $this->dispatchMiddleware($request);

        $this->assertEquals('es', Carbon::getLocale());
    }
}
