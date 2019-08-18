<?php

namespace INWX;

class Base32
{
    /**
     * Utility to decode base32 for 2 factor auth.
     *
     * @param string $secret
     *
     * @return string
     */
    public function decode(string $secret): string
    {
        if (empty($secret)) {
            return '';
        }

        $base32chars = $this->getLookupTable();
        $base32charsFlipped = array_flip($base32chars);

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

    /**
     * Helper method to lookup base32 decoding.
     *
     * @return array
     */
    private function getLookupTable(): array
    {
        return [
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H', // 7
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P', // 15
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'X', // 23
            'Y',
            'Z',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7', // 31
            '=', // padding char
        ];
    }
}
