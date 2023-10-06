<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Storage\Scope;

/**
 * Marker interface for the request scope.
 *
 * The request scope is available only per process (i.e. per request).
 *
 * A Token bucket which uses a storage of the request scope can limit a rate
 * for a resource which is used within one request. E.g. bandwidth throtteling
 * for downloading a stream.
 *
 * @license WTFPL
 */
interface RequestScope
{
}
