<?php

namespace App\Services;

use App\Models\MotivationalQuote;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class QuoteService
{
    /**
     * Get a random greeting and quote.
     * 
     * @return array
     */
    public function getWelcomeMessage(): array
    {
        $greeting = MotivationalQuote::where('type', 'greeting')->inRandomOrder()->first();
        
        // 20% chance to try to fetch from an external API if internet is available
        $quote = null;
        if (rand(1, 100) <= 20) {
            $quote = $this->fetchExternalQuote();
        }

        // Fallback to local DB if API failed or wasn't chosen
        if (!$quote) {
            $quote = MotivationalQuote::where('type', 'quote')->inRandomOrder()->first();
        }

        return [
            'greeting' => $greeting,
            'quote' => $quote,
        ];
    }

    /**
     * Fetch a quote from an external API (ZenQuotes).
     * 
     * @return MotivationalQuote|null
     */
    private function fetchExternalQuote(): ?MotivationalQuote
    {
        try {
            // We use a small cache to avoid hitting the API too many times per minute
            $response = Cache::remember('external_quote_api', 30, function () {
                return Http::timeout(3)->get('https://zenquotes.io/api/random');
            });

            if ($response && $response->successful()) {
                $data = $response->json();
                if (isset($data[0]['q'])) {
                    $text = $data[0]['q'];
                    $author = $data[0]['a'] ?? 'Desconocido';

                    // Simple "dummy" translation layer or just keep English if preferred.
                    // For Sientia, we'll try to find if we already have it or save it as new.
                    $quote = MotivationalQuote::firstOrCreate(
                        ['text' => $text],
                        [
                            'author' => $author,
                            'type' => 'quote'
                        ]
                    );

                    return $quote;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not fetch external quote: ' . $e->getMessage());
        }

        return null;
    }
}
