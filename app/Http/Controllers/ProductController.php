<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display the public product page with its affiliate links grouped by platform.
     */
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load('affiliateLinks');

        return view('products.show', [
            'product' => $product,
            'linksByPlatform' => $product->affiliateLinks->groupBy(
                fn ($affiliateLink) => $affiliateLink->platform->value
            ),
        ]);
    }
}
