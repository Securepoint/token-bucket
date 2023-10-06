<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Storage\Scope;

/**
 * Marker interface for the session scope.
 *
 * The session scope is available per session (i.e. per user).
 *
 * A Token bucket which uses a storage of the session scope can limit a rate
 * for a resource within a session. E.g. limit an API usage per user.
 *
 * @license WTFPL
 */
interface SessionScope
{
}
