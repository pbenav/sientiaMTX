<?php

declare(strict_types=1);

namespace App\Actions\Passkeys;

use Laravel\Passkeys\Actions\GenerateVerificationOptions as BaseGenerateVerificationOptions;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkeys;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialRequestOptions;

class CustomGenerateVerificationOptions extends BaseGenerateVerificationOptions
{
    /**
     * Override verification options generator to soften user verification
     * so that Linux browsers don't suppress QR/Hybrid login.
     */
    public function __invoke(?PasskeyUser $user = null): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            rpId: Passkeys::relyingPartyId(),
            allowCredentials: $this->allowCredentials($user),
            // Changing from REQUIRED to PREFERRED unlocks the hybrid flow on Linux
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            timeout: Passkeys::timeout(),
        );
    }
}
