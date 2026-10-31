<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_genre_index_displays_genres_with_books_count(): void
    {
        $user = User::factory()->create();

        $genreA = Genre::factory()->create([
            'name' => '小説',
        ]);
        $genreB = Genre::factory()->create([
            'name' => '漫画',
        ]);

        $books = Book::factory()->count(3)->create();

        $genreA->books()->attach($books[0]->id);
        $genreA->books()->attach($books[1]->id);
        $genreB->books()->attach($books[2]->id);

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertStatus(200);

        $response->assertSee('小説');
        $response->assertSee('漫画');
        $response->assertSee('2冊');
        $response->assertSee('1冊');
        $response->assertSee('登録');
        $response->assertSee(route('genres.create'));
        $response->assertSee('編集');
        $response->assertSee(route('genres.edit', $genreA));
        $response->assertSee('削除');
        $response->assertSee(route('genres.destroy', $genreA));
    }

    public function test_genre_show_displays_books_with_pagination(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(11)->create();

        foreach ($books as $book) {
            $genre->books()->attach($book);
        }

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertStatus(200);
        $response->assertViewHas('books', function ($books) {
            return $books->count() === 10
            && $books->perPage() === 10;
        });
        $response->assertSee($genre->name);

        $pageBooks = $response->viewData('books');

        foreach ($pageBooks as $book) {
            $response->assertSee($book->title);
        }

        $response = $this->get(route('genres.show', [
            'genre' => $genre,
            'page' => 2,
        ]));

        $response->assertViewHas('books', function ($books) {
            return $books->count() === 1;
        });

        $pageBooks = $response->viewData('books');

        foreach ($pageBooks as $book) {
            $response->assertSee($book->title);
        }
    }

    public function test_guest_cannot_store_genre(): void
    {
        $response = $this->post(route('genres.store'), [
            'name' => '小説',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('genres', [
            'name' => '小説',
        ]);
    }

    public function test_genre_create_page_displays_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.create'));

        $response->assertStatus(200);

        $response->assertSee('name="name"', false);
        $response->assertSee('キャンセル');
        $response->assertSee('登録');
    }

    public function test_genre_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は必須です。',
        ]);

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_genre_name_must_be_string(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => [1, 2],
        ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は文字列で入力してください。',
        ]);

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_genre_name_must_not_exceed_255_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => str_repeat('あ', 256),
        ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は255文字以内で入力してください。',
        ]);

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_genre_name_must_be_unique(): void
    {
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => $genre->name,
        ]);

        $response->assertSessionHasErrors([
            'name' => 'そのジャンル名は既に使用されています。',
        ]);

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_genre_can_be_created_and_redirects_to_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => '小説',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => '小説',
        ]);
    }

    public function test_genre_creation_displays_success_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => '小説',
        ]);

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas('success', 'ジャンルを作成しました。');

        $this->get(route('genres.index'))
            ->assertSee('ジャンルを作成しました。');
    }

    public function test_created_genre_is_displayed_on_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => '小説',
        ]);

        $response->assertRedirect(route('genres.index'));

        $this->get(route('genres.index'))
            ->assertSee('小説');
    }

    public function test_guest_cannot_update_genre(): void
    {
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->put(route('genres.update', $genre), [
            'name' => '漫画',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_edit_page_displays_form_with_existing_values(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->get(route('genres.edit', $genre));

        $response->assertStatus(200);

        $response->assertSee('name="name"', false);
        $response->assertSee('value="小説"', false);
        $response->assertSee('キャンセル');
        $response->assertSee('更新');
    }

    public function test_genre_name_is_required_on_update(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は必須です。',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_name_must_be_string_on_update(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), [
            'name' => [1, 2],
        ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は文字列で入力してください。',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_name_must_not_exceed_255_characters_on_update(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), [
            'name' => str_repeat('あ', 256),
        ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は255文字以内で入力してください。',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_name_can_remain_unchanged_on_update(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), [
            'name' => '小説',
        ]);

        $response->assertSessionHasNoErrors();

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_name_must_be_unique_on_update(): void
    {
        $user = User::factory()->create();

        $genreA = Genre::factory()->create([
            'name' => '小説',
        ]);

        $genreB = Genre::factory()->create([
            'name' => '漫画',
        ]);

        $response = $this->actingAs($user)->put(route('genres.update', $genreA), [
            'name' => $genreB->name,
        ]);

        $response->assertSessionHasErrors([
            'name' => 'そのジャンル名は既に使用されています。',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genreA->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_can_be_updated(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), [
            'name' => '漫画',
        ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '漫画',
        ]);
    }

    public function test_updated_genre_and_success_message_are_displayed(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), [
            'name' => '漫画',
        ]);

        $response->assertSessionHas('success', 'ジャンルを更新しました。');

        $this->get(route('genres.index'))
            ->assertSee('漫画')
            ->assertSee('ジャンルを更新しました。');
    }

    public function test_genre_update_returns_404_when_genre_does_not_exist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->put(route('genres.update', 99999));

        $response->assertNotFound();
    }

    public function test_guest_cannot_delete_genre(): void
    {
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);
        $book = Book::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $response->assertSessionHas('error', 'このジャンルには書籍が紐付いているため削除できません。');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);

        $this->get(route('genres.index'))
            ->assertSee('このジャンルには書籍が紐付いているため削除できません。');
    }

    public function test_genre_can_be_deleted_and_displays_success_message(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);

        $response->assertSessionHas('success', 'ジャンルを削除しました。');

        $this->get(route('genres.index'))
            ->assertSee('ジャンルを削除しました。');
    }
}
