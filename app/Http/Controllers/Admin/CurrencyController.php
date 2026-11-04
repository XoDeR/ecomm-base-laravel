<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CurrencyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/currencies', [
            'currencies' => Currency::orderByDesc('is_active')->orderBy('code')->get(['id', 'code', 'symbol', 'rate', 'is_active'])->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:3', 'alpha', Rule::unique('currencies', 'code')],
            'symbol' => ['required', 'string', 'max:10'],
            'rate' => ['required', 'numeric', 'gt:0', 'max:999999'],
        ]);

        Currency::create([...$data, 'code' => strtoupper($data['code']), 'is_active' => false]);

        $this->toast('Currency added.');

        return back();
    }

    public function update(Request $request, Currency $currency): RedirectResponse
    {
        $data = $request->validate([
            'symbol' => ['required', 'string', 'max:10'],
            'rate' => ['required', 'numeric', 'gt:0', 'max:999999'],
        ]);

        $currency->update($data);

        $this->toast('Currency updated.');

        return back();
    }

    /**
     * Make the currency the one shown in the store.
     */
    public function activate(Currency $currency): RedirectResponse
    {
        $currency->activate();

        $this->toast("{$currency->code} is now the store currency.");

        return back();
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        if ($currency->is_active) {
            $this->toast('The store currency cannot be deleted. Activate another currency first.', 'error');

            return back();
        }

        $currency->delete();

        $this->toast('Currency deleted.');

        return back();
    }
}
