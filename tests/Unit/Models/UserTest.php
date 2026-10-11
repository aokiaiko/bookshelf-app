<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $user = new User;

        $user->fill([
            'name' => 'テスト名',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertEquals('テスト名', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_non_fillable_attributes_cannot_be_mass_assigned(): void
    {
        $user = new User;

        $user->fill([
            'name' => 'テスト名',
            'email' => 'test@example.com',
            'admin_flag' => true,
        ]);

        $this->assertEquals('テスト名', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertNull($user->getAttribute('admin_flag'));
    }

    public function test_hidden_attributes_are_not_included_in_array(): void
    {
        $user = new User;

        $user->password = 'password123';
        $user->remember_token = 'test_token';

        $userArray = $user->toArray();

        $this->assertArrayHasKey('password', $user->getAttributes());
        $this->assertArrayHasKey('remember_token', $user->getAttributes());

        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }

    public function test_password_is_hashed(): void
    {
        $user = new User;

        $user->password = 'password123';

        $this->assertNotEquals('password123', $user->password);

        $this->assertTrue(
            Hash::check('password123', $user->password)
        );
    }

    public function test_user_has_many_books(): void
    {
        $user = new User;

        $relation = $user->books();

        $this->assertInstanceOf(
            HasMany::class,
            $relation
        );

        $this->assertInstanceOf(
            Book::class,
            $relation->getRelated()
        );
    }

    public function test_user_belongs_to_many_favorite_books(): void
    {
        $user = new User;

        $relation = $user->favoriteBooks();

        $this->assertInstanceOf(
            BelongsToMany::class,
            $relation
        );

        $this->assertInstanceOf(
            Book::class,
            $relation->getRelated()
        );

        $this->assertEquals(
            'favorites',
            $relation->getTable()
        );
    }

    public function test_user_has_many_reviews(): void
    {
        $user = new User;

        $relation = $user->reviews();

        $this->assertInstanceOf(
            HasMany::class,
            $relation
        );

        $this->assertInstanceOf(
            Review::class,
            $relation->getRelated()
        );
    }

    public function test_user_belongs_to_many_liked_reviews(): void
    {
        $user = new User;

        $relation = $user->likedReviews();

        $this->assertInstanceOf(
            BelongsToMany::class,
            $relation
        );

        $this->assertInstanceOf(
            Review::class,
            $relation->getRelated()
        );

        $this->assertEquals(
            'review_likes',
            $relation->getTable()
        );
    }
}
