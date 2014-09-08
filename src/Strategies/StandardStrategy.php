<?php namespace PHRETS\Strategies;

use Illuminate\Container\Container;
use PHRETS\Configuration;

class StandardStrategy implements Strategy
{
    /**
     * Default components
     *
     * @var array
     */
    protected $default_components = [
        'parser.login' => '\PHRETS\Parsers\Login\OneFive',
        'parser.object.single' => '\PHRETS\Parsers\GetObject\Single',
        'parser.object.multiple' => '\PHRETS\Parsers\GetObject\Multiple',
        'parser.search' => '\PHRETS\Parsers\Search\OneX',
        'parser.search.recursive' => '\PHRETS\Parsers\Search\RecursiveOneX',
        'parser.metadata.system' => '\PHRETS\Parsers\GetMetadata\System',
        'parser.metadata.resource' => '\PHRETS\Parsers\GetMetadata\Resource',
        'parser.metadata.class' => '\PHRETS\Parsers\GetMetadata\ResourceClass',
        'parser.metadata.table' => '\PHRETS\Parsers\GetMetadata\Table',
        'parser.metadata.object' => '\PHRETS\Parsers\GetMetadata\Object',
        'parser.metadata.lookuptype' => '\PHRETS\Parsers\GetMetadata\LookupType',
    ];

    /**
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * @param $component
     * @return mixed
     */
    public function provide($component)
    {
        return $this->container->make($component);
    }

    /**
     * @param Configuration $configuration
     * @return void
     */
    public function initialize(Configuration $configuration)
    {
        // start up the service locator
        $this->container = new Container;

        foreach ($this->default_components as $k => $v) {
            if ($k == 'parser.login' and $configuration->getRetsVersion()->isAtLeast1_8()) {
                $v ='\PHRETS\Parsers\Login\OneEight';
            }

            $this->container->singleton($k, function () use ($v) { return new $v; });
        }
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }
}
