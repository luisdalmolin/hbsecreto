<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteForPurchases();

            return;
        }

        Schema::table('draw_constraints', function (Blueprint $table): void {
            $table->unsignedBigInteger('order_id')->nullable();
        });

        DB::statement('ALTER TABLE draw_constraints DROP CONSTRAINT draw_constraints_source_check');
        DB::statement('ALTER TABLE draw_constraints ALTER COLUMN created_by DROP NOT NULL');
        DB::statement('ALTER TABLE draw_constraints DROP CONSTRAINT draw_constraints_unique_pair');
        DB::statement('ALTER TABLE draw_constraints ADD CONSTRAINT draw_constraints_unique_pair UNIQUE (edition_id, type, giver_edition_participant_id, receiver_edition_participant_id, source)');
        DB::statement("ALTER TABLE draw_constraints ADD CONSTRAINT draw_constraints_source_check CHECK (source IN ('admin', 'purchase'))");
        DB::statement("ALTER TABLE draw_constraints ADD CONSTRAINT draw_constraints_source_shape_check CHECK ((source = 'admin' AND order_id IS NULL AND created_by IS NOT NULL) OR (source = 'purchase' AND type = 'must_pair' AND order_id IS NOT NULL AND created_by IS NULL))");
        DB::statement('ALTER TABLE draw_constraints ADD CONSTRAINT draw_constraints_order_edition_fk FOREIGN KEY (order_id, edition_id) REFERENCES orders (id, edition_id) ON DELETE RESTRICT');
        DB::statement('CREATE UNIQUE INDEX draw_constraints_order_unique ON draw_constraints (order_id) WHERE order_id IS NOT NULL');
        DB::statement("CREATE UNIQUE INDEX draw_constraints_purchase_giver_unique ON draw_constraints (edition_id, giver_edition_participant_id) WHERE source = 'purchase'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteForAdmins();

            return;
        }

        DB::table('draw_constraints')->where('source', 'purchase')->delete();
        DB::statement('DROP INDEX draw_constraints_purchase_giver_unique');
        DB::statement('DROP INDEX draw_constraints_order_unique');
        DB::statement('ALTER TABLE draw_constraints DROP CONSTRAINT draw_constraints_order_edition_fk');
        DB::statement('ALTER TABLE draw_constraints DROP CONSTRAINT draw_constraints_source_shape_check');
        DB::statement('ALTER TABLE draw_constraints DROP CONSTRAINT draw_constraints_unique_pair');
        DB::statement('ALTER TABLE draw_constraints ADD CONSTRAINT draw_constraints_unique_pair UNIQUE (edition_id, type, giver_edition_participant_id, receiver_edition_participant_id)');
        DB::statement('ALTER TABLE draw_constraints DROP CONSTRAINT draw_constraints_source_check');
        DB::statement("ALTER TABLE draw_constraints ADD CONSTRAINT draw_constraints_source_check CHECK (source IN ('admin'))");
        DB::statement('ALTER TABLE draw_constraints ALTER COLUMN created_by SET NOT NULL');

        Schema::table('draw_constraints', function (Blueprint $table): void {
            $table->dropColumn('order_id');
        });
    }

    private function rebuildSqliteForPurchases(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('ALTER TABLE draw_constraints RENAME TO draw_constraints_legacy');
        DB::statement(<<<'SQL'
            CREATE TABLE "draw_constraints" (
                "id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,
                "edition_id" integer NOT NULL,
                "type" varchar NOT NULL CHECK ("type" IN ('must_not_pair', 'must_pair')),
                "giver_edition_participant_id" integer NOT NULL,
                "receiver_edition_participant_id" integer NOT NULL,
                "source" varchar NOT NULL DEFAULT 'admin' CHECK ("source" IN ('admin', 'purchase')),
                "order_id" integer,
                "created_by" integer,
                "created_at" datetime,
                "updated_at" datetime,
                CONSTRAINT "draw_constraints_participants_must_differ" CHECK ("giver_edition_participant_id" <> "receiver_edition_participant_id"),
                CONSTRAINT "draw_constraints_exclusions_normalized" CHECK ("type" <> 'must_not_pair' OR "giver_edition_participant_id" < "receiver_edition_participant_id"),
                CONSTRAINT "draw_constraints_source_shape_check" CHECK (("source" = 'admin' AND "order_id" IS NULL AND "created_by" IS NOT NULL) OR ("source" = 'purchase' AND "type" = 'must_pair' AND "order_id" IS NOT NULL AND "created_by" IS NULL)),
                CONSTRAINT "draw_constraints_giver_edition_fk" FOREIGN KEY ("giver_edition_participant_id", "edition_id") REFERENCES "edition_participants" ("id", "edition_id") ON DELETE CASCADE,
                CONSTRAINT "draw_constraints_receiver_edition_fk" FOREIGN KEY ("receiver_edition_participant_id", "edition_id") REFERENCES "edition_participants" ("id", "edition_id") ON DELETE CASCADE,
                CONSTRAINT "draw_constraints_order_edition_fk" FOREIGN KEY ("order_id", "edition_id") REFERENCES "orders" ("id", "edition_id") ON DELETE RESTRICT,
                FOREIGN KEY ("edition_id") REFERENCES "editions" ("id") ON DELETE CASCADE,
                FOREIGN KEY ("created_by") REFERENCES "users" ("id") ON DELETE RESTRICT,
                CONSTRAINT "draw_constraints_unique_pair" UNIQUE ("edition_id", "type", "giver_edition_participant_id", "receiver_edition_participant_id", "source")
            )
            SQL);
        DB::statement('INSERT INTO draw_constraints (id, edition_id, type, giver_edition_participant_id, receiver_edition_participant_id, source, created_by, created_at, updated_at) SELECT id, edition_id, type, giver_edition_participant_id, receiver_edition_participant_id, source, created_by, created_at, updated_at FROM draw_constraints_legacy');
        DB::statement('DROP TABLE draw_constraints_legacy');
        DB::statement('CREATE INDEX draw_constraints_edition_giver_index ON draw_constraints (edition_id, giver_edition_participant_id)');
        DB::statement('CREATE INDEX draw_constraints_edition_receiver_index ON draw_constraints (edition_id, receiver_edition_participant_id)');
        DB::statement('CREATE UNIQUE INDEX draw_constraints_order_unique ON draw_constraints (order_id) WHERE order_id IS NOT NULL');
        DB::statement("CREATE UNIQUE INDEX draw_constraints_purchase_giver_unique ON draw_constraints (edition_id, giver_edition_participant_id) WHERE source = 'purchase'");
        Schema::enableForeignKeyConstraints();
    }

    private function rebuildSqliteForAdmins(): void
    {
        DB::table('draw_constraints')->where('source', 'purchase')->delete();
        Schema::disableForeignKeyConstraints();
        DB::statement('ALTER TABLE draw_constraints RENAME TO draw_constraints_purchase_legacy');
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
        DB::statement('INSERT INTO draw_constraints (id, edition_id, type, giver_edition_participant_id, receiver_edition_participant_id, source, created_by, created_at, updated_at) SELECT id, edition_id, type, giver_edition_participant_id, receiver_edition_participant_id, source, created_by, created_at, updated_at FROM draw_constraints_purchase_legacy');
        DB::statement('DROP TABLE draw_constraints_purchase_legacy');
        DB::statement('CREATE INDEX draw_constraints_edition_giver_index ON draw_constraints (edition_id, giver_edition_participant_id)');
        DB::statement('CREATE INDEX draw_constraints_edition_receiver_index ON draw_constraints (edition_id, receiver_edition_participant_id)');
        Schema::enableForeignKeyConstraints();
    }
};
