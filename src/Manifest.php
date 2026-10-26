<?php

namespace HarborTours;

/**
 * The skipper's passenger list for one departure, built from the day's bookings file
 * (one booking per line: name|phone|adults|children) and printed as semicolon-separated lines.
 */
class Manifest
{
    public static function fromLines(array $lines)
    {
        $out = array('name;phone;adults;children;seats');
        $seats = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $fields = explode('|', $line);
            $fields[4] = (int) $fields[2] + (int) $fields[3];
            $seats += $fields[4];
            $out[] = implode(';', $fields);
        }
        $out[] = "total;;;;$seats";
        return implode("\n", $out);
    }
}
