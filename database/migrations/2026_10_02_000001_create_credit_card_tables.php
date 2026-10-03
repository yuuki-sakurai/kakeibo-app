<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('bank_name', 100);
            $table->string('branch_name', 100)->default('');
            $table->string('account_type', 20)->default('普通');
            $table->bigInteger('balance')->default(0);
            $table->string('color', 7)->default('#6255d9');
            $table->timestamps();
        });
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->unsignedTinyInteger('closing_day')->default(31);
            $table->unsignedTinyInteger('payment_day')->default(27);
            $table->unsignedTinyInteger('payment_month_offset')->default(1);
            $table->foreignId('bank_account_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('credit_card_id')->constrained()->restrictOnDelete();
            $table->string('file_name');
            $table->char('fingerprint', 64);
            $table->unsignedInteger('imported_count');
            $table->timestamps();
            $table->unique(['user_id', 'credit_card_id', 'fingerprint'], 'statement_import_fingerprint_unique');
        });
        Schema::create('credit_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('credit_card_id')->constrained()->restrictOnDelete();
            $table->foreignId('statement_import_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->string('merchant', 200);
            $table->string('category_id', 32)->nullable();
            $table->foreign('category_id')->references('id')->on('expense_categories')->restrictOnDelete();
            $table->bigInteger('amount');
            $table->date('payment_month');
            $table->timestamps();
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'payment_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_card_transactions');
        Schema::dropIfExists('statement_imports');
        Schema::dropIfExists('credit_cards');
        Schema::dropIfExists('bank_accounts');
    }
};
