<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Account;

/** Bootstraps the Civic-only account administration experience. */
class AccountModule
{
    public function register(): void
    {
        (new AccountAdmin(new ChangePasswordPage()))->register();
    }
}
