<?php namespace PHRETS\Versions;

use PHRETS\Exceptions\InvalidRETSVersion;

class RETSVersion
{
    protected $number;
    protected $valid_versions = ['1.5', '1.7', '1.7.1', '1.7.2', '1.8'];

    public function setVersion($version)
    {
        $this->number = str_replace('RETS/', '', $version);
        if (!in_array($this->number, $this->valid_versions)) {
            throw new InvalidRETSVersion("RETS version '{$version}' given is not understood");
        }
        return $this;
    }

    public function getVersion()
    {
        return $this->number;
    }

    public function asHeader()
    {
        return 'RETS/' . $this->number;
    }

    public function is1_5()
    {
        return ($this->number == '1.5');
    }

    public function is1_7()
    {
        return ($this->number == '1.7');
    }

    public function is1_7_2()
    {
        return ($this->number == '1.7.2');
    }

    public function is1_8()
    {
        return ($this->number == '1.8');
    }

    public function isAtLeast($version)
    {
        return (version_compare($this->number, $version) >= 0);
    }

    public function isAtLeast1_5()
    {
        return $this->isAtLeast('1.5');
    }

    public function isAtLeast1_7()
    {
        return $this->isAtLeast('1.7');
    }

    public function isAtLeast1_7_2()
    {
        return $this->isAtLeast('1.7.2');
    }

    public function isAtLeast1_8()
    {
        return $this->isAtLeast('1.8');
    }

    public function __toString()
    {
        return $this->asHeader();
    }
}
