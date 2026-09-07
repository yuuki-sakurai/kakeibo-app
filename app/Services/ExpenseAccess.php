<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ExpenseAccess
{
    public const DEFAULT_CATEGORIES = ['housing', 'food', 'dining', 'transport', 'utilities', 'daily', 'shopping', 'health', 'leisure'];

    public static function userId(): int
    {
        abort_unless(auth()->check(), 401);

        return (int) auth()->id();
    }

    public static function categories(): Builder
    {
        $userId = self::userId();

        return DB::table('expense_categories')->where(function ($query) use ($userId) {
            $query->where('user_id', $userId)->orWhere(function ($defaults) {
                $defaults->whereNull('user_id')->whereIn('id', self::DEFAULT_CATEGORIES);
            });
        });
    }

    public static function imports(): Builder
    {
        return DB::table('expense_imports')->where('user_id', self::userId());
    }
}
