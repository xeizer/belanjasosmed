<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display the public product page with its affiliate links grouped by platform.
     *
     * Inactive products are hidden (404) from visitors but can be previewed by users
     * who may manage the product.
     */
    public function show(Request $request, Product $product): View
    {
        $canManageProduct = (bool) $request->user()?->can('update', $product);

        abort_unless($product->is_active || $canManageProduct, 404);

        $product->load('affiliateLinks');

        return view('products.show', [
            'product' => $product,
            'canManageProduct' => $canManageProduct,
            'isInactivePreview' => ! $product->is_active,
            'linksByPlatform' => $product->affiliateLinks->groupBy(
                fn ($affiliateLink) => $affiliateLink->platform->value
            ),
        ]);
    }
}
