<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id('post_id');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('title', 255);
            $table->string('slug', 280);
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('thumbnail', 255)->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title', 160)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->string('meta_keywords', 255)->nullable();
            $table->timestamps();

            $table->unique('slug', 'idx_unique_post_slug');
            $table->index('status', 'idx_post_status');
            $table->index('published_at', 'idx_post_published_at');
            $table->index('author_id', 'idx_post_author_id');

            $table->foreign('author_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
