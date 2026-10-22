<?php

namespace Tests\Unit;

use App\Services\Mermaid\MermaidRendererService;
use Tests\TestCase;

class MermaidRendererTest extends TestCase
{
    public function test_it_preserves_html_without_mermaid()
    {
        $service = new MermaidRendererService();
        $html = '<p>Este es un texto sin diagramas.</p><div>Contenido normal</div>';
        $result = $service->renderMermaidBlocksInHtml($html);

        $this->assertEquals($html, $result);
    }

    public function test_it_replaces_mermaid_code_block_with_rendered_png()
    {
        $service = new MermaidRendererService();
        $html = '<p>Inicio</p><pre><code class="language-mermaid">graph TD' . "\n" . 'A[Inicio] --> B[Fin]</code></pre><p>Fin</p>';
        $result = $service->renderMermaidBlocksInHtml($html);

        $this->assertStringContainsString('class="mermaid-diagram"', $result);
        $this->assertStringContainsString('src="data:image/png;base64,', $result);
        $this->assertStringNotContainsString('<pre><code class="language-mermaid">', $result);
    }

    public function test_it_handles_empty_mermaid_block_safely()
    {
        $service = new MermaidRendererService();
        $html = '<pre><code class="language-mermaid">   </code></pre>';
        $result = $service->renderMermaidBlocksInHtml($html);

        $this->assertEquals($html, $result);
    }
}

