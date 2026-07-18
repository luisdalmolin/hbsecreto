<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->uuid('checkout_idempotency_key')->nullable()->unique()->after('provider_reference');
            $table->timestamp('checkout_expires_at')->nullable()->index()->after('checkout_idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['checkout_idempotency_key']);
            $table->dropIndex(['checkout_expires_at']);
            $table->dropColumn(['checkout_idempotency_key', 'checkout_expires_at']);
        });
    }
};
