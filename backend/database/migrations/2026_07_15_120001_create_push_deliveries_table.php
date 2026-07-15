<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id');
            $table->string('notification_type');
            $table->foreignId('push_device_id')->nullable()->constrained()->nullOnDelete();
            $table->char('expo_push_token_hash', 64);
            $table->string('expo_ticket_id')->nullable()->unique();
            $table->string('status');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('receipt_checked_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['notification_id', 'push_device_id']);
            $table->index(['status', 'attempted_at', 'receipt_checked_at'], 'push_deliveries_receipt_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_deliveries');
    }
};
