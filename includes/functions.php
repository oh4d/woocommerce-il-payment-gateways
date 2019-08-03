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

if (!function_exists('to_xml')) {
    /**
     * Convert Array To XML Tags
     *
     * @param $array
     * @param string|null $lastNodeKey
     * @return string
     */
    function to_xml($array, $lastNodeKey = null)
    {
        $xml = '';

        foreach ($array as $element => $value) {
            if (is_array($value)) {
                $arrayKey = (is_numeric($element) && $lastNodeKey) ? $lastNodeKey : $element;

                if (!is_numeric($element) || $element > 0) {
                    $xml .= "<$arrayKey>";
                }

                $xml .= to_xml($value, $element);

                if (!is_numeric($element) || $element < count($array) - 1) {
                    $xml .= "</$arrayKey>";
                }
            } elseif ($value == '') {
                $xml .= "<$element></$element>";
            } else {
                $xml .= "<$element>" . htmlentities($value) . "</$element>";
            }
        }

        return $xml;
    }
}

if (!function_exists('gen_uuid')) {
    /**
     * Generate uuid v3
     *
     * @return string
     */
    function gen_uuid()
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
}
