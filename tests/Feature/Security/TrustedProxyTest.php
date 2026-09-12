<?php

namespace Tests\Feature\Security;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    protected function tearDown(): void
    {
        TrustProxies::flushState();
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);

        parent::tearDown();
    }

    public function test_forwarded_headers_are_not_trusted_without_explicit_proxy_configuration(): void
    {
        config(['trustedproxy.proxies' => null]);
        $request = $this->forwardedHttpsRequest();

        $isSecure = (new TrustProxies)->handle(
            $request,
            fn (Request $request): bool => $request->isSecure(),
        );

        $this->assertFalse($isSecure);
    }

    public function test_forwarded_headers_are_trusted_for_configured_proxy(): void
    {
        config(['trustedproxy.proxies' => '10.0.0.10']);
        $request = $this->forwardedHttpsRequest();

        $isSecure = (new TrustProxies)->handle(
            $request,
            fn (Request $request): bool => $request->isSecure(),
        );

        $this->assertTrue($isSecure);
    }

    private function forwardedHttpsRequest(): Request
    {
        return Request::create(
            uri: 'http://tasks.test/dashboard',
            server: [
                'REMOTE_ADDR' => '10.0.0.10',
                'HTTP_X_FORWARDED_PROTO' => 'https',
            ],
        );
    }
}
