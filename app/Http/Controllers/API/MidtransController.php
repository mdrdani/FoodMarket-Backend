<?php

namespace App\Http\Controllers\API;

use Midtrans\Config;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Transactions;
use Midtrans\Notification;

class MidtransController extends Controller
{
    //
    public function callback(Request $request) {
        // set konfigurasi midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // buat instance midtrans nofitification
        $notification = new Notification();

        // assign ke vairble untuk memudahkan coding
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $notification->order_id;

        // cari transaksi berdasarkan ID
        $transaction = Transactions::findOrFail($order_id);

        // handle notifgikasi status midtrans
        if($status == 'capture'){
            if($type == 'credit_card'){
                if($fraud == 'challenge'){
                    $transaction->status = 'PENDING';
                }else{
                    $transaction->status = 'SUCCESS';
                }
            }
        }
        else if($status == 'settlement'){
            $transaction->status = 'SUCCESS';
        }
        else if($status == 'pending'){
            $transaction->status = 'PENDING';
        }
        else if($status == 'deny'){
            $transaction->status = 'CANCELED';
        }
        else if($status == 'expire'){
            $transaction->status = 'CANCELED';
        }
        else if($status == 'cancel'){
            $transaction->status = 'CANCELED';
        }

        // simpan transaksi
        $transaction->save();


    }

    public function success(){
        return view('midtrans.success');
    }

    public function unfinish(){
        return view('midtrans.unfinish');
    }

    public function error(){
        return view('midtrans.error');
    }
}
