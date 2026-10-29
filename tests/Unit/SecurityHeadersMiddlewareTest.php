<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddlewareTest extends TestCase
{
    public function test_csp_header_allows_video_and_map_embeds_in_frame_src(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = Request::create('/p/test-microsite', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);

        $this->assertStringContainsString("frame-src", $csp);
        $this->assertStringContainsString("https://www.youtube.com", $csp);
        $this->assertStringContainsString("https://www.youtube-nocookie.com", $csp);
        $this->assertStringContainsString("https://player.vimeo.com", $csp);
        $this->assertStringContainsString("https://www.google.com", $csp);
    }
}
