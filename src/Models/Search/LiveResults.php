<?php namespace PHRETS\Models\Search;

use League\Csv\Writer;
use SplTempFileObject;

class LiveResults implements \Iterator
{
    protected $items = null;
    protected $pointer;
    protected $count;

    public function __construct(Results $results)
    {
        $this->items = $results;

        $this->pointer = 0;
        $this->count = 0;
    }

    public function current()
    {
        $this->count++;
        return $this->items[$this->pointer];
    }

    public function key()
    {
        return $this->pointer;
    }

    public function next()
    {
        $this->pointer++;
    }

    public function rewind()
    {
        $this->pointer = 0;
    }

    public function valid()
    {
        $check = isset( $this->items[ $this->pointer ] );

        if (!$check and $this->items->isMaxRowsReached()) {
            // reached the end of this page but there's more to get
            $this->items->getSession()->debug('Continuing pagination...');
            $this->items->getSession()->debug('Current count collected already: ' . $this->count);

            $parameters = $this->items->getOptionalParameters();

            $parameters['Offset'] = array_get($parameters, 'Offset', 1) + $this->items->getReturnedResultsCount();

            $session = $this->items->getSession();
            $this->items = $session->Search(
                $this->items->getResource(),
                $this->items->getClass(),
                $this->items->getDmqlQuery(),
                $parameters
            );

            $this->pointer = 0;

            return $this->items->count() > 0;
        }

        return $check;
    }

    public function first(\Closure $callback = null, $default = null)
    {
        return $this->items->first($callback, $default);
    }

    public function lists($field)
    {
        $l = [];
        foreach ($this as $r) {
            $v = $r->get($field);
            if ($v and !$r->isRestricted($field)) {
                $l[] = $v;
            }
        }
        return $l;
    }

    public function toCSV()
    {
        // create a temporary file so we can write the CSV out
        $writer = Writer::createFromFileObject(new SplTempFileObject);

        $headers = $this->items->getHeaders();

        // add the header line
        $writer->insertOne($headers);

        // go through each record
        foreach ($this as $r) {
            $record = [];

            // go through each field and ensure that each record is prepared in an order consistent with the headers
            foreach ($headers as $h) {
                $record[] = $r->get($h);
            }
            $writer->insertOne($record);
        }

        // return as a string
        return (string) $writer;
    }

    public function toArray()
    {
        $return = [];
        foreach ($this as $r) {
            $return[] = $r->toArray();
        }
        return $return;
    }

    public function toJSON()
    {
        $return = [];

        foreach ($this as $row) {
            $return[] = $row->toArray();
        }

        return json_encode($return);
    }
}
