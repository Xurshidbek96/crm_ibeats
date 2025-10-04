<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    private $_click_secret;
    public function __construct()
    {
        $this->_click_secret = env('CLICK_SECRET_KEY');
    }

    /*************************
     *	Click payment callback function
     */
    public function click(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
        ]);

        $invoice = Invoice::find( $request['order_id']);

        if($invoice !== null) {

            $service_id = env('CLICK_SERVICE_ID');
            $merchant_id = env('CLICK_MERCHANT_ID');
            $return_url = env('CLICK_RETURN_URL');
            $amount = $invoice['total_amount'];
            $id = $invoice['id'];

            $url = "https://my.click.uz/services/pay?service_id=$service_id&merchant_id=$merchant_id&amount=$amount&transaction_param=$id&return_url=$return_url";

            return response()->json([
                'status' => 'success',
                'data' => [
                    'url' => $url
                ]
            ]);
        }

        return response()->json(['status'=>'error','message'=>'Oops, something went wrong!']);
    }

    public function click_callback(Request $request)
    {
        switch ($request['action']) {
            case 0 : $this->click_prepare($request);
                break;
            case 1 : $this->click_complete($request);
                break;
            default:
                return response()->json(['status'=>'error','message'=>'Oops, something went wrong!']);
        }
    }

    public function payme(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:invoices,id',
        ]);

        $invoice = Invoice::find( $request->order_id );

        if($invoice !== null) {

            $merchant_id = env('PAYME_MERCHANT_ID');
            $account = $invoice->id;
            $amount = $invoice->total_amount;
            $callback = env('PAYME_CALLBACK');

            $url = "https://checkout.paycom.uz/" . base64_encode("m=$merchant_id;ac.order_id=$account;a=$amount;c=$callback;l=uz;ct=500;cr=uzs");
            return response()->json([
                'status' => 'success',
                'data' => [
                    'url' => $url
                ]
            ]);
        }
        return response()->json(['status'=>'error','message'=>'Oops, something went wrong!']);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    protected function click_prepare(Request $request): \Illuminate\Http\JsonResponse
    {

        $click_paydoc_id 		= $request->input('click_paydoc_id');
        $click_trans_id 		= $request->input('click_trans_id');
        $service_id 			= $request->input('service_id');
        $merchant_trans_id 	    = $request->input('merchant_trans_id');
        $amount 				= $request->amount;
        $action 				= $request->action;
        $error 					= $request->input('error');
        $sign_time 				= $request->input('sign_time');
        $sign_string 			= $request->input('sign_string');
        $transaction_id			= null;

        // Check signature
        $signature = md5($click_trans_id. $service_id. $this->_click_secret. $merchant_trans_id. $amount. $action. $sign_time);
        if($signature !== $sign_string) {
            $res_error = '-1';
            $res_error_note = 'ERROR: wrong sign';

            goto click_prepare_result;
        }

        $transaction = Transaction::where([
            'status' => 'completed',
            'merchant_trans_id' => $merchant_trans_id
        ])->first();

        if($transaction === null) {
            // Get invoice from db
            $invoice = Invoice::find( $merchant_trans_id );

            if($invoice !== null) {
                if($invoice->total_amount == $amount) {
                    // Create transaction
                    $transaction = new Transaction();
                    $transaction->user_id = $invoice->user_id;
                    $transaction->invoice_id = $invoice->id;
                    $transaction->merchant_trans_id = $invoice->id;
                    $transaction->amount = $invoice->total_amount;
                    $transaction->trans_id = $click_trans_id;
                    $transaction->paydoc_id = $click_paydoc_id;
                    $transaction->create_time = date('Y-m-d H:i:s');
                    $transaction->provider = 'click';
                    $transaction->sign = $sign_string;
                    $transaction->save();

                    $transaction_id = $transaction->id;

                    $res_error = 0;
                    $res_error_note = 'Success';
                    goto click_prepare_result;
                }
                else {
                    $res_error = '-2';
                    $res_error_note = 'ERROR: wrong payment amount';

                    goto click_prepare_result;
                }
            }
            else {
                $res_error = '-5';
                $res_error_note = 'ERROR: order not found';

                goto click_prepare_result;
            }
        }
        else {
            $res_error = '-4';
            $res_error_note = 'ERROR: transaction already completed';
            goto click_prepare_result;
        }

        click_prepare_result:
        return response()->json([
            'error' => $res_error,
            'error_note' => $res_error_note,
            'click_trans_id' => $click_trans_id,
            'merchant_trans_id' => $merchant_trans_id,
            'merchant_prepare_id' => $transaction_id,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    protected function click_complete(Request $request): \Illuminate\Http\JsonResponse
    {
        $click_paydoc_id 			= $request->input('click_paydoc_id');
        $click_trans_id 			= $request->input('click_trans_id');
        $service_id 				= $request->input('service_id');
        $merchant_trans_id 		    = $request->input('merchant_trans_id');
        $merchant_prepare_id 	    = $request->input('merchant_prepare_id');
        $amount 					= $request->amount;
        $action 					= $request->action;
        $error 						= $request->input('error');
        $error_note 				= $request->input('error_note');
        $sign_time 					= $request->input('sign_time');
        $sign_string 				= $request->input('sign_string');

        // Check signature
        $signature = md5($click_trans_id. $service_id. $this->_click_secret. $merchant_trans_id. $merchant_prepare_id. $amount. $action. $sign_time);
        if($signature !== $sign_string) {
            $res_error = '-1';
            $res_error_note = 'ERROR: wrong sign';

            goto click_complete_result;
        }

        // Get transaction from db
        $transaction = Transaction::query()->find($merchant_prepare_id);

        if($transaction !== null) {
            // Check for errors
            if($error == '-5017' || $transaction->status == 'canceled') {
                if($transaction->status !== 'canceled') {
                    $transaction->status = 'canceled';
                    $transaction->state = $error;
                    $transaction->reason = $error_note;
                    $transaction->cancel_time = date('Y-m-d H:i:s');
                    $transaction->save();

                    $transaction->invoice()->update([
                        'status' => 'not_paid'
                    ]);
                }
                elseif ($error == '-1') {
                    $res_error = '-4';
                    $res_error_note = 'ERROR: transaction already completed';
                    goto click_complete_result;
                }

                $res_error = '-9';
                $res_error_note = 'ERROR: transaction canceled';
                goto click_complete_result;
            }

            // Complete payment
            if($transaction->amount == $amount) {
                if($transaction->status === 'prepared')
                {
                    $transaction->status = 'completed';
                    $transaction->perform_time = date('Y-m-d H:i:s');
                    $transaction->save();

                    $transaction->invoice()->update([
                        'status' => 'prepaid',
                        'provider' => 'click',
                        'date' => date('Y-m-d')
                    ]);

                    $res_error = '0';
                    $res_error_note = 'Success';
                    goto click_complete_result;
                }
                elseif($transaction->status === 'completed') {
                    $res_error = '-4';
                    $res_error_note = 'ERROR: transaction already completed';
                    goto click_complete_result;
                }
            }
            else {
                $res_error = '-2';
                $res_error_note = 'ERROR: wrong payment amount';

                goto click_complete_result;
            }
        }
        else {
            $res_error = '-6';
            $res_error_note = 'ERROR: transaction not found';
            goto click_complete_result;
        }


        click_complete_result:
        return response()->json([
            'error' => $res_error,
            'error_note' => $res_error_note,
            'click_trans_id' => $click_trans_id,
            'merchant_trans_id' => $merchant_trans_id,
            'merchant_confirm_id' => 0,
        ]);
    }

    public function uzcardhumo(Request $request)
    {
        $invoice = Invoice::find( $request->id );

        if($invoice !== null) {
            $total_price = $invoice->total_amount * 100;
            // $total_price = str_replace(',','',$total_price);
            // $total_price = str_replace('.','',$total_price);

            $user = User::find( $invoice->user_id );
            $MerID = 'fffa7bcde8f84181ad47cb9f65a6b309aGU1WXdLSElMSytXMlBPVWlacUhWQ1pXbGpLcmRwNURHZGI4VWswcDVUMHh0aDkvbFlkTEpFYis4MHFzWWxNRA==';
            $OrderID = $invoice->invoice_id;
            $PurchaseAmt = $total_price;
            $merchant_url = 'https://pay.ofb.uz';
            $callback_url = 'https://my.eskiz.uz/Invoices/payment-callback';

            if( $user !== null) {
                $data = array(
                    'merchant_id' 		=> $MerID,
                    'order_id' 			=> $OrderID,
                    'amount' 			=> $PurchaseAmt,
                    'currency' 			=> 'UZS',
                    'payment' 			=> 'uzcardhumo',
                    'callback_url' 		=> $callback_url,
                    'url' 		        => $merchant_url,
                    'contract_number'	=> $invoice->invoice_id,
                    'contract_date' 	=> date("d.m.Y", strtotime($invoice->date))
                );

                $transaction = Transaction::where(['invoice_id'=>$invoice->invoice_id, 'payment_code' => 'uzcardhumo'])->first();

                if(!$transaction)
                    $transaction = new Transaction();

                $transaction->user_id = $invoice->user_id;
                $transaction->invoice_id = $invoice->invoice_id;
                $transaction->merchant_trans_id = '';
                $transaction->amount = $invoice->total_amount;
                $transaction->create_time = date('Y-m-d H:i:s');
                $transaction->payment_code = 'uzcardhumo';
                $transaction->status = 'prepared';
                $transaction->state = '';
                $transaction->save();

                return response()->json($data);
            }
        }
        return response()->json(['status'=>'error','message'=>'Oops, something went wrong!']);
    }


    /*************************
     *	Payme Callback function
     */
    public function payme_callback(Request $request)
    {
        Storage::disk('local')->put('file-payme-'.date('Y-m-d H-i-s').'.txt', json_encode($request->all()));

        // Paycom Authorization
        $headers = getallheaders();
        if (!$headers || !isset($headers['Authorization']) || !preg_match('/^\s*Basic\s+(\S+)\s*$/i', $headers['Authorization'], $matches) || base64_decode($matches[1]) != "Paycom:" . env('PAYME_KEY'))
        {
            return response()->json(['error' => [
                'code'=>-32504,
                'message'=>['ru'=>'Неверная авторизация','uz'=>'Login noto`g`ri','en'=>'Wrong authorization']
            ]]);
        }

        // All Paycom methods
        $method = $request->input('method');
        $params = $request->params;
        $amount = isset($params['amount']) ? substr($params['amount'], 0, -2) : '';

        if ($method === 'CheckPerformTransaction') {
            $invoice = Invoice::find( $params['account']['order_id'] );

            if($invoice !== null) {
                if($invoice->status === 'not_paid') {
                    if($invoice->total_amount == $amount) {
                        return response()->json(['result' => ['allow'=>true]]);
                    }
                    else
                        return response()->json(['error' => ['code'=>-31001,
                            'message'=>['ru'=>'Неверная сумма','uz'=>'Pul miqdori noto`g`ri','en'=>'Wrong total amount']
                        ]]);
                }
                else
                    return response()->json(['error' => ['code'=>-31051,
                        'message'=>['ru'=>'Заказ не доступен для оплаты','uz'=>'Bu buyurtma bo`yicha to`lov yo`q','en'=>'Order is not available for purchase']
                    ]]);
            }
            else
                return response()->json(['error' => ['code'=>-31050,
                    'message'=>['ru'=>'Заказ не найден','uz'=>'Buyurtma topilmadi','en'=>'Order not found']
                ]]);
        }

        elseif ($method === 'CreateTransaction') {
            $invoice = Invoice::find( $params['account']['order_id'] );

            if($invoice !== null) {
                if($invoice->total_amount == $amount) {
                    $transaction_id = $params['id'];

                    $transaction = Transaction::where([
//                        'trans_id' => $transaction_id,
                        'merchant_trans_id' => $invoice->id,
                        'status' => 'prepared'
                    ])->first();

                    if($transaction == null) {
                        $transaction = new Transaction();
                        $transaction->user_id = $invoice->user_id;
                        $transaction->invoice_id = $invoice->id;
                        $transaction->merchant_trans_id = $invoice->id;
                        $transaction->amount = $invoice->total_amount;
                        $transaction->trans_id = $transaction_id;
                        $transaction->provider_time = $params['time'];
                        $transaction->create_time = date('Y-m-d H:i:s');
                        $transaction->provider = 'payme';
                        $transaction->state = 1;
                        $transaction->save();

                        return response()->json(['result' => ['create_time'=>strtotime($transaction->create_time)*1000, 'transaction'=>(string)$transaction->id,'state'=>$transaction->state]]);
                    }
                    else
                        if($transaction_id == $transaction->trans_id)
                            return response()->json(['result' => ['create_time'=>strtotime($transaction->create_time)*1000, 'transaction'=>(string)$transaction->id,'state'=>$transaction->state]]);
                        else
                            return response()->json(['error' => ['code'=>-31051,
                                'message'=>['ru'=>'Счет в ожидании оплаты','uz'=>'Buyurtma to`lovi kutilmoqda','en'=>'Waiting order payment']
                            ]]);
                }
                else
                    return response()->json(['error' => ['code'=>-31001,
                        'message'=>['ru'=>'Неверная сумма','uz'=>'Pul miqdori noto`g`ri','en'=>'Wrong total amount']
                    ]]);
            }
            else
                return response()->json(['error' => ['code'=>-31050,
                    'message'=>['ru'=>'Заказ не найден','uz'=>'Buyurtma topilmadi','en'=>'Order not found']
                ]]);
        }

        elseif ($method === 'PerformTransaction') {
            $transaction = Transaction::query()
                ->where('provider', 'payme')
                ->where('trans_id', $params['id'])
                ->first();

            if ($transaction === null || $transaction->status === 'CANCELED')
                return response()->json(['error' => ['code'=>-31003,
                    'message'=>['ru'=>'Транзакция не найдена','uz'=>'Bitim topilmadi','en'=>'Transaction not found']
                ]]);

            elseif ($transaction->status === 'PREPARED') {
                $transaction->status = 'COMPLETED';
                $transaction->perform_time = date('Y-m-d H:i:s');
                $transaction->state = 2;
                $transaction->save();

//                $getPayment = Payment::where('code', 'payme')->first();

                // Update Invoice status
                $invoice = Invoice::find($transaction->invoice_id);
                $invoice->status = 'prepaid';
//                $invoice->provider = $getPayment->id;
                $invoice->provider = 'payme';
                $invoice->date = date('Y-m-d');
                $invoice->save();

                // Send notification to user
//                $this->_send_notification($invoice, 'payme');

                return response()->json(['result' => ['perform_time'=>strtotime($transaction->perform_time)*1000, 'transaction'=>"$transaction->id",'state'=>$transaction->state]]);
            }

            elseif ($transaction->status === 'COMPLETED') {
                return response()->json(['result' => ['perform_time'=>strtotime($transaction->perform_time)*1000, 'transaction'=>"$transaction->id",'state'=>$transaction->state]]);
            }
        }

        elseif ($method === 'CancelTransaction') {
            $transaction = Transaction::query()
                ->where([
                    'provider' => 'payme',
                    'trans_id' => $params['id']
                ])->first();

            if ($transaction->status !== 'CANCELED') {
                $transaction->status = 'CANCELED';
                $transaction->reason = $params['reason'];
                $transaction->cancel_time = date('Y-m-d H:i:s');
                $transaction->state = ($transaction->state == 1 || $transaction->state == -1) ? -1 : -2;
                $transaction->save();

                // Update invoice status to not paid
                 $invoice = Invoice::find( $transaction->invoice_id );
                 $invoice->status = 'not_paid';
                 $invoice->save();

                return response()->json(['result' => ['transaction'=>"$transaction->id",'cancel_time'=>strtotime($transaction->cancel_time)*1000,'state'=>$transaction->state]]);
            }

            elseif ($transaction->status === 'CANCELED')
                return response()->json(['result' => ['transaction'=>"$transaction->id",'cancel_time'=>strtotime($transaction->cancel_time)*1000,'state'=>$transaction->state]]);

            elseif ($transaction === null)
                return response()->json(['error' => ['code'=>-31003,
                    'message'=>['ru'=>'Транзакция не найдена','uz'=>'Bitim topilmadi','en'=>'Transaction not found']
                ]]);
        }

        elseif ($method === 'CheckTransaction') {
            $transaction = Transaction::where([
                'provider' => 'payme',
                'trans_id' => $params['id']
            ])->first();

            if($transaction !== null) {
                $create_time = ($transaction->create_time) ? strtotime($transaction->create_time)*1000 : 0;
                $perform_time = ($transaction->perform_time) ? strtotime($transaction->perform_time)*1000 : 0;
                $cancel_time = ($transaction->cancel_time) ? strtotime($transaction->cancel_time)*1000 : 0;

                return response()->json(['result' => ['create_time'=>$create_time,'perform_time'=>$perform_time,'cancel_time'=>$cancel_time,'transaction'=>"$transaction->id",'state'=>$transaction->state,'reason'=>$transaction->reason]]);
            }
            else
                return response()->json(['error' => ['code'=>-31003,
                    'message'=>['ru'=>'Транзакция не найдена','uz'=>'Bitim topilmadi','en'=>'Transaction not found']
                ]]);
        }

        elseif ($method === 'GetStatement') {

            // Extract the 'from' and 'to' dates from the DTO
            $fromDate = Carbon::parse($params['from'] / 1000);
            $toDate = Carbon::parse($params['to'] / 1000);

            // Query the transactions
            $transactions = Transaction::where('provider', 'payme')
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->get();

            // Map the transactions to the desired format
            $result = $transactions->map(function ($transaction) {
                // dd(is_null($transaction->created_at));
                return [
                    'id' => $transaction->trans_id,
                    'time' => Carbon::parse($transaction->created_at)->timestamp * 1000,  // Convert to milliseconds
                    'amount' => $transaction->amount,
                    'account' => [
                        'user_id' => $transaction->user_id,
                        'order_id' => $transaction->invoice_id,
                    ],
                    'create_time' => !is_null($transaction->create_time) ? Carbon::parse($transaction->create_time)->timestamp * 1000 : 0,
                    'perform_time' => !is_null($transaction->perform_time) ? Carbon::parse($transaction->perform_time)->timestamp * 1000 : 0,
                    'cancel_time' => !is_null($transaction->cancel_time) ? Carbon::parse($transaction->cancel_time)->timestamp * 1000 : 0,
                    'transaction' => $transaction->id,
                    'state' => $transaction->state,
                    'reason' => $transaction->reason ?? null,
                ];
            });

            return response()->json([
                'result' => [
                    'transactions' => $result,
                ]
            ]);
        }
    }

    public function uzum_check(Request $request)
    {
        $serviceId = $request['serviceId'];
        $my_serviceId = env('UZUM_SERVICE_ID');
        $order_id = $request['params']['account'];

        if ($serviceId === $my_serviceId) {
            $order = Invoice::query()->find($order_id);
            if ($order !== null) {
                if ($order->status === 'not_paid') {
                    return response()->json([
                        'serviceId' => $serviceId,
                        'timestamp' => Carbon::parse(now())->timestamp * 1000,
                        'status' => 'OK',
                        'data' => [
                            'order_id' => "$order->id"
                        ]
                    ]);
                } else {
                    return response()->json([
                        'serviceId' => $serviceId,
                        'timestamp' => Carbon::parse(now())->timestamp * 1000,
                        'status' => 'FAILED',
                        'errorCode' => 10008,
                    ]);
                }
            } else {
                return response()->json([
                    'serviceId' => $serviceId,
                    'timestamp' => Carbon::parse(now())->timestamp * 1000,
                    'status' => 'FAILED',
                    'errorCode' => 10007,
                ]);
            }
        } else {
            return response()->json([
                'serviceId' => $serviceId,
                'timestamp' => Carbon::parse(now())->timestamp * 1000,
                'status' => 'FAILED',
                'errorCode' => 10006,
            ]);
        }
    }

    public function uzum_create(Request $request)
    {
        $serviceId = $request['serviceId'];
        $create_time = $request['timestamp'];
        $transId = $request['transId'];
        $params = $request['params'];
        $amount = $request['amount'];

        $my_serviceId = env('UZUM_SERVICE_ID');

        $order = Invoice::query()->find($params['account']);
        if ($serviceId === $my_serviceId) {
            if (!is_null($order)) {
                if ($order['total_amount'] === $amount / 100) {
                    $transaction = Transaction::query()
                        ->where([
                            'trans_id' => $transId,
                            'merchant_trans_id' => $order['id'],
                            'status' => 'PREPARED'
                        ])->first();

                    if (is_null($transaction)) {
                        $transaction = Transaction::query()
                            ->create([
                                'merchant_trans_id' => $order['id'],
                                'invoice_id' => $order['id'],
                                'user_id' => $order['user_id'],
                                'amount' => $order['total_amount'],
                                'trans_id' => $transId,
                                'provider' => 'uzum',
                                'provider_time' => $create_time,
                                'create_time' => date('Y-m-d H:i:s', $create_time / 1000),
                            ]);

                        return response()->json([
                            'serviceId' => $serviceId,
                            'transId' => $transId,
                            'transTime' => $create_time,
                            'status' => 'CREATED',
                            'amount' => $amount,
                            'data' => [
                                'account' => "$order->id"
                            ]
                        ]);
                    } else {
                        return response()->json([
                            'serviceId' => $serviceId,
                            'transId' => $transId,
                            'transTime' => Carbon::parse(now())->timestamp * 1000,
                            'status' => 'FAILED',
                            'errorCode' => 10010,
                        ]);
                    }
                } else {
                    return response()->json([
                        'serviceId' => $serviceId,
                        'transId' => $transId,
                        'transTime' => Carbon::parse(now())->timestamp * 1000,
                        'status' => 'FAILED',
                        'errorCode' => 10011,
                    ]);
                }
            } else {
                return response()->json([
                    'serviceId' => $serviceId,
                    'transId' => $transId,
                    'transTime' => Carbon::parse(now())->timestamp * 1000,
                    'status' => 'FAILED',
                    'errorCode' => 10007,
                ]);
            }
        } else {
            return response()->json([
                'serviceId' => $serviceId,
                'transId' => $transId,
                'transTime' => Carbon::parse(now())->timestamp * 1000,
                'status' => 'FAILED',
                'errorCode' => 10006,
            ]);
        }
    }

    public function uzum_confirm(Request $request)
    {
        $serviceId = $request['serviceId'];
        $confirm_time = $request['timestamp'];
        $transId = $request['transId'];

        $my_serviceId = env('UZUM_SERVICE_ID');

        if ($serviceId === $my_serviceId) {
            $transaction = Transaction::query()
                ->where([
                    'provider' => 'uzum',
                    'trans_id', $transId
                ])->first();

            if (!is_null($transaction)) {
                if ($transaction['status'] !== 'COMPLETED') {
                    $transaction->update([
                        'status' => 'COMPLETED',
                        'perform_time' => date('Y-m-d H:i:s', $confirm_time / 1000),
                    ]);

                    $transaction->Invoice()->update([
                        'status' => 'prepaid',
                        'date' => date('Y-m-d')
                    ]);

                    return response()->json([
                        'serviceId' => $serviceId,
                        'transId' => $transId,
                        'status' => 'CONFIRMED',
                        'confirmTime' => $confirm_time,
                        'amount' => $transaction->amount,
                        'data' => [
                            'account' => "$transaction->invoice_id"
                        ]
                    ]);
                } else {
                    return response()->json([
                        'serviceId' => $serviceId,
                        'transId' => $transId,
                        'confirmTime' => Carbon::parse(now())->timestamp * 1000,
                        'status' => 'FAILED',
                        'errorCode' => 10016
                    ]);
                }
            } else {
                return response()->json([
                    'serviceId' => $serviceId,
                    'transId' => $transId,
                    'confirmTime' => Carbon::parse(now())->timestamp * 1000,
                    'status' => 'FAILED',
                    'errorCode' => 10014
                ]);
            }
        } else {
            return response()->json([
                'serviceId' => $serviceId,
                'transId' => $transId,
                'confirmTime' => Carbon::parse(now())->timestamp * 1000,
                'status' => 'FAILED',
                'errorCode' => 10006,
            ]);
        }
    }

    public function uzum_reverse(Request $request)
    {
        $serviceId = $request['serviceId'];
        $cancel_time = $request['timestamp'];
        $transId = $request['transId'];

        $my_serviceId = env('UZUM_SERVICE_ID');

        if ($serviceId === $my_serviceId) {
            $transaction = Transaction::query()
                ->where([
                    'provider' => 'uzum',
                    'trans_id', $transId
                ])->first();

            if (!is_null($transaction)) {
                $transaction->Invoice()->update([
                    'status' => 'not_paid'
                ]);

                $transaction->update([
                    'status' => 'CANCELED',
                    'cancel_time' => date('Y-m-d H:i:s', $cancel_time / 1000),
                ]);

                return response()->json([
                    'serviceId' => $serviceId,
                    'transId' => $transId,
                    'status' => 'REVERSED',
                    'reverseTime' => $cancel_time,
                    'amount' => $transaction->amount,
                    'data' => [
                        'account' => "$transaction->invoice_id"
                    ]
                ]);

            } else {
                return response()->json([
                    'serviceId' => $serviceId,
                    'transId' => $transId,
                    'reverseTime' => Carbon::parse(now())->timestamp * 1000,
                    'status' => 'FAILED',
                    'errorCode' => 10014
                ]);
            }

        } else {
            return response()->json([
                'serviceId' => $serviceId,
                'transId' => $transId,
                'reverseTime' => Carbon::parse(now())->timestamp * 1000,
                'status' => 'FAILED',
                'errorCode' => 10006,
            ]);
        }

    }

    public function uzum_status(Request $request)
    {
        $serviceId = $request['serviceId'];
        $transId = $request['transId'];

        $my_serviceId = env('UZUM_SERVICE_ID');

        $statuses = [
             'PREPARED' => 'CREATED',
             'COMPLETED' => 'CONFIRMED',
             'CANCELED' => 'REVERSED',
        ];

        if ($serviceId === $my_serviceId) {
            $transaction = Transaction::query()
                ->where([
                    'provider' => 'uzum',
                    'trans_id', $transId
                ])->first();

            if (!is_null($transaction)) {
                return response()->json([
                    'serviceId' => $serviceId,
                    'transId' => $transId,
                    'transTime' => !is_null($transaction['create_time']) ? strtotime($transaction['create_time']) * 1000 : 0,
                    'confirmTime' => !is_null($transaction['perform_time']) ? strtotime($transaction['perform_time']) * 1000 : 0,
                    'reverseTime' => !is_null($transaction['cancel_time']) ? strtotime($transaction['cancel_time']) * 1000 : 0,
                    'status' => $statuses[$transaction['status']],
                ]);

            } else {
                return response()->json([
                    'serviceId' => $serviceId,
                    'transId' => $transId,
                    'transTime' => null,
                    'confirmTime' => null,
                    'reverseTime' => null,
                    'status' => 'FAILED',
                    'errorCode' => 10014
                ]);
            }

        } else {
            return response()->json([
                'serviceId' => $serviceId,
                'transId' => $transId,
                'transTime' => null,
                'confirmTime' => null,
                'reverseTime' => null,
                'status' => 'FAILED',
                'errorCode' => 10006,
            ]);
        }
    }

    public function oson_app_start(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
        ]);

        $order = Invoice::query()->find($request['order_id']);

        if (!is_null($order)) {

            $merchant_id = env('OSON_MERCHANT_ID');
            $callback = env('OSON_CALLBACK');
            $order_id = $request['order_id'];
            $amount = number_format($order['total_amount'], 2, '.', '');
            $url = 'https://pay.oson.uz/payment/get/' . base64_encode("m=$merchant_id&a=$amount&ac=$order_id&c=$callback&cr=UZS&l=uz");

            return response()->json([
                'status' => 'success',
                'data' => [
                    'url' => $url
                ]
            ]);

        } else {

            return response()->json([
                "success" => false,
                'message'=>'Oops, something went wrong!'
            ]);

        }

    }

    public function oson_start(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
        ]);

        $order = Invoice::query()->find($request['order_id']);

        if (!is_null($order)) {

            $uid = Str::uuid();

            Transaction::query()->create([
                'trans_id' => $uid,
                'invoice_id' => $order['id'],
                'user_id' => $order['user_id'],
                'merchant_trans_id' => $order['id'],

                'amount' => $order['total_amount'],
                'provider' => 'oson',
                'create_time' => date('Y-m-d H:i:s'),
            ]);

            $client = new \GuzzleHttp\Client();

            try {
                $response = $client->post('https://api.oson.uz/api/invoice/create', [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'token' => config('services.oson.token')

                    ],
                    'json' => [
                        "merchant_id" => config('services.oson.merchant_id'),
                        "transaction_id" => $uid,
                        "user_account" => "user@mail.com",
                        "amount" => $order['total_amount'],
                        "currency" => "UZS",
                        "comment" => "Оплата заказа №51",
                        "return_url" => "http://dukan.uz",
                        "lifetime" => 60*24,
                        "lang" => "uz"
                    ]
                ]);
            } catch (GuzzleException $exception) {
                return response()->json([
                    "success" => false,
                    'message' => $exception->getMessage() // Xatolikni ko‘rsatish
                ]);
            }

            $response = json_decode($response->getBody()->getContents(), true);

            $url = $response['pay_url'];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'url' => $url
                ]
            ]);

        } else {

            return response()->json([
                "success" => false,
                'message'=>'Oops, something went wrong!'
            ]);

        }
    }

    public function oson_callback(Request $request)
    {
        if (!isset($request['signature']) || is_null($request['signature'])) {
            return response()->json([
                "success" => false,
                "state" => 3,
                "message" => "signature not found!"
            ]);
        }

        $param = "$request[transaction_id]:$request[bill_id]:$request[status]";
        $hash = hash('sha256', env('OSON_AUTH_TOKEN') . ':' . env('OSON_MERCHANT_ID'));

        $sign = hash('sha256', $hash . ':' . $param);

        if ($sign !== $request['signature']) {
            return response()->json([
                "success" => false,
                "state" => 3,
                "message" => "wrong signature!"
            ]);
        }

        $transaction = Transaction::query()
            ->where(['trans_id' => $request['transaction_id']])
            ->first();

        if (is_null($transaction)) {
            return response()->json([
                'success' => false,
                'state' => 3,
                "message" => "transaction not found!"
            ]);
        }

        if ($request['status'] === "PAY") $args = ['status' => 'COMPLETED', 'perform_time' => date('Y-m-d H:i:s')];
        if ($request['status'] === "DECLINED") $args = ['status' => 'CANCELED', 'cancel_time' => date('Y-m-d H:i:s')];
        if ($request['status'] === "PAY_ERROR") $args = ['status' => 'ERROR', 'cancel_time' => date('Y-m-d H:i:s')];

        $transaction->update($args);

        return response()->json([
            'success' => true,
            'state' => 1
        ]);
    }

    public function oson_check(Request $request)
    {
        $account = $request['account'];
        $amount  = $request['amount'];

        $order = Invoice::query()->find($account);

        if (!is_null($order)) {
            if ($order['total_amount'] == $amount) {
                if ($order['status'] == 'not_paid') {
                    return response()->json([
                        "success" => true,
                        "state" => 1
                    ]);
                } else {
                    return response()->json([
                        "success" => false,
                        "state" => -1
                    ]);
                }

            } else {
                return response()->json([
                    "success" => false,
                    "state" => 5
                ]);
            }
        } else {
            return response()->json([
                "success" => false,
                "state" => 3
            ]);
        }
    }

    public function oson_pay(Request $request)
    {
        $account = $request['account'];
        $merchant_id = $request['merchant_id'];
        $agent_transaction_id = $request['agent_transaction_id'];
        $params = $request['params'];
        $amount  = $request['amount'];

        $order = Invoice::query()->find($account);

        if (!is_null($order)) {
            if ($order['total_amount'] == $amount) {
                if ($order['status'] == 'not_paid') {

                    $transaction = Transaction::query()->where([
                        'provider' => 'oson',
                        'trans_id' => $agent_transaction_id,
                        'status' => 'PREPARED'
                    ])->first();

                    if (is_null($transaction)) {
                        $transaction = Transaction::query()->create([
                            'trans_id' => $agent_transaction_id,
                            'amount' => $amount,
                            'invoice_id' => $order['id'],
                            'merchant_trans_id' => $order['id'],
                            'user_id' => $order['user_id'],
                            'status' => 'COMPLETED',
                            'state' => '1',
                            'provider' => 'oson',
                            'create_time' => date('Y-m-d H:i:s'),
                            'perform_time' => date('Y-m-d H:i:s'),
                        ]);

                        return response()->json([
                            'transactionId' => $transaction['trans_id'],
                            'state' => 1
                        ]);

                    } else {
                        return response()->json([
                            "transaction_id" => $transaction['trans_id'],
                            "state" => -1
                        ]);
                    }
                } else {
                    return response()->json([
                        "success" => false,
                        "state" => -1
                    ]);
                }

            } else {
                return response()->json([
                    "success" => false,
                    "state" => 5
                ]);
            }
        } else {
            return response()->json([
                "success" => false,
                "state" => 3
            ]);
        }
//        base64_encode('m=1474&cr=UZS&a=1000.00&ac=1&l=uz&c=https://oson.uz');
    }

    public function oson_check_status(Request $request)
    {
        $transaction = Transaction::query()
            ->where('trans_id', $request['transaction_id'])
            ->first();

        return response()->json([
            'state' => $transaction ? $transaction['state'] : -1,
        ]);
    }
}
