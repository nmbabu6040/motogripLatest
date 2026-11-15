<?php

namespace App\Http\Controllers\Admin\Erp;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\Erp\ErpService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /** Removes a payment that was recorded by mistake. The person needs the permission of the document the payment belongs to. */
    public function destroy(Request $request, Payment $payment, ErpService $erp)
    {
        $permission = match ($payment->payable_type) { Sale::class => 'sales.manage', Purchase::class => 'purchases.manage', default => null };
        abort_unless($permission && $request->user()->can($permission), 403);

        $payable = $payment->payable;
        $erp->deletePayment($payment);
        activity('admin')->causedBy($request->user())->performedOn($payable)->withProperties(['amount' => (string) $payment->amount, 'method' => $payment->method])->log('payment.deleted');

        return back()->with('status', 'Payment removed.');
    }
}
