<?php namespace PHRETS\Models\Search;

class Record implements \ArrayAccess
{
    protected $resource;
    protected $class;
    protected $fields = [];
    protected $restricted_value = '****';
    protected $values = [];

    /**
     * @param $field
     * @return string|null
     */
    public function get($field)
    {
        return (array_key_exists((string)$field, $this->values)) ? $this->values[(string)$field] : null;
    }

    /**
     * @param $field
     * @param $value
     */
    public function set($field, $value)
    {
        $this->values[(string)$field] = $value;
    }

    /**
    * @param $field
    */
    public function remove($field)
    {
      unset($this->values[(string)$field]);
    }

    /**
     * @param $field
     * @return bool
     */
    public function isRestricted($field)
    {
        $val = $this->get($field);
        return ($val == $this->restricted_value);
    }

    /**
     * @param Results $results
     * @return $this
     */
    public function setParent(Results $results)
    {
        $this->resource = $results->getResource();
        $this->class = $results->getClass();
        $this->restricted_value = $results->getRestrictedIndicator();
        $this->fields = $results->getHeaders();
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
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->values;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->values);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->values);
    }

    /**
     * @param mixed $offset
     * @return null|string
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        if (array_key_exists($offset, $this->values)) {
            unset($this->values[$offset]);
        }
    }
}
