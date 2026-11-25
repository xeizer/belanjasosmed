<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaviconTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_admin_pages_link_the_favicons(): void
    {
        foreach (['/', '/admin/login', '/product/999'] as $url) {
            $this->get($url)
                ->assertSee('<link rel="icon" href="'.asset('favicon.svg').'" type="image/svg+xml">', false)
                ->assertSee(asset('favicon.ico'), false)
                ->assertSee(asset('apple-touch-icon.png'), false)
                ->assertSee(asset('site.webmanifest'), false);
        }
    }

    public function test_icon_files_exist_and_are_not_empty(): void
    {
        foreach (['favicon.ico', 'favicon.svg', 'apple-touch-icon.png', 'icon-192.png', 'icon-512.png', 'site.webmanifest'] as $file) {
            $path = public_path($file);

            $this->assertFileExists($path);
            $this->assertGreaterThan(0, filesize($path), "{$file} is empty");
        }

        $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('#ee4d2d', $manifest['theme_color']);
        $this->assertCount(2, $manifest['icons']);
    }
}
