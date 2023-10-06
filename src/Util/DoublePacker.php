<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Util;

use Securepoint\TokenBucket\Storage\StorageException;

/**
 * Double packer.
 *
 * @license WTFPL
 * @internal
 */
final class DoublePacker
{
    /**
     * Packs a 64 bit double into an 8 byte string.
     *
     * @param double $double 64 bit double
     * @return string packed 8 byte string representation
     */
    public static function pack($double)
    {
        $string = pack('d', $double);
        assert(strlen($string) === 8);
        return $string;
    }

    /**
     * Unpacks a 64 bit double from an 8 byte string.
     *
     * @param string $string packed 8 byte string representation.
     * @return double unpacked 64 bit double
     */
    public static function unpack($string)
    {
        if (strlen($string) !== 8) {
            throw new StorageException('The string is not 64 bit long.');
        }
        $unpack = unpack('d', $string);
        if (! is_array($unpack) || ! array_key_exists(1, $unpack)) {
            throw new StorageException('Could not unpack string.');
        }
        return $unpack[1];
    }
}
