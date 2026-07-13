<?php

use Illuminate\Database\Migrations\Migration;
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
        if (DB::getDriverName() === 'sqlite') {
            DB::statement(<<<'SQL'
                CREATE TABLE "assignments" (
                    "id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,
                    "edition_id" integer NOT NULL,
                    "giver_edition_participant_id" integer NOT NULL,
                    "receiver_edition_participant_id" integer NOT NULL,
                    "created_at" datetime,
                    "updated_at" datetime,
                    CONSTRAINT "assignments_participants_must_differ" CHECK ("giver_edition_participant_id" <> "receiver_edition_participant_id"),
                    CONSTRAINT "assignments_giver_edition_fk" FOREIGN KEY ("giver_edition_participant_id", "edition_id") REFERENCES "edition_participants" ("id", "edition_id") ON DELETE RESTRICT,
                    CONSTRAINT "assignments_receiver_edition_fk" FOREIGN KEY ("receiver_edition_participant_id", "edition_id") REFERENCES "edition_participants" ("id", "edition_id") ON DELETE RESTRICT,
                    FOREIGN KEY ("edition_id") REFERENCES "editions" ("id") ON DELETE CASCADE,
                    UNIQUE ("edition_id", "giver_edition_participant_id"),
                    UNIQUE ("edition_id", "receiver_edition_participant_id")
                )
                SQL);

            return;
        }

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edition_id');
            $table->unsignedBigInteger('giver_edition_participant_id');
            $table->unsignedBigInteger('receiver_edition_participant_id');
            $table->timestamps();

            $table->foreign(['giver_edition_participant_id', 'edition_id'], 'assignments_giver_edition_fk')
                ->references(['id', 'edition_id'])->on('edition_participants')->restrictOnDelete();
            $table->foreign(['receiver_edition_participant_id', 'edition_id'], 'assignments_receiver_edition_fk')
                ->references(['id', 'edition_id'])->on('edition_participants')->restrictOnDelete();
            $table->foreign('edition_id')->references('id')->on('editions')->cascadeOnDelete();
            $table->unique(['edition_id', 'giver_edition_participant_id']);
            $table->unique(['edition_id', 'receiver_edition_participant_id']);
        });

        DB::statement('ALTER TABLE assignments ADD CONSTRAINT assignments_participants_must_differ CHECK (giver_edition_participant_id <> receiver_edition_participant_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
