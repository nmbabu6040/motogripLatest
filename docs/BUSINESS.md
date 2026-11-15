# Stock, sales, purchases and invoices

Everything is under **Admin → Stock and accounts**.

## First time
1. Admin → Products: add your products with their pack sizes (for example `1 L, 4 L, 20 L, 208 L`).
2. Admin → Stock → **Add products to stock list**. Each product and pack size becomes one stock item. Set the **sale price** and **reorder level** there.
3. Admin → Suppliers / Customers: add the people you buy from and sell to.
4. Put opening stock in with **Stock → item → Adjust stock** (reason: "Opening stock"), or record a purchase.
5. Admin → Settings → **Business and invoices**: currency symbol, default VAT %, VAT/BIN number and the note printed on invoices.

## Daily work
| Task | Where | What happens |
|---|---|---|
| Buy goods | Purchases → New purchase | Stock goes up; average cost is updated |
| Sell goods | Sales and invoices → New sale | Stock goes down; invoice is created (print it) |
| Customer returns goods | Open the invoice → Return items | Stock goes up; the customer's balance goes down |
| Send goods back to supplier | Open the purchase → Return items to supplier | Stock goes down; what you owe goes down |
| Deliver goods | Open the invoice → Create challan | Printable delivery note, partial deliveries allowed |
| Receive or make a payment | Open the invoice or purchase → payment form | Balance due updates |
| Mistake in an invoice or purchase | **Void** (only if no payment, return or challan is attached) | Stock is put back |

## Rules the system enforces
- Stock can never go below zero. A sale that needs more than you have is refused.
- Stock is the sum of its history (Stock → item → History). Nothing edits a stock number directly.
- A return can never be bigger than what was sold or bought, and never bigger than what is left to return.
- A payment can never be bigger than the balance due.
- Voided documents stay on file but do not count in reports.

## Dashboard figures
- **Estimated profit** = item price minus the average purchase cost, on invoices of the month, minus returned items. It ignores discounts, tax and running costs, so treat it as a guide, not an accounting result.
- **Customers owe you / You owe suppliers** = totals minus returns minus payments.
- **Stock value** = quantity in stock times average cost.

## Recommended products (Amazon, AliExpress)
Admin → **Recommended links**. Links are shown exactly as the store gave them; visitors go straight to the store, and the site only counts clicks per day. Amazon requires its own disclosure wording (Settings → Recommended products) and does not allow showing prices without its own tools, so no prices are shown.


## Editing and deleting
| Document | Edit | Delete |
|---|---|---|
| Invoice / purchase | Date, customer or supplier and notes any time. Items until a return or challan (invoice) or a return to the supplier (purchase) exists. Stock is corrected automatically and every change stays in the stock history. | **Void** (needs no payment, return or challan). A payment entered by mistake can be removed first. |
| Sale return / purchase return | Date and reason | Delete: stock is corrected again. A sale return cannot be deleted if the goods have already been sold again. |
| Challan | All fields | Delete: its items become "not yet delivered" again |
| Stock item | Pack size, prices, reorder level | Only while it has no stock history and is on no document |
| Newsletter subscriber | Confirm (only with their consent), resend the confirmation e-mail, unsubscribe | Delete permanently |

## Online payment of invoices (SSLCommerz)
Customers can pay an invoice on SSLCommerz's own page (cards, bKash, Nagad and more). Card and wallet details are entered there and never reach this site.

Switch it on:
1. Open an SSLCommerz merchant account and ask for your **Store ID** and **Store Password**. Start in their sandbox.
2. Put these lines in `.env`:
   ```
   MOTOGRIP_ONLINE_PAYMENTS=true
   SSLCOMMERZ_STORE_ID=your_store_id
   SSLCOMMERZ_STORE_PASSWORD=your_store_password
   SSLCOMMERZ_SANDBOX=true
   ```
3. Run `php artisan config:clear`.
4. Open an invoice → **Create online payment link** → send the link to the customer.
5. The gateway must be able to reach your site. On your own computer (`127.0.0.1`) the customer's browser comes back fine, but the gateway's server-to-server notice (IPN) cannot reach you; use a public address or a tunnel while testing.
6. When the sandbox works, set `SSLCOMMERZ_SANDBOX=false` with your live credentials.

How it stays safe: a payment is recorded only after SSLCommerz's validation service confirms the same transaction and amount; the browser's word is never enough. The same reply twice records once. Payments flagged risky, or arriving after the invoice balance changed, are held under **Online payment → Review** on the invoice for a person to decide.
