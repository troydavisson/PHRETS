<?php namespace PHRETS\Strategies;

use PHRETS\Configuration;

interface Strategy
{
    /**
     * @param $component
     * @return mixed
     */
    public function provide($component);

    /**
     * @param Configuration $configuration
     * @return mixed
     */
    public function initialize(Configuration $configuration);
}
