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
        Strategy::PARSER_LOGIN => \PHRETS\Parsers\Login\OneFive::class,
        Strategy::PARSER_OBJECT_SINGLE => \PHRETS\Parsers\GetObject\Single::class,
        Strategy::PARSER_OBJECT_MULTIPLE => \PHRETS\Parsers\GetObject\Multiple::class,
        Strategy::PARSER_SEARCH => \PHRETS\Parsers\Search\OneX::class,
        Strategy::PARSER_SEARCH_RECURSIVE => \PHRETS\Parsers\Search\RecursiveOneX::class,
        Strategy::PARSER_METADATA_SYSTEM => \PHRETS\Parsers\GetMetadata\System::class,
        Strategy::PARSER_METADATA_RESOURCE => \PHRETS\Parsers\GetMetadata\Resource::class,
        Strategy::PARSER_METADATA_CLASS => \PHRETS\Parsers\GetMetadata\ResourceClass::class,
        Strategy::PARSER_METADATA_TABLE => \PHRETS\Parsers\GetMetadata\Table::class,
        Strategy::PARSER_METADATA_OBJECT => \PHRETS\Parsers\GetMetadata\BaseObject::class,
        Strategy::PARSER_METADATA_LOOKUPTYPE => \PHRETS\Parsers\GetMetadata\LookupType::class,
        Strategy::PARSER_XML => \PHRETS\Parsers\XML::class,
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
                $v = \PHRETS\Parsers\Login\OneEight::class;
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
