<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->string('id', 32)->primary();
            $table->string('name', 50);
        });
        $categories = ['housing' => '住居', 'food' => '食費', 'dining' => '外食', 'transport' => '交通', 'utilities' => '水道・光熱', 'daily' => '日用品', 'shopping' => '買い物', 'health' => '健康', 'leisure' => '趣味・娯楽'];
        foreach ($categories as $id => $name) {
            DB::table('expense_categories')->insert(compact('id', 'name'));
        }
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('store', 255)->index();
            $table->string('category_id', 32);
            $table->foreign('category_id')->references('id')->on('expense_categories')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_items');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
