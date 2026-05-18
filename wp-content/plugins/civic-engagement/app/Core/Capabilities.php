<?php

declare(strict_types=1);

namespace CivicPlatform\Core;

/**
 * Registers Civic Platform roles and capabilities.
 *
 * Capability management is centralized so module admin pages can rely on
 * explicit current_user_can() checks without depending on third-party role
 * plugins.
 */
class Capabilities
{
    /**
     * Civic manager role name.
     */
    private const MANAGER_ROLE = 'civic_manager';

    /**
     * Reps management capability.
     */
    private const MANAGE_REPS = 'manage_civic_reps';

    /**
     * Register initial roles and capabilities.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerManagerRole();
        $this->grantAdministratorCapabilities();
    }

    /**
     * Register the civic manager role.
     *
     * @return void
     */
    private function registerManagerRole(): void
    {
        $role = get_role(self::MANAGER_ROLE);

        if (null === $role) {
            add_role(
                self::MANAGER_ROLE,
                __('Civic Manager', 'civic-engagement'),
                [
                    'read' => true,
                    self::MANAGE_REPS => true,
                ]
            );

            return;
        }

        $role->add_cap(self::MANAGE_REPS);
    }

    /**
     * Grant civic capabilities to administrators.
     *
     * @return void
     */
    private function grantAdministratorCapabilities(): void
    {
        $role = get_role('administrator');

        if (null === $role) {
            return;
        }

        $role->add_cap(self::MANAGE_REPS);
    }
}
