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
        // Set to NO_PREFERENCE universally to allow the browser to decide.
        // This presents BOTH internal sensors (if available) AND external devices/QR flow seamlessly.
        $attachment = AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE;

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
