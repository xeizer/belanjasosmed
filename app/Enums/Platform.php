<?php

namespace App\Enums;

enum Platform: string
{
    case Shopee = 'shopee';
    case TiktokShop = 'tiktok_shop';

    /**
     * Get the human readable label for the platform.
     */
    public function label(): string
    {
        return match ($this) {
            self::Shopee => 'Shopee',
            self::TiktokShop => 'TikTok Shop',
        };
    }

    /**
     * Get the Tailwind classes used for the platform's buy button.
     */
    public function buttonClasses(): string
    {
        return match ($this) {
            self::Shopee => 'bg-shopee text-white hover:bg-shopee-dark',
            self::TiktokShop => 'bg-tiktok text-white hover:bg-black',
        };
    }
}
