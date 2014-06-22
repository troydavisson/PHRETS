<?php namespace PHRETS\Models\Search;

use Closure;
use Illuminate\Support\Collection;
use Countable;
use ArrayAccess;
use IteratorAggregate;

class Results implements Countable, ArrayAccess, IteratorAggregate
{
    protected $resource;
    protected $class;
    /** @var \PHRETS\Session */
    protected $session;
    protected $metadata = null;
    protected $total_results_count = 0;
    protected $returned_results_count = 0;
    protected $error = null;
    /** @var \Illuminate\Support\Collection */
    protected $results;
    protected $headers = [];
    protected $restricted_indicator = '****';
    protected $maxrows_reached = false;

    public function __construct()
    {
        $this->results = new Collection;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    public function addRecord(Record $record, $keyed_by = null)
    {
        // register this Results object as the record's parent automatically
        $record->setParent($this);

        $this->returned_results_count++;

        if (is_callable($keyed_by)) {
            $this->results->put($keyed_by($record), $record);
        } elseif ($keyed_by) {
            $this->results->put($record->get($keyed_by), $record);
        } else {
            $this->results->push($record);
        }
    }

    public function keyResultsBy($field)
    {
        $results = clone $this->results;
        $this->results = new Collection;
        foreach ($results as $r) {
            $this->addRecord($r, $field);
        }
    }

    /**
     * Grab a record by it's tracked key
     *
     * @param $key_id
     * @return Record
     */
    public function find($key_id)
    {
        return $this->results->get($key_id);
    }

    /**
     * @return null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param null $error
     * @return $this
     */
    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return int
     */
    public function getReturnedResultsCount()
    {
        return $this->returned_results_count;
    }

    /**
     * @param int $returned_results_count
     * @return $this
     */
    public function setReturnedResultsCount($returned_results_count)
    {
        $this->returned_results_count = $returned_results_count;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalResultsCount()
    {
        return $this->total_results_count;
    }

    /**
     * @param int $total_results_count
     * @return $this
     */
    public function setTotalResultsCount($total_results_count)
    {
        $this->total_results_count = $total_results_count;
        return $this;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $class
     * @return $this
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param string $resource
     * @return $this
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return \PHRETS\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param \PHRETS\Session $session
     * @return $this
     */
    public function setSession($session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * @return null
     */
    public function getMetadata()
    {
        if (!$this->metadata) {
            $this->metadata = $this->session->GetTableMetadata($this->getResource(), $this->getClass());
        }
        return $this->metadata;
    }

    /**
     * @param null $metadata
     * @return $this
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return string
     */
    public function getRestrictedIndicator()
    {
        return $this->restricted_indicator;
    }

    /**
     * @param $indicator
     * @return $this
     */
    public function setRestrictedIndicator($indicator)
    {
        $this->restricted_indicator = $indicator;
        return $this;
    }

    public function getIterator()
    {
        return $this->results->getIterator();
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->offsetExists($offset);
    }

    /**
     * @param mixed $offset
     * @return Record|null
     */
    public function offsetGet($offset)
    {
        return $this->results->offsetGet($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->results->offsetSet($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->results->offsetUnset($offset);
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->results->count();
    }

    /**
     * @param callable $callback
     * @param null $default
     * @return Record|null
     */
    public function first(Closure $callback = null, $default = null)
    {
        return $this->results->first($callback, $default);
    }

    /**
     * @return Record|null
     */
    public function last()
    {
        return $this->results->last();
    }

    /**
     * @return bool
     */
    public function isMaxRowsReached()
    {
        return ($this->maxrows_reached == true);
    }

    /**
     * @return $this
     */
    public function setMaxRowsReached()
    {
        $this->maxrows_reached = true;
        return $this;
    }
}
