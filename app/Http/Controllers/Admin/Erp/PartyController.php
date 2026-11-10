<?php

namespace App\Http\Controllers\Admin\Erp;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    public function index(Request $request, string $kind)
    {
        abort_unless(isset(Party::KINDS[$kind]), 404);

        $parties = Party::kind($kind)
            ->when($request->string('q')->trim()->value(), fn ($q, $t) => $q->where(fn ($w) => $w->where('name', 'like', "%{$t}%")->orWhere('phone', 'like', "%{$t}%")->orWhere('email', 'like', "%{$t}%")))
            ->orderBy('name')->paginate(30)->withQueryString();

        return view('admin.erp.parties.index', ['kind' => $kind, 'parties' => $parties]);
    }

    public function store(Request $request, string $kind)
    {
        abort_unless(isset(Party::KINDS[$kind]), 404);
        $party = Party::create(['kind' => $kind] + $this->data($request));
        activity('admin')->causedBy($request->user())->performedOn($party)->log($kind.'.created');

        return redirect()->route('admin.parties.index', $kind)->with('status', Party::KINDS[$kind].' added.');
    }

    public function edit(Party $party)
    {
        $balance = $party->kind === 'customer'
            ? Sale::where('customer_id', $party->id)->where('status', 'confirmed')->get()->sum(fn ($s) => $s->balance())
            : Purchase::where('supplier_id', $party->id)->where('status', 'received')->get()->sum(fn ($p) => $p->balance());

        return view('admin.erp.parties.edit', ['party' => $party, 'balance' => $balance]);
    }

    public function update(Request $request, Party $party)
    {
        $party->update($this->data($request));
        activity('admin')->causedBy($request->user())->performedOn($party)->log($party->kind.'.updated');

        return redirect()->route('admin.parties.index', $party->kind)->with('status', 'Saved.');
    }

    public function destroy(Request $request, Party $party)
    {
        activity('admin')->causedBy($request->user())->performedOn($party)->log($party->kind.'.deleted');
        $kind = $party->kind;
        $party->delete();   // soft delete: old invoices keep showing the name

        return redirect()->route('admin.parties.index', $kind)->with('status', 'Removed.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'], 'phone' => ['nullable', 'string', 'max:32'], 'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:1000'], 'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
