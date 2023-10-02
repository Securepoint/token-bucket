<?php

declare(strict_types=1);

namespace Securepoint\TokenBucket\Util;

use Securepoint\TokenBucket\Rate;

/**
 * Tokens converter.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @license WTFPL
 * @internal
 */
final class TokenConverter
{
    /**
     * @var int precision scale for bc_* operations.
     */
    private int $bcScale = 8;

    /**
     * Sets the token rate.
     *
     * @param int $rate The rate.
     */
    public function __construct(private readonly Rate $rate)
    {
    }

    /**
     * Converts a duration of seconds into an amount of tokens.
     *
     * @param double $seconds The duration in seconds.
     * @return int The amount of tokens.
     */
    public function convertSecondsToTokens($seconds)
    {
        return (int) ($seconds * $this->rate->getTokensPerSecond());
    }

    /**
     * Converts an amount of tokens into a duration of seconds.
     *
     * @param int $tokens The amount of tokens.
     * @return double The seconds.
     */
    public function convertTokensToSeconds($tokens)
    {
        return $tokens / $this->rate->getTokensPerSecond();
    }

    /**
     * Converts an amount of tokens into a timestamp.
     *
     * @param int $tokens The amount of tokens.
     * @return double The timestamp.
     */
    public function convertTokensToMicrotime($tokens)
    {
        return microtime(true) - $this->convertTokensToSeconds($tokens);
    }

    /**
     * Converts a timestamp into tokens.
     *
     * @param double $microtime The timestamp.
     *
     * @return int The tokens.
     */
    public function convertMicrotimeToTokens($microtime)
    {
        $delta = bcsub((string) microtime(true), (string) $microtime, $this->bcScale);
        return $this->convertSecondsToTokens($delta);
    }
}
