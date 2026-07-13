<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('classic');
            $table->enum('status', ['draft', 'open', 'drawn', 'revealed', 'archived'])->default('draft');
            $table->unsignedInteger('budget_cents')->nullable();
            $table->string('currency', 3)->default('BRL');
            $table->date('event_date')->nullable();
            $table->json('settings')->default('{}');
            $table->timestamp('drawn_at')->nullable();
            $table->timestamp('revealed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['id', 'group_id']);
            $table->index(['group_id', 'status']);
            $table->index(['group_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editions');
    }
};
