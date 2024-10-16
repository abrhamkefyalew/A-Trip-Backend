<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});





Route::get('organization_user/invoices/pay_invoices/{valuePayment}', function ($valuePayment) {
    return view('boa_pay', ['valuePayment' => $valuePayment]);
})->name('pay.with.boa');


// Route::get('organization_user/invoices/pay_invoices/{valuePayment}', fn ($valuePayment) => view(
//     'boa_pay',
//     [
//         'valuePayment' => $valuePayment,
//     ]
// ))->name('pay.with.boa');