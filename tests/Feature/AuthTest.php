<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 会員登録成功後はログイン画面へ遷移し、未ログイン状態になる
     *
     * Bookshelf では RegisterResponse により登録後に自動ログインせず
     * ログアウト状態のまま /login へリダイレクトする独自仕様。
     */
    public function test_user_is_redirected_to_login_and_remains_guest_after_registration(): void
    {
        $response = $this->post('/register', [
            'name' => '登録テストユーザー',
            'email' => 'register@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'register@example.com',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /**
     * 会員登録で入力不備の場合は登録できない
     *
     * CreateNewUser のバリデーションルールに基づき、
     * 必須項目が空の場合はエラーになりDBへ登録されない。
     */
    public function test_registration_fails_when_required_fields_are_invalid(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
        ]);

        $this->assertDatabaseCount('users', 0);

        $this->assertGuest();
    }

    /**
     * 正しい認証情報でログインできる
     *
     * ログイン成功後は RouteServiceProvider::HOME (/books) へリダイレクトされ
     * 認証済み状態になる。
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
        ]);

        $response = $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(RouteServiceProvider::HOME);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * 誤った認証情報ではログインできない
     *
     * 存在するユーザーに対して誤ったパスワードを送信した場合、
     * バリデーションエラーが返り未認証状態のままになる。
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
        ]);

        $response = $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * ログアウトできる
     *
     * Fortify が登録する POST /logout により認証状態が解除される。
     * ログアウト後は /login へリダイレクトされる。
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');

        $this->assertGuest();
    }

    /**
     * ログイン済みユーザーは /login と /register へアクセスできない
     *
     * RedirectIfAuthenticated ミドルウェアにより
     * RouteServiceProvider::HOME (/books) へリダイレクトされる。
     */
    public function test_authenticated_user_is_redirected_from_guest_only_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect(RouteServiceProvider::HOME);

        $this->actingAs($user)
            ->get('/register')
            ->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_registration_fails_when_email_and_password_formats_are_invalid(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'not-an-email',
            'password' => '1234567',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors([
            'email',
            'password',
        ]);

        $this->assertDatabaseCount('users', 0);

        $this->assertGuest();
    }

    public function test_registration_fails_when_email_is_already_registered(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->post('/register', [
            'name' => '新規ユーザー',
            'email' => 'existing@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);

        $this->assertDatabaseCount('users', 1);

        $this->assertGuest();
    }
}
