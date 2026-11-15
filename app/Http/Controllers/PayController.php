<?php

namespace App\Http\Controllers;

use App\Models\OnlinePayment;
use App\Models\Sale;
use App\Services\Payments\SslCommerz;
use Illuminate\Http\Request;
use RuntimeException;

class PayController extends Controller
{
    public function __construct(private SslCommerz $gateway) {}

    private function sale(string $token): Sale
    {
        abort_unless($this->gateway->enabled(), 404);

        return Sale::where('pay_token', $token)->where('status', 'confirmed')->firstOrFail();
    }

    public function show(string $token)
    {
        $sale = $this->sale($token)->load('items.stockItem.product', 'customer');

        return view('pay.show', ['sale' => $sale, 'due' => $sale->dueAmount(), 'token' => $token]);
    }

    public function start(Request $request, string $token)
    {
        $sale = $this->sale($token);
        $payer = $request->validate([
            'name' => ['required', 'string', 'max:160'], 'email' => ['required', 'email', 'max:190'], 'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s()]{6,32}$/'],
        ]);

        try {
            $url = $this->gateway->start($sale, $payer);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['pay' => $e->getMessage()]);
        }

        return redirect()->away($url);
    }

    /** The customer's browser returns here from the gateway. Whatever it says is checked with the gateway before anything is recorded. */
    public function callback(Request $request, string $outcome)
    {
        abort_unless($this->gateway->enabled(), 404);

        $in = $request->all();
        if (empty($in['status'])) {
            $in['status'] = ['success' => 'VALID', 'fail' => 'FAILED', 'cancel' => 'CANCELLED'][$outcome];
        }
        $result = $this->gateway->finish($in);
        $token = $result['payment']?->sale?->pay_token;
        abort_unless($token, 404);

        return redirect()->route('pay.show', $token)->with('pay_result', $result['status']);
    }

    /** Server-to-server notice from the gateway, in case the customer closed the browser before coming back. */
    public function ipn(Request $request)
    {
        abort_unless($this->gateway->enabled(), 404);
        $this->gateway->finish($request->all());

        return response('OK');
    }
}
