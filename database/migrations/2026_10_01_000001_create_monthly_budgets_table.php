<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedBigInteger('amount')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'year', 'month']);
        });

        Schema::create('monthly_category_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_budget_id')->constrained()->cascadeOnDelete();
            $table->string('category_id', 32);
            $table->unsignedBigInteger('amount');
            $table->timestamps();
            $table->foreign('category_id')->references('id')->on('expense_categories')->cascadeOnDelete();
            $table->unique(['monthly_budget_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_category_budgets');
        Schema::dropIfExists('monthly_budgets');
    }
};
