<?php

namespace Tests\Unit;

use App\Models\Microsite;
use App\Models\MicrositeImage;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class MicrositeImageTest extends TestCase
{
    public function test_formatted_size_accessor(): void
    {
        $image1 = new MicrositeImage(['file_size' => 500]);
        $this->assertEquals('500 B', $image1->formatted_size);

        $image2 = new MicrositeImage(['file_size' => 1024 * 250]);
        $this->assertEquals('250 KB', $image2->formatted_size);

        $image3 = new MicrositeImage(['file_size' => 1024 * 1024 * 3.5]);
        $this->assertEquals('3.5 MB', $image3->formatted_size);
    }

    public function test_html_snippet_accessor(): void
    {
        $image = new MicrositeImage([
            'file_name' => 'foto.jpg',
            'file_path' => 'microsites/1/images/foto.jpg',
            'title' => 'Foto Panorámica',
        ]);

        $snippet = $image->html_snippet;

        $this->assertStringContainsString('src="', $snippet);
        $this->assertStringContainsString('alt="Foto Panorámica"', $snippet);
        $this->assertStringContainsString('class="ms-card-img', $snippet);
    }

    public function test_html_snippet_escapes_special_characters(): void
    {
        $image = new MicrositeImage([
            'file_name' => 'test.png',
            'file_path' => 'microsites/1/images/test.png',
            'title' => 'Imagen "con comillas" & <script>',
        ]);

        $snippet = $image->html_snippet;

        $this->assertStringNotContainsString('<script>', $snippet);
        $this->assertStringContainsString('&quot;con comillas&quot;', $snippet);
    }

    public function test_relationships_defined(): void
    {
        $image = new MicrositeImage();

        $this->assertInstanceOf(BelongsTo::class, $image->microsite());
        $this->assertInstanceOf(BelongsTo::class, $image->team());
        $this->assertInstanceOf(BelongsTo::class, $image->user());

        $microsite = new Microsite();
        $this->assertInstanceOf(HasMany::class, $microsite->images());
    }
}
