<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket;

/**
 * This exception indicates an exceeded timeout.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @license WTFPL
 */
final class TimeoutException extends TokenBucketException
{
}
