<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery as m;
use PHPUnit\Framework\Assert as PHPUnit;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        TestResponse::macro('assertText', function ($text) {
            PHPUnit::assertTrue(Str::contains($this->getContent(), $text), "Expected text [{$text}] not found.");

            return $this;
        });

        TestResponse::macro('assertTextMissing', function ($text) {
            PHPUnit::assertFalse(Str::contains($this->getContent(), $text), "Expected missing text [{$text}] found.");

            return $this;
        });
    }
    /** @test */
    public function can_not_create_user_if_email_is_taken()
    {
        factory(User::class)->create(['email' => 'test@example.com']);

        $this->mockSocialite('github', ['email' => 'test@example.com']);

        $this->get('/api/oauth/github/callback')
           // ->assertText('Email already used in local adapt authentication.  If you are trying to create an account  please either directly log in to Adapt with that email account or use a different email through the single sign on.  Please first visit: &lt;a href=&quot;https://sso.libretexts.org/cas/logout&quot;&gt;https://sso.libretexts.org/cas/logout&lt;/a&gt; in your browser to reset the authentication process.')
            ->assertTextMissing('token')
            ->assertStatus(400);
    }

    /** @test */
    public function redirect_to_provider()
    {
        $this->mockSocialite('github');

        $this->postJson('/api/oauth/github')
            ->assertSuccessful()
            ->assertJson(['url' => 'https://url-to-provider']);
    }

    /** @test */
    /*   public function create_user_and_return_token()
    {
     $this->mockSocialite('github', [
            'id' => '123',
            'name' => 'Test User',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'token' => 'access-token',
            'refreshToken' => 'refresh-token',
        ]);

        $this->withoutExceptionHandling();

        $this->get('/api/oauth/github/callback')
            ->assertText('token')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => User::first()->id,
            'provider' => 'github',
            'provider_user_id' => '123',
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
        ]);
    }*/

    /** @test */
    public function update_user_and_return_token()
    {
        $user = factory(User::class)->create(['first_name' => 'Test', 'last_name'=> 'User', 'email' => 'test@example.com']);
        $user->oauthProviders()->create([
            'provider' => 'github',
            'provider_user_id' => '123',
        ]);

        $this->mockSocialite('github', [
            'id' => '123',
            'email' => 'test@example.com',
            'token' => 'updated-access-token',
            'refreshToken' => 'updated-refresh-token',
        ]);

        $this->get('/api/oauth/github/callback')
            ->assertText('token')
            ->assertSuccessful();

        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $user->id,
            'access_token' => 'updated-access-token',
            'refresh_token' => 'updated-refresh-token',
        ]);
    }



    protected function mockSocialite($provider, $user = null)
    {
        $mock = Socialite::shouldReceive('stateless')
            ->andReturn(m::self())
            ->shouldReceive('driver')
            ->with($provider)
            ->andReturn(m::self());

        if ($user) {
            $mock->shouldReceive('user')
                ->andReturn((new SocialiteUser)->setRaw($user)->map($user));
        } else {
            $mock->shouldReceive('redirect')
                ->andReturn(redirect('https://url-to-provider'));
        }
    }
}
