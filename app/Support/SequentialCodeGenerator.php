<?php

namespace App\Support;

/**
 * Shared "increment the trailing number of the last code" logic used by
 * every auto-generated code (Branch Code, per-branch Employee Code, Shift
 * Code, ...) so each one preserves whatever prefix/padding is already in
 * use instead of imposing a new fixed format.
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

    /**
     * The "last code" to feed into next() must be the row holding the
     * HIGHEST numeric suffix, INCLUDING soft-deleted rows — a database
     * unique index has no concept of "soft deleted," so a deleted row's
     * code still blocks it from ever being reused. Picking "whichever row
     * has the highest id" (the previous approach, still followed by every
     * caller here) silently ignores this: a deleted row can hold a HIGHER
     * code than the newest surviving row, causing next() to recompute and
     * collide with that still-reserved deleted code every time. This scans
     * every code (via a soft-deleting model's withTrashed() query, which
     * the caller must pass in already scoped that way) and returns the one
     * with the largest trailing number — not the most recently inserted row.
     *
     * @param \Illuminate\Support\Collection<int, string> $allCodes every existing code for this model, INCLUDING trashed rows
     */
    public static function highestCode(\Illuminate\Support\Collection $allCodes): ?string
    {
        return $allCodes
            ->filter(fn ($code) => $code && preg_match('/^(.*?)(\d+)$/', $code))
            ->sortByDesc(function ($code) {
                preg_match('/^(.*?)(\d+)$/', $code, $m);
                return (int) $m[2];
            })
            ->first();
    }
}
