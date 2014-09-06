<?php namespace PHRETS\Strategies;

class StandardStrategy extends Strategy
{
    /**
     * @return array
     */
    public function getBindings()
    {
        $bindings = [];

        /**
         * Set default parsers
         */
        $default_parsers = [
            'login' => '\PHRETS\Parsers\Login\OneFive',
            'object.single' => '\PHRETS\Parsers\GetObject\Single',
            'object.multiple' => '\PHRETS\Parsers\GetObject\Multiple',
            'search' => '\PHRETS\Parsers\Search\OneX',
            'search.recursive' => '\PHRETS\Parsers\Search\RecursiveOneX',
            'metadata.system' => '\PHRETS\Parsers\GetMetadata\System',
            'metadata.resource' => '\PHRETS\Parsers\GetMetadata\Resource',
            'metadata.class' => '\PHRETS\Parsers\GetMetadata\ResourceClass',
            'metadata.table' => '\PHRETS\Parsers\GetMetadata\Table',
            'metadata.object' => '\PHRETS\Parsers\GetMetadata\Object',
            'metadata.lookuptype' => '\PHRETS\Parsers\GetMetadata\LookupType',
        ];

        /**
         * Start some basic overrides
         */
        if ($this->getConfiguration()->getRetsVersion()->isAtLeast1_8()) {
            $default_parsers['login'] = '\PHRETS\Parsers\Login\OneEight';
        }

        foreach ($default_parsers as $k => $v) {
            $bindings['parser.' . $k] = $v;
        }

        return $bindings;
    }
}
