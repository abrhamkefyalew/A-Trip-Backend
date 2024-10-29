<?php

use App\Models\Invoice;
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





// Route::get('web/v1/organization_user/invoices/pay_invoices/{valuePayment}', function (Invoice $invoice) {
//     return view('boa_pay_organization_using_invoice_model_instance', ['invoice' => $invoice]);
// })->name('pay.with.boa');
//
Route::get('web/v1/organization_user/invoices/pay_invoices/{invoice}', fn (Invoice $invoice) => view(
    'boa_pay_organization_using_invoice_model_instance',
    [
        'invoice' => $invoice,
    ]
))->name('pay.with.boa');