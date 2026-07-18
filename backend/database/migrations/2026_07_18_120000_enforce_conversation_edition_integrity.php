<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->unique(['id', 'edition_id'], 'conversations_id_edition_unique');
        });

        $timestamp = now();

        DB::table('editions')
            ->select('id')
            ->whereNotExists(fn (Builder $conversations): Builder => $conversations
                ->selectRaw('1')
                ->from('conversations')
                ->whereColumn('conversations.edition_id', 'editions.id')
                ->where('conversations.type', 'edition'))
            ->orderBy('id')
            ->chunkById(200, function ($editions) use ($timestamp): void {
                DB::table('conversations')->insert($editions->map(fn ($edition): array => [
                    'edition_id' => $edition->id,
                    'type' => 'edition',
                    'assignment_id' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])->all());
            });

        DB::statement("CREATE UNIQUE INDEX conversations_one_edition_chat_unique ON conversations (edition_id) WHERE type = 'edition'");

        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('edition_id')->nullable()->after('id');
        });
        DB::statement('UPDATE messages SET edition_id = (SELECT edition_id FROM conversations WHERE conversations.id = messages.conversation_id)');
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('edition_id')->nullable(false)->change();
            $table->dropForeign(['conversation_id']);
            $table->dropForeign(['sender_edition_participant_id']);
            $table->foreign(['conversation_id', 'edition_id'], 'messages_conversation_edition_fk')
                ->references(['id', 'edition_id'])->on('conversations')->cascadeOnDelete();
            $table->foreign(['sender_edition_participant_id', 'edition_id'], 'messages_sender_edition_fk')
                ->references(['id', 'edition_id'])->on('edition_participants')->restrictOnDelete();
        });

        Schema::table('conversation_reads', function (Blueprint $table) {
            $table->unsignedBigInteger('edition_id')->nullable()->after('id');
        });
        DB::statement('UPDATE conversation_reads SET edition_id = (SELECT edition_id FROM conversations WHERE conversations.id = conversation_reads.conversation_id)');
        Schema::table('conversation_reads', function (Blueprint $table) {
            $table->unsignedBigInteger('edition_id')->nullable(false)->change();
            $table->dropForeign(['conversation_id']);
            $table->dropForeign(['edition_participant_id']);
            $table->foreign(['conversation_id', 'edition_id'], 'conversation_reads_conversation_edition_fk')
                ->references(['id', 'edition_id'])->on('conversations')->cascadeOnDelete();
            $table->foreign(['edition_participant_id', 'edition_id'], 'conversation_reads_participant_edition_fk')
                ->references(['id', 'edition_id'])->on('edition_participants')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_reads', function (Blueprint $table) {
            $table->dropForeign('conversation_reads_conversation_edition_fk');
            $table->dropForeign('conversation_reads_participant_edition_fk');
            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $table->foreign('edition_participant_id')->references('id')->on('edition_participants')->cascadeOnDelete();
            $table->dropColumn('edition_id');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign('messages_conversation_edition_fk');
            $table->dropForeign('messages_sender_edition_fk');
            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $table->foreign('sender_edition_participant_id')->references('id')->on('edition_participants')->restrictOnDelete();
            $table->dropColumn('edition_id');
        });

        DB::statement('DROP INDEX conversations_one_edition_chat_unique');

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique('conversations_id_edition_unique');
        });
    }
};
