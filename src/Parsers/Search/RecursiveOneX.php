<?php namespace PHRETS\Parsers\Search;

use GuzzleHttp\Message\ResponseInterface;
use PHRETS\Exceptions\AutomaticPaginationError;
use PHRETS\Models\Search\Results;
use PHRETS\Session;

class RecursiveOneX
{
    public function parse(Session $rets, ResponseInterface $response, $parameters)
    {
        // we're given the first response automatically, so parse this and start the recursion

        /** @var \PHRETS\Parsers\Search\OneX $parser */
        $parser = $rets->getConfiguration()->getStrategy()->provide('parser.search');
        $rs = $parser->parse($rets, $response, $parameters);

        while ($this->continuePaginating($rets, $parameters, $rs)) {
            $pms = $parameters;

            $rets->debug("Continuing pagination...");
            $rets->debug("Current count collected already: " . $rs->count());

            $resource = $pms['SearchType'];
            $class = $pms['Class'];
            $query = (array_key_exists('Query', $pms)) ? $pms['Query'] : null;

            $pms['Offset'] = $this->getNewOffset($rets, $parameters, $rs);

            unset($pms['SearchType']);
            unset($pms['Class']);
            unset($pms['Query']);

            /** @var Results $inner_rs */
            $inner_rs = $rets->Search($resource, $class, $query, $pms, false);
            $rs->setTotalResultsCount($inner_rs->getTotalResultsCount());
            $rs->setMaxRowsReached($inner_rs->isMaxRowsReached());

            // test if we're actually paginating
            if ($this->isPaginationBroken($rs, $inner_rs)) {
                throw new AutomaticPaginationError("Automatic pagination doesn't not appear to be supported by the server");
            }

            foreach ($inner_rs as $ir) {
                $rs->addRecord($ir);
            }
        }

        return $rs;
    }

    /**
     * @param Session $rets
     * @param $parameters
     * @param Results $rs
     * @return bool
     */
    protected function continuePaginating(Session $rets, $parameters, Results $rs)
    {
        return $rs->isMaxRowsReached();
    }

    /**
     * @param Session $rets
     * @param $parameters
     * @param Results $rs
     * @return int
     */
    protected function getNewOffset(Session $rets, $parameters, Results $rs)
    {
        return $rs->getReturnedResultsCount() + 1;
    }

    /**
     * @param Results $big
     * @param Results $small
     * @return bool
     */
    protected function isPaginationBroken(Results $big, Results $small)
    {
        return $big->first()->toArray() == $small->first()->toArray();
    }
}
