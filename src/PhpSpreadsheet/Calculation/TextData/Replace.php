<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\TextData;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\TextData;

class Replace
{
    /**
     * REPLACE.
     *
     * @param string $oldText String to modify
     * @param int $start Start character
     * @param int $chars Number of characters
     * @param string $newText String to replace in defined position
     */
    public static function replace($oldText, $start, $chars, $newText): string
    {
        $oldText = Functions::flattenSingleValue($oldText);
        $start = Functions::flattenSingleValue($start);
        $chars = Functions::flattenSingleValue($chars);
        $newText = Functions::flattenSingleValue($newText);

        $left = Extract::left($oldText, $start - 1);
        $right = Extract::right($oldText, TextData::STRINGLENGTH($oldText) - ($start + $chars) + 1);

        return $left . $newText . $right;
    }

    /**
     * SUBSTITUTE.
     *
     * @param string $text Value
     * @param string $fromText From Value
     * @param string $toText To Value
     * @param int $instance Instance Number
     */
    public static function substitute($text = '', $fromText = '', $toText = '', $instance = 0): string
    {
        $text = Functions::flattenSingleValue($text);
        $fromText = Functions::flattenSingleValue($fromText);
        $toText = Functions::flattenSingleValue($toText);
        $instance = floor(Functions::flattenSingleValue($instance));

        if ($instance == 0) {
            return str_replace($fromText, $toText, $text);
        }

        $pos = -1;
        while ($instance > 0) {
            $pos = mb_strpos($text, $fromText, $pos + 1, 'UTF-8');
            if ($pos === false) {
                break;
            }
            --$instance;
        }

        if ($pos !== false) {
            return self::REPLACE($text, ++$pos, mb_strlen($fromText, 'UTF-8'), $toText);
        }

        return $text;
    }
}
