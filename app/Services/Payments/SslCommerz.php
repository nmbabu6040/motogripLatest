<?php

namespace App\Services\Payments;

use App\Models\OnlinePayment;
use App\Models\Sale;
use App\Services\Erp\ErpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * SSLCommerz hosted checkout. The customer pays on the gateway's own page (cards, bKash, Nagad and more), so card and wallet details never
 * touch this site. A payment is recorded only after the gateway's validation API confirms it; what the browser or an IPN call says is not trusted.
 */
class SslCommerz
{
    public function enabled(): bool
    {
        return (bool) config('motogrip.payments.enabled')
            && filled(config('motogrip.payments.sslcommerz.store_id'))
            && filled(config('motogrip.payments.sslcommerz.store_password'));
    }

    private function base(): string
    {
        return config('motogrip.payments.sslcommerz.sandbox', true) ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com';
    }

    private function credentials(): array
    {
        return ['store_id' => config('motogrip.payments.sslcommerz.store_id'), 'store_passwd' => config('motogrip.payments.sslcommerz.store_password')];
    }

    /**
     * Opens a payment for the full balance of the invoice and returns the gateway page the customer must be sent to.
     *
     * @param array{name:string,email:string,phone:string} $payer
     */
    public function start(Sale $sale, array $payer): string
    {
        $due = $sale->dueAmount();
        if ($sale->isVoid() || $due <= 0) {
            throw new RuntimeException('There is nothing to pay on this invoice.');
        }

        $payment = OnlinePayment::create([
            'sale_id' => $sale->id, 'tran_id' => 'MG'.$sale->id.'-'.Str::upper(Str::random(12)), 'amount' => $due, 'currency' => 'BDT', 'status' => 'initiated',
            'payer_name' => $payer['name'], 'payer_email' => $payer['email'], 'payer_phone' => $payer['phone'],
        ]);

        $response = Http::asForm()->timeout(25)->post($this->base().'/gwprocess/v4/api.php', $this->credentials() + [
            'total_amount' => number_format($due, 2, '.', ''), 'currency' => 'BDT', 'tran_id' => $payment->tran_id,
            'success_url' => route('pay.callback', 'success'), 'fail_url' => route('pay.callback', 'fail'), 'cancel_url' => route('pay.callback', 'cancel'), 'ipn_url' => route('pay.ipn'),
            'cus_name' => $payer['name'], 'cus_email' => $payer['email'], 'cus_phone' => $payer['phone'],
            // The gateway insists on an address; the invoice customer's own address is used when there is one.
            'cus_add1' => $sale->customer?->address ?: 'N/A', 'cus_city' => 'N/A', 'cus_country' => 'Bangladesh',
            'shipping_method' => 'NO', 'product_name' => 'Invoice '.$sale->number(), 'product_category' => 'Goods', 'product_profile' => 'general',
        ]);
        $json = $response->json() ?? [];

        if (! $response->successful() || ($json['status'] ?? '') !== 'SUCCESS' || empty($json['GatewayPageURL'])) {
            $payment->update(['status' => 'failed', 'note' => mb_substr((string) ($json['failedreason'] ?? 'The gateway did not open a payment page.'), 0, 250)]);
            throw new RuntimeException('The payment page could not be opened. Please try again in a few minutes.');
        }

        return (string) $json['GatewayPageURL'];
    }

    /**
     * Handles a reply from the gateway (the customer's browser coming back, or the gateway's own IPN call).
     *
     * @return array{status: string, payment: ?OnlinePayment}  status is one of paid, failed, cancelled, review, unknown
     */
    public function finish(array $in): array
    {
        $tran = (string) ($in['tran_id'] ?? '');
        $payment = $tran !== '' ? OnlinePayment::where('tran_id', $tran)->first() : null;
        if (! $payment) {
            return ['status' => 'unknown', 'payment' => null];
        }
        if (in_array($payment->status, ['paid', 'review'], true)) {
            return ['status' => $payment->status, 'payment' => $payment];   // already dealt with: the same reply twice changes nothing
        }

        $said = strtoupper((string) ($in['status'] ?? ''));
        if (in_array($said, ['FAILED', 'CANCELLED'], true)) {
            if ($payment->status === 'initiated') {
                $payment->update(['status' => $said === 'FAILED' ? 'failed' : 'cancelled']);
            }

            return ['status' => $payment->status, 'payment' => $payment];
        }
        if (! in_array($said, ['VALID', 'VALIDATED'], true) || empty($in['val_id'])) {
            return ['status' => $payment->status, 'payment' => $payment];
        }

        // Never trust the browser: ask the gateway whether this payment is real, for this transaction and this amount.
        $check = Http::timeout(25)->get($this->base().'/validator/api/validationserverAPI.php', $this->credentials() + ['val_id' => $in['val_id'], 'format' => 'json'])->json() ?? [];
        $real = in_array($check['status'] ?? '', ['VALID', 'VALIDATED'], true)
            && ($check['tran_id'] ?? '') === $payment->tran_id
            && ($check['currency_type'] ?? 'BDT') === 'BDT'
            && abs((float) ($check['amount'] ?? 0) - (float) $payment->amount) < 0.01;

        if (! $real) {
            $payment->update(['status' => 'failed', 'val_id' => (string) $in['val_id'], 'note' => 'The gateway did not confirm this payment.']);

            return ['status' => 'failed', 'payment' => $payment];
        }

        return DB::transaction(function () use ($payment, $check, $in) {
            $p = OnlinePayment::lockForUpdate()->findOrFail($payment->id);
            if (in_array($p->status, ['paid', 'review'], true)) {
                return ['status' => $p->status, 'payment' => $p];
            }
            $sale = Sale::lockForUpdate()->findOrFail($p->sale_id);
            $details = ['val_id' => (string) $in['val_id'], 'bank_tran_id' => $check['bank_tran_id'] ?? null];

            // risk_level 1 means the gateway flags the payment as risky: keep the money aside until a person has looked at it.
            if ((string) ($check['risk_level'] ?? '0') === '1') {
                $p->update($details + ['status' => 'review', 'note' => 'The gateway marked this payment as risky. Check it in your gateway account before you accept it.']);

                return ['status' => 'review', 'payment' => $p];
            }

            try {
                app(ErpService::class)->addPayment($sale, (float) $p->amount, 'online', today()->toDateString(), $p->tran_id, 'Online payment (SSLCommerz)', null);
            } catch (ValidationException) {
                $p->update($details + ['status' => 'review', 'note' => 'Money was received but could not be applied because the invoice balance changed. Record it by hand or refund it.']);

                return ['status' => 'review', 'payment' => $p];
            }
            $p->update($details + ['status' => 'paid', 'paid_at' => now(), 'note' => null]);

            return ['status' => 'paid', 'payment' => $p];
        });
    }
}
