<?php
declare(strict_types=1);

namespace App\Formatter;

use Cake\I18n\FrozenDate;

class Formatter
{
    /**
     * Returns an up or down arrow to indicate positive or negative, or no arrow for zero
     *
     * @param mixed $value Observation value
     * @return string|null
     */
    public static function getArrow($value): ?string
    {
        if ($value > 0) {
            return '<i class="fas fa-arrow-circle-up"></i>';
        }

        if ($value < 0) {
            return '<i class="fas fa-arrow-circle-down"></i>';
        }

        return null;
    }

    /**
     * Returns a formatted observation value
     *
     * @param mixed $value Observation value
     * @param ?string $prepend String to apply before numeric part of return value
     * @return string
     */
    public static function formatValue($value, $prepend = null): string
    {
        $decimalLimit = 2;
        $negative = (float)$value < 0;
        $value = round((float)$value, $decimalLimit);
        $value = number_format($value, $decimalLimit);
        if (str_contains($value, '.')) {
            $value = rtrim($value, '0');
        }
        if (substr($value, -1) === '.') {
            $value = rtrim($value, '.');
        }
        if ($negative) {
            return str_replace('-', '-' . $prepend, $value);
        }

        return $prepend . $value;
    }

    /**
     * Returns a formatted date string
     *
     * @param \Cake\I18n\FrozenDate $date Date string
     * @param string $frequency e.g. 'monthly'
     * @return string
     */
    public static function getFormattedDate(FrozenDate $date, string $frequency): string
    {
        $frequency = strtolower($frequency);
        if (str_contains($frequency, 'quarterly')) {
            $month = $date->format('n');
            $quarter = ceil($month / 3);

            return sprintf('Q%s %s', $quarter, $date->format('Y'));
        }

        if (str_contains($frequency, 'monthly')) {
            $format = 'F Y';
        } else {
            $format = 'F j, Y';
        }

        return (new FrozenDate($date))->format($format);
    }

    /**
     * Returns a string that values should be prepended with, or NULL
     *
     * @param string $unit The result of getUnit()
     * @return string|null
     */
    public static function getPrepend(string $unit): ?string
    {
        return str_contains(strtolower($unit), 'dollar') ? '$' : null;
    }
}
