<?php namespace PHRETS;

class Capabilities
{
    protected $capabilities = [];

    public function add($name, $uri)
    {
        $this->capabilities[$name] = $uri;
        return $this;
    }

    public function get($name)
    {
        return (array_key_exists($name, $this->capabilities)) ? $this->capabilities[$name] : null;
    }
}
