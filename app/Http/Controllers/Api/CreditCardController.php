<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportStatementRequest;
use App\Http\Requests\ListCreditTransactionsRequest;
use App\Http\Requests\SaveBankAccountRequest;
use App\Http\Requests\SaveCreditCardRequest;
use App\Services\CreditCardService;
use App\Services\StatementImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditCardController extends Controller
{
    public function __construct(private CreditCardService $service) {}

    public function accounts(): JsonResponse
    {
        return response()->json($this->service->owned('bank_accounts')->orderBy('id')->get());
    }

    public function saveAccount(SaveBankAccountRequest $request, ?int $account = null): JsonResponse
    {
        $data = $request->validated();
        $data['branch_name'] = $data['branch_name'] ?? '';

        return response()->json($this->service->save('bank_accounts', $data, $account), $account === null ? 201 : 200);
    }

    public function cards(): JsonResponse
    {
        return response()->json($this->service->owned('credit_cards')->orderBy('id')->get());
    }

    public function saveCard(SaveCreditCardRequest $request, ?int $card = null): JsonResponse
    {
        return response()->json($this->service->save('credit_cards', $request->validated(), $card), $card === null ? 201 : 200);
    }

    public function transactions(ListCreditTransactionsRequest $request): JsonResponse
    {
        return response()->json($this->service->list($request->validated()));
    }

    public function overview(ListCreditTransactionsRequest $request): JsonResponse
    {
        return response()->json($this->service->overview($request->validated()));
    }

    public function imports(Request $request): JsonResponse
    {
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:1000000']]);
        $query = $this->service->owned('statement_imports');
        $total = (clone $query)->count();
        $page = (int) ($data['page'] ?? 1);
        $rows = $query->join('credit_cards as cards', 'cards.id', '=', 'credit_card_id')->select('statement_imports.*', 'cards.name as card_name')->orderByDesc('statement_imports.id')->offset(($page - 1) * 50)->limit(50)->get();

        return response()->json(['data' => $rows, 'page' => $page, 'total' => $total, 'per_page' => 50]);
    }

    public function import(ImportStatementRequest $request, StatementImportService $importer): JsonResponse
    {
        $result = $importer->import($request->file('file'), (int) $request->validated('credit_card_id'));

        return response()->json($result, $result['alreadyImported'] ? 200 : 201);
    }
}
