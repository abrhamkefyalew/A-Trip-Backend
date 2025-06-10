<?php

use App\Models\Invoice;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\OrganizationUser\InvoiceController as InvoiceForOrganizationController;

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








Route::middleware('web')->group(function () {
    // FAYDA TEST ROUTE
    // Route::get('/fayda_test_open_route/fayda/redirect', [InvoiceForOrganizationController::class, 'redirect']);
    Route::get('/fayda_test_open_route/fayda/redirect', [InvoiceForOrganizationController::class, 'home']);
                //
                //
                // Callback
                //      See Web.php
                //              for substitute
                //
    // fayda callback
    //      MUST BE in here with out any prefixes 
    //      BECAUSE Fayda requires the callback to be JUST like below without any prefixes, the Callback URL just exactly as below
    //          i.e. 'http://localhost:3000/callback'
    //
    Route::get('/callback', [InvoiceForOrganizationController::class, 'callback']);
});