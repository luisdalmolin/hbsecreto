<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('provider');
            $table->string('external_id');
            $table->string('title');
            $table->text('url');
            $table->text('affiliate_url')->nullable();
            $table->unsignedInteger('price_cents')->nullable();
            $table->string('currency', 3);
            $table->text('image_url')->nullable();
            $table->json('raw');
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['provider', 'external_id']);
            $table->index(['provider', 'fetched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
