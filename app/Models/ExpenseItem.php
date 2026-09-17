<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseItem extends Model
{
    protected $fillable = ['name', 'unit_price', 'quantity', 'taxable', 'tax_rate', 'tax_amount'];

    protected function casts(): array
    {
        return ['unit_price' => 'integer', 'quantity' => 'integer', 'taxable' => 'boolean', 'tax_rate' => 'float', 'tax_amount' => 'integer'];
    }
}
