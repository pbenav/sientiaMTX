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
        // Forces option to use external/mobile devices, which enables QR code flow on restricted OSes
        $crossPlatform = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_CROSS_PLATFORM;

        // Require a discoverable credential
        $residentKey = AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED;

        // Downgrade from REQUIRED to PREFERRED to prevent strict OS blockers on Linux without native PIN management
        $userVerification = AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED;

        return AuthenticatorSelectionCriteria::create(
            authenticatorAttachment: $crossPlatform,
            userVerification: $userVerification,
            residentKey: $residentKey,
        );
    }
}
