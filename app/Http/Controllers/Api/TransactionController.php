<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\Transaction\TransactionResource;
use App\Models\Transaction;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{



    public function index(): JsonResponse
    {
        $transactions = Transaction::useFilters()->dynamicPaginate();

        return ApiResponse::sendResponse(true, 'Data retrieve successful', new TransactionResource($transactions));
    }

    public function store(Request $request): JsonResponse
    {
        $transaction = Transaction::create($request->validated());

        return ApiResponse::sendResponse(true, 'Transaction created successfully', new TransactionResource($transaction));
    }

    public function show(Transaction $transaction): JsonResponse
    {
        return ApiResponse::sendResponse(true, 'Data Retrieve Successful', new TransactionResource($transaction));
    }

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $transaction->update($request->validated());

        return ApiResponse::sendResponse(true, 'Transaction updated Successfully', new TransactionResource($transaction));
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        $transaction->delete();

        return ApiResponse::sendResponse(true, 'Data Deleted Successful');
    }


}