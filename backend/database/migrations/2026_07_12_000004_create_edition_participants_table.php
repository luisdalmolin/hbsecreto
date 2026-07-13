<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edition_participants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('edition_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('group_member_id');
            $table->timestamps();

            $table->foreign(['edition_id', 'group_id'])
                ->references(['id', 'group_id'])
                ->on('editions')
                ->cascadeOnDelete();
            $table->foreign(['group_member_id', 'group_id'])
                ->references(['id', 'group_id'])
                ->on('group_members')
                ->restrictOnDelete();
            $table->unique(['edition_id', 'group_member_id']);
            $table->unique(['id', 'edition_id']);
            $table->index(['group_id', 'group_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edition_participants');
    }
};
