<?php

namespace Tests\Unit;

use Tests\TestCase;

class StorageTranslationTest extends TestCase
{
    public function test_team_quota_message_is_translated_in_all_supported_locales(): void
    {
        $locales = ['es', 'en', 'fr', 'de', 'pt', 'ar', 'ro', 'wo'];
        $keyTitle = 'Your Team Quota';
        $keyBody = 'This team is assigned :quota GB of storage. Upon reaching the limit, members will not be able to upload new files until space is freed or the quota is increased.';

        foreach ($locales as $locale) {
            app()->setLocale($locale);

            $translatedTitle = __($keyTitle);
            $this->assertNotEmpty($translatedTitle);

            $translatedBody = __($keyBody, ['quota' => '4']);
            $this->assertStringContainsString('4', $translatedBody);
            $this->assertNotEquals($keyBody, $translatedBody, "Locale {$locale} should not fallback to the raw translation key.");
        }
    }
}
