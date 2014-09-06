<?php namespace PHRETS\Strategies;

use PHRETS\Configuration;

abstract class Strategy
{
    protected $container;
    protected $configuration;

    /**
     * @return array
     */
    abstract public function getBindings();

    /**
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param $container
     * @return $this
     */
    public function setContainer($container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @return \PHRETS\Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return \Illuminate\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}
