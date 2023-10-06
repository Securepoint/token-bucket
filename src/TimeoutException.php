<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket;

/**
 * This exception indicates an exceeded timeout.
 *
 * @license WTFPL
 */
final class TimeoutException extends TokenBucketException
{
}
