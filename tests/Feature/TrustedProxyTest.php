<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    private const string CLOUDFLARE_IP = '173.245.48.10';

    private const string CLOUDFLARE_IPV6 = '2606:4700::1111';

    private const string VISITOR_IP = '203.0.113.7';

    /**
     * Static LAN IP of the production server, used by cloudflared to reach Nginx.
     */
    private const string TUNNEL_IP = '15.15.15.102';

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/request-info', fn (Request $request): array => [
            'ip' => $request->ip(),
            'secure' => $request->isSecure(),
            'url' => url('/cari'),
        ])->middleware('web');
    }

    /**
     * Get the host (and port) the test requests are sent to, taken from APP_URL.
     */
    private function authority(): string
    {
        return Str::after(config('app.url'), '://');
    }

    public function test_request_from_cloudflare_uses_forwarded_scheme_and_visitor_ip(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => self::CLOUDFLARE_IP])
            ->withHeaders([
                'X-Forwarded-For' => self::VISITOR_IP,
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/_test/request-info')
            ->assertExactJson([
                'ip' => self::VISITOR_IP,
                'secure' => true,
                'url' => 'https://'.$this->authority().'/cari',
            ]);
    }

    public function test_request_from_cloudflare_ipv6_is_trusted(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => self::CLOUDFLARE_IPV6])
            ->withHeaders([
                'X-Forwarded-For' => self::VISITOR_IP,
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/_test/request-info')
            ->assertJson(['ip' => self::VISITOR_IP, 'secure' => true]);
    }

    public function test_request_through_cloudflare_tunnel_uses_visitor_ip_and_https(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => self::TUNNEL_IP])
            ->withHeaders([
                'X-Forwarded-For' => self::VISITOR_IP,
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/_test/request-info')
            ->assertExactJson([
                'ip' => self::VISITOR_IP,
                'secure' => true,
                'url' => 'https://'.$this->authority().'/cari',
            ]);
    }

    public function test_spoofed_forwarded_for_through_tunnel_resolves_to_real_visitor(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => self::TUNNEL_IP])
            ->withHeaders([
                'X-Forwarded-For' => '10.9.8.7, '.self::VISITOR_IP,
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/_test/request-info')
            ->assertJson(['ip' => self::VISITOR_IP]);
    }

    public function test_forwarded_headers_from_untrusted_ip_are_ignored(): void
    {

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->withHeaders([
                'X-Forwarded-For' => self::VISITOR_IP,
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/_test/request-info')
            ->assertExactJson([
                'ip' => '198.51.100.20',
                'secure' => false,
                'url' => 'http://'.$this->authority().'/cari',
            ]);
    }

    public function test_forwarded_host_is_not_trusted_even_from_cloudflare(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => self::CLOUDFLARE_IP])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'evil.example',
            ])
            ->get('/_test/request-info')
            ->assertJson(['url' => 'https://'.$this->authority().'/cari']);
    }

    public function test_public_page_links_use_https_behind_cloudflare(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => self::TUNNEL_IP])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/')
            ->assertOk()
            ->assertSee('action="https://'.$this->authority().'/cari"', false)
            ->assertDontSee('"http://'.$this->authority(), false);
    }

    public function test_livewire_update_uri_uses_https_on_admin_login_behind_cloudflare(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => self::TUNNEL_IP])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/admin/login')
            ->assertOk()
            ->assertSee('data-update-uri="https://'.$this->authority().'/livewire-', false)
            ->assertDontSee('"http://'.$this->authority(), false);
    }
}
