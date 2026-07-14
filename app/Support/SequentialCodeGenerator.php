<?php

namespace App\Support;

/**
 * Shared "increment the trailing number of the last code" logic used by
 * every auto-generated code (Branch Code, per-branch Employee Code, ...) so
 * each one preserves whatever prefix/padding is already in use instead of
 * imposing a new fixed format.
 */
class SequentialCodeGenerator
{
    /**
     * Increment the trailing numeric portion of $lastCode, preserving its
     * prefix and zero-padding width. Falls back to $fallback when there is
     * no previous code, or it has no trailing digits to increment.
     */
    public static function next(?string $lastCode, string $fallback): string
    {
        if ($lastCode && preg_match('/^(.*?)(\d+)$/', $lastCode, $m)) {
            $incremented = (string) ((int) $m[2] + 1);

            return $m[1] . str_pad($incremented, strlen($m[2]), '0', STR_PAD_LEFT);
        }

        return $fallback;
    }
}
