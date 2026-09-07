<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_imports', function (Blueprint $table) {
            $table->id();
            $table->char('fingerprint', 64)->unique();
            $table->unsignedInteger('expense_count');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_imports');
    }
};
