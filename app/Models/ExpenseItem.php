<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseItem extends Model
{
    protected $fillable = ['name', 'unit_price', 'quantity'];

    protected function casts(): array
    {
        return ['unit_price' => 'integer', 'quantity' => 'integer'];
    }
}
