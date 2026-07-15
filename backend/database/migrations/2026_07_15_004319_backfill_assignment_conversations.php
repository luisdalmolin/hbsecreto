<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $timestamp = now();

        DB::table('assignments')
            ->select(['id', 'edition_id'])
            ->orderBy('id')
            ->chunkById(200, function ($assignments) use ($timestamp): void {
                DB::table('conversations')->insertOrIgnore($assignments->map(fn ($assignment): array => [
                    'edition_id' => $assignment->edition_id,
                    'type' => 'assignment',
                    'assignment_id' => $assignment->id,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])->all());
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Conversation data may already contain messages and is intentionally preserved.
    }
};
