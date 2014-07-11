<?php namespace PHRETS\Parsers\Login;

class OneEight extends OneX
{
    public function readLine($line)
    {
        $name = null;
        $value = null;

        if (strpos($line, '=') !== false) {
            @list($name, $value) = explode("=", $line, 2);
        }

        if ($name == 'Info') {
            if ($value) {
                list($name, $type, $value) = explode(';', $value);
                if ($type == 'Int') {
                    $value = (int) $value;
                } elseif ($type == 'Boolean') {
                    $value = (bool) $value;
                } else {
                    $value = trim($value);
                }
            }
        }

        return [trim($name), $value];
    }
}
