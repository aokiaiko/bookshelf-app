<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('book_genre', function (Blueprint $table) {
            $table->dropForeign('book_genres_book_id_foreign');
            $table->dropForeign('book_genres_genre_id_foreign');

            $table->foreign('book_id')
                ->references('id')
                ->on('books')
                ->cascadeOnDelete();

            $table->foreign('genre_id')
                ->references('id')
                ->on('genres')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_genre', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->dropForeign(['genre_id']);

            $table->foreign('book_id', 'book_genres_book_id_foreign')
                ->references('id')
                ->on('books')
                ->cascadeOnDelete();

            $table->foreign('genre_id', 'book_genres_genre_id_foreign')
                ->references('id')
                ->on('genres')
                ->restrictOnDelete();
        });
    }
};
