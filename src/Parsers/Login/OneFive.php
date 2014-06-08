<?php namespace PHRETS\Parsers\Login;

class OneFive extends OneX
{
    public function readLine($line)
    {
        $name = null;
        $value = null;

        if (strpos($line, '=') !== false) {
            @list($name, $value) = explode("=", $line, 2);
        }

        return [trim($name), trim($value)];
    }
}
