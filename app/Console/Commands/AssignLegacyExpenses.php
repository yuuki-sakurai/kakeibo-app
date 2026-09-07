<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ExpenseAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignLegacyExpenses extends Command
{
    protected $signature = 'expenses:assign-legacy {email}';

    protected $description = 'Assign unowned legacy household data to an explicitly verified account';

    public function handle(): int
    {
        $user = User::where('email', mb_strtolower(trim($this->argument('email'))))->first();
        if (! $user) {
            $this->error('先に対象アカウントを新規登録してください。');

            return self::FAILURE;
        }
        DB::transaction(function () use ($user) {
            $legacy = DB::table('expense_categories')->whereNull('user_id')->whereNotIn('id', ExpenseAccess::DEFAULT_CATEGORIES)->lockForUpdate()->get();
            foreach ($legacy as $category) {
                if (DB::table('expense_categories')->where('user_id', $user->id)->where('name', $category->name)->exists()) {
                    throw new \RuntimeException('同名の独自カテゴリがあります。引き継ぎ前に管理者が確認してください。');
                }
            }
            DB::table('expense_categories')->whereNull('user_id')->whereNotIn('id', ExpenseAccess::DEFAULT_CATEGORIES)->update(['user_id' => $user->id]);
            DB::table('expenses')->whereNull('user_id')->update(['user_id' => $user->id]);
            // Identical CSVs already imported by this user remain deduplicated.
            foreach (DB::table('expense_imports')->whereNull('user_id')->lockForUpdate()->get() as $import) {
                if (DB::table('expense_imports')->where('user_id', $user->id)->where('fingerprint', $import->fingerprint)->exists()) {
                    DB::table('expense_imports')->where('id', $import->id)->delete();
                } else {
                    DB::table('expense_imports')->where('id', $import->id)->update(['user_id' => $user->id]);
                }
            }
        });
        $this->info('既存データの引き継ぎが完了しました。');

        return self::SUCCESS;
    }
}
