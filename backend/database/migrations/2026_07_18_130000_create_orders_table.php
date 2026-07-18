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
            DB::statement(<<<'SQL'
                CREATE TABLE "orders" (
                    "id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,
                    "user_id" integer NOT NULL,
                    "edition_id" integer NOT NULL,
                    "type" varchar NOT NULL CHECK ("type" IN ('pick_purchase')),
                    "amount_cents" integer NOT NULL CHECK ("amount_cents" > 0),
                    "currency" varchar NOT NULL DEFAULT 'BRL' CHECK (length("currency") = 3 AND "currency" = upper("currency")),
                    "status" varchar NOT NULL DEFAULT 'pending' CHECK ("status" IN ('pending', 'paid', 'failed', 'refunded')),
                    "payment_provider" varchar NOT NULL,
                    "provider_reference" varchar,
                    "paid_at" datetime,
                    "metadata" text NOT NULL DEFAULT '{}',
                    "created_at" datetime,
                    "updated_at" datetime,
                    FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE RESTRICT,
                    FOREIGN KEY ("edition_id") REFERENCES "editions" ("id") ON DELETE CASCADE,
                    UNIQUE ("id", "edition_id"),
                    UNIQUE ("payment_provider", "provider_reference")
                )
                SQL);
            DB::statement('CREATE INDEX orders_user_created_index ON orders (user_id, created_at)');
            DB::statement('CREATE INDEX orders_edition_status_index ON orders (edition_id, status)');

            return;
        }

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('BRL');
            $table->string('status')->default('pending');
            $table->string('payment_provider');
            $table->string('provider_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->default('{}');
            $table->timestamps();

            $table->unique(['id', 'edition_id']);
            $table->unique(['payment_provider', 'provider_reference']);
            $table->index(['user_id', 'created_at']);
            $table->index(['edition_id', 'status']);
        });

        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_type_check CHECK (type IN ('pick_purchase'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ('pending', 'paid', 'failed', 'refunded'))");
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_amount_positive CHECK (amount_cents > 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_currency_check CHECK (length(currency) = 3 AND currency = upper(currency))');
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
