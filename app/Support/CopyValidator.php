<?php
namespace App\Support;

/** Post-checks AI website copy so it cannot introduce facts that are not in the verified facts. */
class CopyValidator
{
    public static function isSafe(array $copy, string $factsBlob): bool
    {
        $texts = [$copy['hero']['headline'] ?? '', $copy['hero']['subheadline'] ?? '', $copy['about']['body'] ?? ''];
        foreach ($copy['services'] ?? [] as $s) $texts[] = $s['description'] ?? '';

        foreach ($texts as $t) {
            if (preg_match('/@|https?:|www\./i', $t)) return false;          // no emails / URLs
            preg_match_all('/\d+/', $t, $m);
            foreach ($m[0] as $digits) if (!str_contains($factsBlob, $digits)) return false; // numbers must exist in facts
        }
        return trim($texts[0]) !== '';                                        // headline required
    }
}
