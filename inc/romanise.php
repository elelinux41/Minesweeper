<?php
/**
 * Convert an integer to a Roman numeral.
 *
 * @param int $num The integer to convert. Must be in the range [-3999, 3999].
 * @return string The Roman numeral representation of the integer.
 * @throws ValueError If the input integer is outside the allowed range.
 */
function romanise(int $num) : string {
    $dict = [
        1000 => 'M',
        500  => 'D',
        100  => 'C',
        50   => 'L',
        10   => 'X',
        5    => 'V',
        1    => 'I',
    ];
    if ($num == 0) {
        return 'O';
    } elseif ($num < 0) {
        return '-' . romanise(-$num);
    } elseif ($num >= 4000) {
        throw new ValueError("Value must be ∈ [-3999, 3999]");
    }
    $num_in_digits = array_reverse(array_reverse(str_split((string)$num)), true);
    $roman = '';
    foreach ($num_in_digits as $place => $digit) {
        $digit = (int)$digit;
        $multiplier = 10 ** $place;
        if ($digit == 9) {
            $roman .= $dict[$multiplier] . $dict[10 * $multiplier];
        } elseif ($digit >= 5) {
            $roman .= $dict[5 * $multiplier] . str_repeat($dict[$multiplier], $digit - 5);
        } elseif ($digit == 4) {
            $roman .= $dict[$multiplier] . $dict[5 * $multiplier];
        } else {
            $roman .= str_repeat($dict[$multiplier], $digit);
        }
    }
    return $roman;
}
?>