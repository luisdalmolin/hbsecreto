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
                CREATE TABLE "conversations" (
                    "id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,
                    "edition_id" integer NOT NULL,
                    "type" varchar NOT NULL CHECK ("type" IN ('edition', 'assignment')),
                    "assignment_id" integer,
                    "created_at" datetime,
                    "updated_at" datetime,
                    CONSTRAINT "conversations_assignment_shape_check" CHECK (("type" = 'assignment' AND "assignment_id" IS NOT NULL) OR ("type" = 'edition' AND "assignment_id" IS NULL)),
                    CONSTRAINT "conversations_assignment_edition_fk" FOREIGN KEY ("assignment_id", "edition_id") REFERENCES "assignments" ("id", "edition_id") ON DELETE CASCADE,
                    FOREIGN KEY ("edition_id") REFERENCES "editions" ("id") ON DELETE CASCADE,
                    UNIQUE ("assignment_id")
                )
                SQL);
            DB::statement('CREATE INDEX "conversations_edition_type_index" ON "conversations" ("edition_id", "type")');

            return;
        }

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edition_id');
            $table->string('type');
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->timestamps();

            $table->foreign(['assignment_id', 'edition_id'], 'conversations_assignment_edition_fk')
                ->references(['id', 'edition_id'])->on('assignments')->cascadeOnDelete();
            $table->foreign('edition_id')->references('id')->on('editions')->cascadeOnDelete();
            $table->unique('assignment_id');
            $table->index(['edition_id', 'type']);
        });

        DB::statement("ALTER TABLE conversations ADD CONSTRAINT conversations_type_check CHECK (type IN ('edition', 'assignment'))");
        DB::statement("ALTER TABLE conversations ADD CONSTRAINT conversations_assignment_shape_check CHECK ((type = 'assignment' AND assignment_id IS NOT NULL) OR (type = 'edition' AND assignment_id IS NULL))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
