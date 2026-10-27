<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_displays_form(): void
    {
        $response = $this->get('register');

        $response->assertStatus(200);

        $response->assertSee('お名前');
        $response->assertSee('メールアドレス');
        $response->assertSee('パスワード');
        $response->assertSee('パスワード確認');

        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);

        $response->assertSee('ログイン');
        $response->assertSee('登録');
    }

    public function test_register_requires_name(): void
    {
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_name_must_be_string(): void
    {
        $response = $this->post(route('register'), [
            'name' => ['テスト'],
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'name' => '名前は文字で入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_name_must_not_exceed_255_characters(): void
    {
        $response = $this->post(route('register'), [
            'name' => str_repeat('あ', 256),
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'name' => '名前は255字以下で入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_requires_email(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_email_must_not_exceed_255_characters(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => str_repeat('a', 244).'@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスは255字以下で入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_email_must_be_valid_format(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスはメール形式で入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_email_must_be_unique(): void
    {
        $userA = User::factory()->create();

        $response = $this->post(route('register'), [
            'name' => 'テストユーザーB',
            'email' => $userA->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseCount('users', 1);

        $response->assertSessionHasErrors([
            'email' => 'そのメールアドレスは既に使用されています',
        ]);
    }

    public function test_register_password_must_be_at_least_8_characters(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_password_must_be_string(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => ['password123'],
            'password_confirmation' => ['password123'],
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは文字列で入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_password_confirmation_must_match(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_password_is_required(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_password_confirmation_is_required(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'password_confirmation' => '確認用パスワードを入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_saves_user_to_database(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_redirects_to_books_index(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('books.index'));
    }

    public function test_register_page_has_login_link(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('ログイン');
        $response->assertSee('href="'.route('login').'"', false);
    }

    public function test_authenticated_user_cannot_access_register_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('register'));

        $response->assertRedirect(route('books.index'));
    }

    public function test_register_input_values_are_preserved_after_validation_error(): void
    {
        $response = $this->from(route('register'))->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('password');

        $response->assertSessionHasInput('name', 'テストユーザー');
        $response->assertSessionHasInput('email', 'test@example.com');

        $response->assertSessionMissing('_old_input.password');
        $response->assertSessionMissing('_old_input.password_confirmation');
    }

    public function test_login_page_displays_form(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('ログイン');
    }

    public function test_login_email_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);

        $this->assertGuest();
    }

    public function test_login_email_must_be_valid_format(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => 'invalid-email',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスはメール形式で入力してください',
        ]);

        $this->assertGuest();
    }

    public function test_login_password_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);

        $this->assertGuest();
    }

    public function test_login_fails_with_unregistered_email(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);

        $this->assertGuest();
    }

    public function test_login_authenticates_user(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_redirects_to_books_index(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('books.index'));
    }

    public function test_login_page_has_register_link(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('会員登録');
        $response->assertSee('href="'.route('register').'"', false);
    }

    public function test_authenticated_user_cannot_access_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('books.index'));
    }

    public function test_logout_redirects_to_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_login_input_values_are_preserved_after_authentication_error(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors();

        $response->assertSessionHasInput('email', $user->email);
        $response->assertSessionMissing('_old_input.password');

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('value="'.$user->email.'"', false);
    }
}
