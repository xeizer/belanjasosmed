<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductSearchController extends Controller
{
    /**
     * Redirect a product code lookup to the matching public product page.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('search', [
            'kode' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ], [
            'kode.required' => 'Masukkan kode produk.',
            'kode.integer' => 'Kode produk harus berupa angka.',
            'kode.min' => 'Kode produk tidak valid.',
            'kode.max' => 'Kode produk tidak valid.',
        ]);

        $code = (int) $validated['kode'];

        if (! Product::active()->where('code', $code)->exists()) {
            return redirect()
                ->route('home')
                ->withInput()
                ->withErrors(['kode' => "Produk dengan kode {$code} tidak ditemukan."], 'search');
        }

        return redirect()->route('products.show', $code);
    }
}
