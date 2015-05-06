<?php namespace PHRETS\Parsers\Login;

abstract class OneX
{
    protected $capabilities = [];
    protected $details = [];
    protected $valid_transactions = [
        'Action', 'ChangePassword', 'GetObject', 'Login', 'LoginComplete', 'Logout', 'Search', 'GetMetadata',
        'ServerInformation', 'Update', 'PostObject', 'GetPayloadList'
    ];

    public function parse($body)
    {
        $lines = explode("\r\n", $body);
        if (empty($lines[3])) {
            $lines = explode("\n", $body);
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) {
                continue;
            }

            list($name, $value) = $this->readLine($line);
            if ($name) {
                if (in_array($name, $this->valid_transactions) or preg_match('/^X\-/', $name)) {
                    $this->capabilities[$name] = $value;
                } else {
                    $this->details[$name] = $value;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getCapabilities()
    {
        return $this->capabilities;
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    abstract public function readLine($line);
}
