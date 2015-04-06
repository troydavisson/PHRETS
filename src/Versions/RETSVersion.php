<?php namespace PHRETS\Versions;

use PHRETS\Exceptions\InvalidRETSVersion;

class RETSVersion
{
    const VERSION_1_5 = '1.5';
    const VERSION_1_7 = '1.7';
    const VERSION_1_7_1 = '1.7.1';
    const VERSION_1_7_2 = '1.7.2';
    const VERSION_1_8 = '1.8';

    protected $number;
    protected $valid_versions = [
        self::VERSION_1_5,
        self::VERSION_1_7,
        self::VERSION_1_7_1,
        self::VERSION_1_7_2,
        self::VERSION_1_8,
    ];

    /**
     * @param $version
     * @return $this
     * @throws InvalidRETSVersion
     */
    public function setVersion($version)
    {
        $this->number = str_replace('RETS/', '', $version);
        if (!in_array($this->number, $this->valid_versions)) {
            throw new InvalidRETSVersion("RETS version '{$version}' given is not understood");
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function asHeader()
    {
        return 'RETS/' . $this->number;
    }

    /**
     * @return bool
     */
    public function is1_5()
    {
        return ($this->number == self::VERSION_1_5);
    }

    /**
     * @return bool
     */
    public function is1_7()
    {
        return ($this->number == self::VERSION_1_7);
    }

    /**
     * @return bool
     */
    public function is1_7_2()
    {
        return ($this->number == self::VERSION_1_7_2);
    }

    /**
     * @return bool
     */
    public function is1_8()
    {
        return ($this->number == self::VERSION_1_8);
    }

    /**
     * @param $version
     * @return bool
     */
    public function isAtLeast($version)
    {
        return (version_compare($this->number, $version) >= 0);
    }

    /**
     * @return bool
     */
    public function isAtLeast1_5()
    {
        return $this->isAtLeast(self::VERSION_1_5);
    }

    /**
     * @return bool
     */
    public function isAtLeast1_7()
    {
        return $this->isAtLeast(self::VERSION_1_7);
    }

    /**
     * @return bool
     */
    public function isAtLeast1_7_2()
    {
        return $this->isAtLeast(self::VERSION_1_7_2);
    }

    /**
     * @return bool
     */
    public function isAtLeast1_8()
    {
        return $this->isAtLeast(self::VERSION_1_8);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->asHeader();
    }
}
