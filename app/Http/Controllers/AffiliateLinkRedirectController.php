<?php

namespace App\Http\Controllers;

use App\Models\AffiliateLink;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class AffiliateLinkRedirectController extends Controller
{
    /**
     * Count the click and redirect the visitor to the affiliate link.
     *
     * This GET route intentionally changes state (click counter); the exception
     * to the "no state change over GET" rule was approved for this project.
     */
    public function __invoke(Product $product, AffiliateLink $affiliateLink): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $affiliateLink->increment('click_count');

        return redirect()->away($affiliateLink->url);
    }
}
