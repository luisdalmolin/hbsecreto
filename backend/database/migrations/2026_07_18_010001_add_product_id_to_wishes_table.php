<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wishes', function (Blueprint $table): void {
            $table->foreignId('product_id')
                ->nullable()
                ->after('description')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wishes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
