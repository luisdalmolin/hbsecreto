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
                CREATE TABLE "draw_constraints" (
                    "id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,
                    "edition_id" integer NOT NULL,
                    "type" varchar NOT NULL CHECK ("type" IN ('must_not_pair', 'must_pair')),
                    "giver_edition_participant_id" integer NOT NULL,
                    "receiver_edition_participant_id" integer NOT NULL,
                    "source" varchar NOT NULL DEFAULT 'admin' CHECK ("source" IN ('admin')),
                    "created_by" integer NOT NULL,
                    "created_at" datetime,
                    "updated_at" datetime,
                    CONSTRAINT "draw_constraints_participants_must_differ" CHECK ("giver_edition_participant_id" <> "receiver_edition_participant_id"),
                    CONSTRAINT "draw_constraints_exclusions_normalized" CHECK ("type" <> 'must_not_pair' OR "giver_edition_participant_id" < "receiver_edition_participant_id"),
                    CONSTRAINT "draw_constraints_giver_edition_fk" FOREIGN KEY ("giver_edition_participant_id", "edition_id") REFERENCES "edition_participants" ("id", "edition_id") ON DELETE CASCADE,
                    CONSTRAINT "draw_constraints_receiver_edition_fk" FOREIGN KEY ("receiver_edition_participant_id", "edition_id") REFERENCES "edition_participants" ("id", "edition_id") ON DELETE CASCADE,
                    FOREIGN KEY ("edition_id") REFERENCES "editions" ("id") ON DELETE CASCADE,
                    FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE RESTRICT,
                    CONSTRAINT "draw_constraints_unique_pair" UNIQUE ("edition_id", "type", "giver_edition_participant_id", "receiver_edition_participant_id")
                )
                SQL);
            DB::statement('CREATE INDEX "draw_constraints_edition_giver_index" ON "draw_constraints" ("edition_id", "giver_edition_participant_id")');
            DB::statement('CREATE INDEX "draw_constraints_edition_receiver_index" ON "draw_constraints" ("edition_id", "receiver_edition_participant_id")');

            return;
        }

        Schema::create('draw_constraints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edition_id');
            $table->enum('type', ['must_not_pair', 'must_pair']);
            $table->unsignedBigInteger('giver_edition_participant_id');
            $table->unsignedBigInteger('receiver_edition_participant_id');
            $table->enum('source', ['admin'])->default('admin');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->foreign(['giver_edition_participant_id', 'edition_id'], 'draw_constraints_giver_edition_fk')
                ->references(['id', 'edition_id'])->on('edition_participants')->cascadeOnDelete();
            $table->foreign(['receiver_edition_participant_id', 'edition_id'], 'draw_constraints_receiver_edition_fk')
                ->references(['id', 'edition_id'])->on('edition_participants')->cascadeOnDelete();
            $table->foreign('edition_id')->references('id')->on('editions')->cascadeOnDelete();
            $table->unique(['edition_id', 'type', 'giver_edition_participant_id', 'receiver_edition_participant_id'], 'draw_constraints_unique_pair');
            $table->index(['edition_id', 'giver_edition_participant_id']);
            $table->index(['edition_id', 'receiver_edition_participant_id']);
        });

        DB::statement('ALTER TABLE draw_constraints ADD CONSTRAINT draw_constraints_participants_must_differ CHECK (giver_edition_participant_id <> receiver_edition_participant_id)');
        DB::statement("ALTER TABLE draw_constraints ADD CONSTRAINT draw_constraints_exclusions_normalized CHECK (type <> 'must_not_pair' OR giver_edition_participant_id < receiver_edition_participant_id)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draw_constraints');
    }
};
