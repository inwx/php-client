<?php

namespace INWX;

class Base32
{
    private const CHARS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '2', '3', '4', '5', '6', '7', '='];
    private const CHARS_FLIPPED = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23, 'Y' => 24, 'Z' => 25, '2' => 26, '3' => 27, '4' => 28, '5' => 29, '6' => 30, '7' => 31, '=' => 32];

    /**
     * Utility to decode base32 for 2 factor auth.
     *
     * @param string $secret
     *
     * @return string|false
     */
    public function decode(string $secret): string|false
    {
        if (empty($secret)) {
            return '';
        }

        $base32chars = self::CHARS;
        $base32charsFlipped = self::CHARS_FLIPPED;

        $paddingCharCount = substr_count($secret, $base32chars[32]);
        $allowedValues = [6, 4, 3, 1, 0];
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }
        for ($i = 0; $i < 4; ++$i) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -$allowedValues[$i]) != str_repeat($base32chars[32], $allowedValues[$i])) {
                return false;
            }
        }
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $remainder = count($secret) % 8;
        if ($remainder !== 0) {
            $secret = array_pad($secret, count($secret) + (8 - $remainder), 'A');
        }
        $binaryString = '';
        $secretCount = count($secret);
        for ($i = 0; $i < $secretCount; $i += 8) {
            $x = '';
            if (!in_array($secret[$i], $base32chars)) {
                return false;
            }
            for ($j = 0; $j < 8; ++$j) {
                $x .= str_pad(base_convert($base32charsFlipped[$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            $eightBitsCount = count($eightBits);
            for ($z = 0; $z < $eightBitsCount; ++$z) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || 48 == ord($y)) ? $y : '';
            }
        }

        return $binaryString;
    }

}
