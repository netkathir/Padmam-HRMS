<?php

namespace App\Support;

/** FSD 13.7 — "Net Salary in Words," Indian numbering (lakh/crore), pure PHP, no package. */
class NumberToWords
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen',
    ];
    private const TENS = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    public static function convert(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        $words = trim(self::convertInteger($rupees)) . ' Rupees';
        if ($paise > 0) {
            $words .= ' and ' . trim(self::convertInteger($paise)) . ' Paise';
        }

        return $words . ' Only';
    }

    private static function convertInteger(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        $lakh = intdiv($number, 100000);
        $number %= 100000;
        $thousand = intdiv($number, 1000);
        $number %= 1000;
        $hundred = intdiv($number, 100);
        $rest = $number % 100;

        $parts = [];
        if ($crore) $parts[] = self::belowThousand($crore) . ' Crore';
        if ($lakh) $parts[] = self::belowThousand($lakh) . ' Lakh';
        if ($thousand) $parts[] = self::belowThousand($thousand) . ' Thousand';
        if ($hundred) $parts[] = self::ONES[$hundred] . ' Hundred';
        if ($rest) $parts[] = self::belowHundred($rest);

        return implode(' ', $parts);
    }

    private static function belowThousand(int $n): string
    {
        $hundred = intdiv($n, 100);
        $rest = $n % 100;
        $out = $hundred ? self::ONES[$hundred] . ' Hundred' : '';
        $restWords = self::belowHundred($rest);
        return trim($out . ' ' . $restWords);
    }

    private static function belowHundred(int $n): string
    {
        if ($n < 20) {
            return self::ONES[$n];
        }
        $tens = intdiv($n, 10);
        $ones = $n % 10;
        return trim(self::TENS[$tens] . ' ' . ($ones ? self::ONES[$ones] : ''));
    }
}
