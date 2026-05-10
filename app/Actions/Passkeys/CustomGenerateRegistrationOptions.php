<?php

declare(strict_types=1);

namespace App\Actions\Passkeys;

use Laravel\Passkeys\Actions\GenerateRegistrationOptions as BaseGenerateRegistrationOptions;
use Webauthn\AuthenticatorSelectionCriteria;

class CustomGenerateRegistrationOptions extends BaseGenerateRegistrationOptions
{
    /**
     * Override authenticator selection criteria to force Cross-Platform (Mobile/QR) 
     * and soften user verification to avoid Linux browser restrictions.
     */
    public function authenticatorSelection(): AuthenticatorSelectionCriteria
    {
        $ua = strtolower(request()->userAgent() ?? '');
        $isMobile = str_contains($ua, 'android') || str_contains($ua, 'iphone') || str_contains($ua, 'ipad');

        // If we are ON a mobile device, allow the browser to prefer its internal sensor (Platform).
        // Otherwise (on Desktop Linux/Windows), keep forcing Cross-Platform to encourage the QR/Mobile hybrid flow.
        $attachment = $isMobile 
            ? AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE 
            : AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_CROSS_PLATFORM;

        // Require a discoverable credential
        $residentKey = AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED;

        // Keep as PREFERRED to prevent strict OS blockers on systems without deep hardware management
        $userVerification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;

        return AuthenticatorSelectionCriteria::create(
            authenticatorAttachment: $attachment,
            userVerification: $userVerification,
            residentKey: $residentKey,
        );
    }
}
