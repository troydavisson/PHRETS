<?php namespace PHRETS\Strategies;

use PHRETS\Configuration;

interface Strategy
{
    const PARSER_LOGIN = 'parser.login';
    const PARSER_OBJECT_SINGLE = 'parser.object.single';
    const PARSER_OBJECT_MULTIPLE = 'parser.object.multiple';
    const PARSER_SEARCH = 'parser.search';
    const PARSER_SEARCH_RECURSIVE = 'parser.search.recursive';
    const PARSER_METADATA_SYSTEM = 'parser.metadata.system';
    const PARSER_METADATA_RESOURCE = 'parser.metadata.resource';
    const PARSER_METADATA_CLASS = 'parser.metadata.class';
    const PARSER_METADATA_TABLE = 'parser.metadata.table';
    const PARSER_METADATA_OBJECT = 'parser.metadata.object';
    const PARSER_METADATA_LOOKUPTYPE = 'parser.metadata.lookuptype';
    const PARSER_XML = 'parser.xml';

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
