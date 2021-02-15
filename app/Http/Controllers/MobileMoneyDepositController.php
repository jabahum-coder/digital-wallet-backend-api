<?php

namespace App\Http\Controllers;

use App\Jobs\MobileMoneyDeposit;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Webpatser\Uuid\Uuid;

class MobileMoneyDepositController extends Controller
{
    public function mmdeposit(Request $request)
    {
        $wallet = Wallet::whereWalletNo($request->input('walletNo'))->first();
        if(!$wallet) {
            return response()->json(['error' => "No Wallet with walletNo:{$request->input('walletNo')} found!"]);
        }
        DB::transaction(function () use ($wallet, $request) {
            // Create a Transaction
            $transaction = Transaction::create([
                'wallet_no' => $request->input('walletNo'),
                'amount' => $request->input('amount'),
                'description' => $request->input('description'),
                'transaction_type' => 'Deposit',

                "phone"         => $request->input('phone'),
                "reference"     => Uuid::generate(),
                "reason"        => "Wallet Deposit"
            ]);
    
            MobileMoneyDeposit::dispatch($transaction);

            // Update account balance
            $wallet->account_balance = $wallet->account_balance + $request->input('amount');
            $wallet->save();
        });

        return response()->json(["message" => 'Deposit Successfully']);
    }
}
