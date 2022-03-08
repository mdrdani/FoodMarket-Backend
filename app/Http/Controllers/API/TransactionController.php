<?php

namespace App\Http\Controllers\API;

use App\Models\Transactions;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;

class TransactionController extends Controller
{
    //
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');

        if($id)
        {
            $transaction = Transactions::with(['food','user'])->find($id);

            if($transaction)
            {
                return ResponseFormatter::success(
                    $transaction,
                    'data transaksi berhasil di ambil'
                );
            }
            else
            {
                return ResponseFormatter::error(
                    null,
                    'Data transkasi tidak ada',
                    404
                );
            }

        }

        $transaction = Transactions::with(['food','user'])
                        ->where('user_id', Auth::user()->id);

        if($food_id)
        {
            $transaction->where('food_id', $food_id);
        }

        if($status)
        {
            $transaction->where('status', $status);
        }

        

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data List Transaksi Berhasil di ambil'
        );
    }

    public function update(Request $request, $id)
    {
        $transaction = Transactions::findOrFail($id);
        $transaction->update($request->all);

        return ResponseFormatter::success($transaction, 'Transaksi Berhasil di perbaharui');
    }

    public function checkout(Request $request) {
        $request->validate([
            'food_id' => 'required|exists:food,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required',
            'total' => 'required',
            'status' => 'required'
        ]);

        $transaction = Transactions::create([
            'food_id' => $request->food_id,
            'user_id' => $request->user_id,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status' => $request->staus,
            'payment_url' => '',
        ]);

        // konfigurasi Midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // panggil transkasi yang tdai di buat
        $transaction = Transactions::with(['food','user'])->find($transaction->id);

        // membuat transaksi midtrans
        $midtrans = [
            'transaction_details' => [
                'order_id' => $transaction->id,
                'gross_amount' => (int) $transaction->total,
            ],
            'customer_details' => [
                'first_name' => $transaction->user->name,
                'email' => $transaction->user->email,
            ],
            'enabled_payments' => ['gopay','bank_transfer'],
            'vtweb' => []
            ];

        // memanggil midtrans
            try {
                // ambil halaman payment midtrans
                $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;

                $transaction->payment_url = $paymentUrl;
                $transaction->save();
                
                // mengembalikan Data ke API
                return ResponseFormatter::success($transaction, 'Transaksi Berhasil');
            }
            catch(Exception $e){
                return ResponseFormatter::error($e->getMessage(), 'transaksi Gagal');
            } 

    }
    
}
