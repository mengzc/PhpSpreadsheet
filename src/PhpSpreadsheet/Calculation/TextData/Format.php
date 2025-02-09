<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\TextData;

use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Calculation\DateTime;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Format
{
    /**
     * DOLLAR.
     *
     * This function converts a number to text using currency format, with the decimals rounded to the specified place.
     * The format used is $#,##0.00_);($#,##0.00)..
     *
     * @param float $value The value to format
     * @param int $decimals The number of digits to display to the right of the decimal point.
     *                                    If decimals is negative, number is rounded to the left of the decimal point.
     *                                    If you omit decimals, it is assumed to be 2
     */
    public static function DOLLAR($value = 0, $decimals = 2): string
    {
        $value = Functions::flattenSingleValue($value);
        $decimals = $decimals === null ? 2 : Functions::flattenSingleValue($decimals);

        // Validate parameters
        if (!is_numeric($value) || !is_numeric($decimals)) {
            return Functions::VALUE();
        }
        $decimals = (int) $decimals;

        $mask = '$#,##0';
        if ($decimals > 0) {
            $mask .= '.' . str_repeat('0', $decimals);
        } else {
            $round = 10 ** abs($decimals);
            if ($value < 0) {
                $round = 0 - $round;
            }
            $value = MathTrig\Mround::funcMround($value, $round);
        }
        $mask = "$mask;($mask)";

        return NumberFormat::toFormattedString($value, $mask);
    }

    /**
     * FIXEDFORMAT.
     *
     * @param mixed $value Value to check
     * @param mixed $decimals
     * @param bool $noCommas
     */
    public static function FIXEDFORMAT($value, $decimals = 2, $noCommas = false): string
    {
        $value = Functions::flattenSingleValue($value);
        $decimals = $decimals === null ? 2 : Functions::flattenSingleValue($decimals);
        $noCommas = Functions::flattenSingleValue($noCommas);

        // Validate parameters
        if (!is_numeric($value) || !is_numeric($decimals)) {
            return Functions::VALUE();
        }
        $decimals = (int) floor($decimals);

        $valueResult = round($value, $decimals);
        if ($decimals < 0) {
            $decimals = 0;
        }
        if (!$noCommas) {
            $valueResult = number_format(
                $valueResult,
                $decimals,
                StringHelper::getDecimalSeparator(),
                StringHelper::getThousandsSeparator()
            );
        }

        return (string) $valueResult;
    }

    /**
     * TEXTFORMAT.
     *
     * @param mixed $value Value to check
     * @param string $format Format mask to use
     */
    public static function TEXTFORMAT($value, $format): string
    {
        $value = Functions::flattenSingleValue($value);
        $format = Functions::flattenSingleValue($format);

        if ((is_string($value)) && (!is_numeric($value)) && Date::isDateTimeFormatCode($format)) {
            $value = DateTime::DATEVALUE($value);
        }

        return (string) NumberFormat::toFormattedString($value, $format);
    }

    /**
     * VALUE.
     *
     * @param mixed $value Value to check
     *
     * @return DateTimeInterface|float|int|string A string if arguments are invalid
     */
    public static function VALUE($value = '')
    {
        $value = Functions::flattenSingleValue($value);

        if (!is_numeric($value)) {
            $numberValue = str_replace(
                StringHelper::getThousandsSeparator(),
                '',
                trim($value, " \t\n\r\0\x0B" . StringHelper::getCurrencyCode())
            );
            if (is_numeric($numberValue)) {
                return (float) $numberValue;
            }

            $dateSetting = Functions::getReturnDateType();
            Functions::setReturnDateType(Functions::RETURNDATE_EXCEL);

            if (strpos($value, ':') !== false) {
                $timeValue = DateTime::TIMEVALUE($value);
                if ($timeValue !== Functions::VALUE()) {
                    Functions::setReturnDateType($dateSetting);

                    return $timeValue;
                }
            }
            $dateValue = DateTime::DATEVALUE($value);
            if ($dateValue !== Functions::VALUE()) {
                Functions::setReturnDateType($dateSetting);

                return $dateValue;
            }
            Functions::setReturnDateType($dateSetting);

            return Functions::VALUE();
        }

        return (float) $value;
    }

    /**
     * NUMBERVALUE.
     *
     * @param mixed $value Value to check
     * @param string $decimalSeparator decimal separator, defaults to locale defined value
     * @param string $groupSeparator group/thosands separator, defaults to locale defined value
     *
     * @return float|string
     */
    public static function NUMBERVALUE($value = '', $decimalSeparator = null, $groupSeparator = null)
    {
        $value = Functions::flattenSingleValue($value);
        $decimalSeparator = Functions::flattenSingleValue($decimalSeparator);
        $groupSeparator = Functions::flattenSingleValue($groupSeparator);

        if (!is_numeric($value)) {
            $decimalSeparator = empty($decimalSeparator) ? StringHelper::getDecimalSeparator() : $decimalSeparator;
            $groupSeparator = empty($groupSeparator) ? StringHelper::getThousandsSeparator() : $groupSeparator;

            $decimalPositions = preg_match_all('/' . preg_quote($decimalSeparator) . '/', $value, $matches, PREG_OFFSET_CAPTURE);
            if ($decimalPositions > 1) {
                return Functions::VALUE();
            }
            $decimalOffset = array_pop($matches[0])[1];
            if (strpos($value, $groupSeparator, $decimalOffset) !== false) {
                return Functions::VALUE();
            }

            $value = str_replace([$groupSeparator, $decimalSeparator], ['', '.'], $value);

            // Handle the special case of trailing % signs
            $percentageString = rtrim($value, '%');
            if (!is_numeric($percentageString)) {
                return Functions::VALUE();
            }

            $percentageAdjustment = strlen($value) - strlen($percentageString);
            if ($percentageAdjustment) {
                $value = (float) $percentageString;
                $value /= 10 ** ($percentageAdjustment * 2);
            }
        }

        return (float) $value;
    }
}
