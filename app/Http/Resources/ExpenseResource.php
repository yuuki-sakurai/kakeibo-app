<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'date' => $this->date,
            'store' => $this->store,
            'category' => $this->category_id,
            'items' => $this->items->map(fn ($item) => [
                'id' => (string) $item->id,
                'name' => $item->name,
                'unitPrice' => $item->unit_price,
                'quantity' => $item->quantity,
            ])->all(),
            'total' => $this->items->sum(fn ($item) => $item->unit_price * $item->quantity),
        ];
    }
}
