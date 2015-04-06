<?php namespace PHRETS\Parsers\Search;

use GuzzleHttp\Message\ResponseInterface;
use PHRETS\Models\Search\Record;
use PHRETS\Models\Search\Results;
use PHRETS\Session;

class OneX
{
    public function parse(Session $rets, ResponseInterface $response, $parameters)
    {
        $xml = $response->xml();

        $rs = new Results;
        $rs->setSession($rets)
            ->setResource($parameters['SearchType'])
            ->setClass($parameters['Class']);

        if ($this->getRestrictedIndicator($rets, $xml, $parameters)) {
            $rs->setRestrictedIndicator($this->getRestrictedIndicator($rets, $xml, $parameters));
        }

        $rs->setHeaders($this->getColumnNames($rets, $xml, $parameters));
        $rets->debug(count($rs->getHeaders()) . ' column headers/fields given');

        $this->parseRecords($rets, $xml, $parameters, $rs);

        if ($this->getTotalCount($rets, $xml, $parameters) !== null) {
            $rs->setTotalResultsCount($this->getTotalCount($rets, $xml, $parameters));
            $rets->debug($rs->getTotalResultsCount() . ' total results found');
        }
        $rets->debug($rs->getReturnedResultsCount() . ' results given');

        if ($this->foundMaxRows($rets, $xml, $parameters)) {
            // MAXROWS tag found.  the RETS server withheld records.
            // if the server supports Offset, more requests can be sent to page through results
            // until this tag isn't found anymore.
            $rs->setMaxRowsReached();
            $rets->debug('Maximum rows returned in response');
        }

        unset($xml);

        return $rs;
    }

    /**
     * @param Session $rets
     * @param $xml
     * @param $parameters
     * @return string
     */
    protected function getDelimiter(Session $rets, $xml, $parameters)
    {
        if (isset($xml->DELIMITER)) {
            // delimiter found so we have at least a COLUMNS row to parse
            return chr("{$xml->DELIMITER->attributes()->value}");
        } else {
            // assume tab delimited since it wasn't given
            $rets->debug('Assuming TAB delimiter since none specified in response');
            return chr("09");
        }
    }

    /**
     * @param Session $rets
     * @param $xml
     * @param $parameters
     * @return string|null
     */
    protected function getRestrictedIndicator(Session $rets, &$xml, $parameters)
    {
        if (array_key_exists('RestrictedIndicator', $parameters)) {
            return $parameters['RestrictedIndicator'];
        } else {
            return null;
        }
    }

    protected function getColumnNames(Session $rets, &$xml, $parameters)
    {
        $delim = $this->getDelimiter($rets, $xml, $parameters);

        // break out and track the column names in the response
        $column_names = "{$xml->COLUMNS[0]}";

        // parse and return the rest
        return array_slice(explode($delim, $column_names), 1, -1);
    }

    protected function parseRecords(Session $rets, &$xml, $parameters, Results $rs)
    {
        if (isset($xml->DATA)) {
            foreach ($xml->DATA as $line) {
                $rs->addRecord($this->parseRecordFromLine($rets, $xml, $parameters, $line, $rs));
            }
        }
    }

    protected function parseRecordFromLine(Session $rets, &$xml, $parameters, &$line, Results $rs)
    {
        $delim = $this->getDelimiter($rets, $xml, $parameters);

        $r = new Record;
        $field_data = (string)$line;

        // split up DATA row on delimiter found earlier
        $field_data = array_slice(explode($delim, $field_data), 1, -1);

        foreach ($rs->getHeaders() as $key => $name) {
            // assign each value to it's name retrieved in the COLUMNS earlier
            $r->set($name, $field_data[$key]);
        }
        return $r;
    }

    protected function getTotalCount(Session $rets, &$xml, $parameters)
    {
        if (isset($xml->COUNT)) {
            return (int)"{$xml->COUNT->attributes()->Records}";
        } else {
            return null;
        }
    }

    protected function foundMaxRows(Session $rets, &$xml, $parameters)
    {
        return isset($xml->MAXROWS);
    }
}
