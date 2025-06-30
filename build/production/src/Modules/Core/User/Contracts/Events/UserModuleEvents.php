<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Contracts\Events;

/**
 * User Module Events - Public Event Names.
 *
 * Defines the event names that the User module publishes
 * for other modules to subscribe to.
 */
final class UserModuleEvents
{
    /**
     * Fired when a new user is registered.
     */
    public const USER_REGISTERED = 'user.registered';

    /**
     * Fired when user information is updated.
     */
    public const USER_UPDATED = 'user.updated';

    /**
     * Fired when user is deleted.
     */
    public const USER_DELETED = 'user.deleted';

    /**
     * Fired when user password is changed.
     */
    public const USER_PASSWORD_CHANGED = 'user.password_changed';

    /**
     * Fired when user status changes (active/inactive/suspended).
     */
    public const USER_STATUS_CHANGED = 'user.status_changed';

    /**
     * Fired when user role is changed.
     */
    public const USER_ROLE_CHANGED = 'user.role_changed';

    /**
     * Fired when user logs in successfully.
     */
    public const USER_LOGGED_IN = 'user.logged_in';

    /**
     * Fired when user logs out.
     */
    public const USER_LOGGED_OUT = 'user.logged_out';

    /**
     * Get all available event names.
     *
     * @return array<string>
     */
    public static function getAllEvents(): array
    {
        return [
            self::USER_REGISTERED,
            self::USER_UPDATED,
            self::USER_DELETED,
            self::USER_PASSWORD_CHANGED,
            self::USER_STATUS_CHANGED,
            self::USER_ROLE_CHANGED,
            self::USER_LOGGED_IN,
            self::USER_LOGGED_OUT,
        ];
    }
}
