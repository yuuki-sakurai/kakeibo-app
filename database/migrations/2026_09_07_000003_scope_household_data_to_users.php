<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['expenses', 'expense_categories', 'expense_imports'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                // Legacy data stays unassigned until explicitly transferred by the administrator.
                $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            });
        }
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['user_id', 'name']);
        });
        Schema::table('expense_imports', function (Blueprint $table) {
            $table->dropUnique(['fingerprint']);
            $table->unique(['user_id', 'fingerprint']);
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        // Returning to a shared ledger would expose private data and may collide on names/hashes.
        throw new RuntimeException('User isolation cannot be rolled back automatically. Restore a reviewed backup instead.');
    }
};
