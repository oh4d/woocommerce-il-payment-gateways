<?php

if (!function_exists('wc_ilpg_iframe')) {
    /**
     * @param string $src
     * @param string $width
     * @param string $height
     * @param string $name
     * @return string
     */
    function wc_ilpg_iframe($src = '', $width = '', $height = '', $name = '')
    {
        return '<iframe src="'.$src.'" height="'.$height.'" width="'.$width.'" style="border: 1px solid #ebebeb;"></iframe>';
    }
}

if (!function_exists('get_request_ip')) {
    /**
     * Get Request Client IP Address
     *
     * @return mixed
     */
    function get_request_ip()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            return $_SERVER['HTTP_CLIENT_IP'];

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            return $_SERVER['HTTP_X_FORWARDED_FOR'];

        return $_SERVER['REMOTE_ADDR'];
    }
}

if (!function_exists('luhn_check')) {
    /**
     * Luhn algorithm number checker - (c) 2005-2008 shaman - www.planzero.org
     * This code has been released into the public domain
     *
     * @param $number
     * @return bool
     */
    function luhn_check($number)
    {
        // Strip any non-digits (useful for credit card numbers with spaces and hyphens)
        $number = preg_replace('/\D/', '', $number);

        // Set the string length and parity
        $number_length = strlen($number);
        $parity = $number_length % 2;

        // Loop through each digit and do the maths
        $total = 0;

        for ($i = 0; $i<$number_length; $i++) {
            $digit = $number[$i];

            // Multiply alternate digits by two
            if ($i % 2 == $parity) {
                $digit *= 2;

                // If the sum is two digits, add them together (in effect)
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            // Total up the digits
            $total += $digit;
        }

        // If the total mod 10 equals 0, the number is valid
        return ($total % 10 == 0) ? true : false;
    }
}