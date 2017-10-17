<?php namespace PHRETS\Parsers\Search;

use PHRETS\Http\Response;
use PHRETS\Exceptions\AutomaticPaginationError;
use PHRETS\Models\Search\LiveResults;
use PHRETS\Models\Search\Results;
use PHRETS\Session;

class RecursiveOneX
{
    public function parse(Session $rets, Response $response, $parameters)
    {
        // we're given the first response automatically, so parse this and start the recursion

        /** @var \PHRETS\Parsers\Search\OneX $parser */
        $parser = $rets->getConfiguration()->getStrategy()->provide('parser.search');
        $rs = $parser->parse($rets, $response, $parameters);

        return new LiveResults($rs);
    }
}
