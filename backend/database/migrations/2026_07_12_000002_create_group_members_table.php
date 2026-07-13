<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('display_name')->nullable();
            $table->string('email')->nullable();
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->string('invite_token', 64)->nullable()->unique();
            $table->timestamp('invite_expires_at')->nullable();
            $table->enum('status', ['invited', 'active', 'inactive'])->default('invited');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
            $table->unique(['id', 'group_id']);
            $table->index(['group_id', 'status']);
            $table->index(['group_id', 'role', 'status']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};
