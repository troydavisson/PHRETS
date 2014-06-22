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

        if (array_key_exists('RestrictedIndicator', $parameters)) {
            $rs->setRestrictedIndicator($parameters['RestrictedIndicator']);
        }

        if (isset($xml->DELIMITER)) {
            // delimiter found so we have at least a COLUMNS row to parse
            $delimiter_character = chr("{$xml->DELIMITER->attributes()->value}");
        } else {
            // assume tab delimited since it wasn't given
            $delimiter_character = chr("09");
        }

        // break out and track the column names in the response
        $column_names = "{$xml->COLUMNS[0]}";
        $column_names = preg_replace("/^{$delimiter_character}/", "", $column_names);
        $column_names = preg_replace("/{$delimiter_character}\$/", "", $column_names);
        $rs->setHeaders(explode($delimiter_character, $column_names));

        if (isset($xml->DATA)) {
            foreach ($xml->DATA as $field_data) {
                $r = new Record;

                $field_data = (string)$field_data;

                // split up DATA row on delimiter found earlier
                $field_data = preg_replace("/^{$delimiter_character}/", "", $field_data);
                $field_data = preg_replace("/{$delimiter_character}\$/", "", $field_data);
                $field_data = explode($delimiter_character, $field_data);

                foreach ($rs->getHeaders() as $key => $name) {
                    // assign each value to it's name retrieved in the COLUMNS earlier
                    $r->set($name, $field_data[$key]);
                }

                $rs->addRecord($r);
            }
        }

        if (isset($xml->COUNT)) {
            // found the record count returned.  save it
            $rs->setTotalResultsCount((int)"{$xml->COUNT->attributes()->Records}");
        }

        if (isset($xml->MAXROWS)) {
            // MAXROWS tag found.  the RETS server withheld records.
            // if the server supports Offset, more requests can be sent to page through results
            // until this tag isn't found anymore.
            $rs->setMaxRowsReached();
        }

        if (isset($xml)) {
            unset($xml);
        }

        return $rs;
    }
}
