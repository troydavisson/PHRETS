<?php namespace PHRETS\Models\Metadata;

abstract class Base implements \ArrayAccess
{
    /** @var \PHRETS\Session */
    protected $session;
    protected $elements = [];
    protected $attributes = [];
    protected $values = [];

    /**
     * @return \PHRETS\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param mixed $session
     * @return $this
     */
    public function setSession($session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * @param $name
     * @param array $args
     * @return $this|mixed|null
     */
    public function __call($name, $args = [])
    {
        if (preg_match('/^set/', strtolower($name))) {
            foreach (array_merge($this->getXmlElements(), $this->getXmlAttributes()) as $attr) {
                if (strtolower('set' . $attr) == strtolower($name)) {
                    $this->values[$attr] = $args[0];
                    break;
                }
            }
            return $this;
        } elseif (preg_match('/^get/', strtolower($name))) {
            foreach (array_merge($this->getXmlElements(), $this->getXmlAttributes()) as $attr) {
                if (strtolower('get' . $attr) == strtolower($name)) {
                    return \array_get($this->values, $attr);
                }
            }
            return null;
        }

        throw new \BadMethodCallException;
    }

    /**
     * @return array
     */
    public function getXmlElements()
    {
        return $this->elements;
    }

    /**
     * @return array
     */
    public function getXmlAttributes()
    {
        return $this->attributes;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        foreach (array_merge($this->getXmlElements(), $this->getXmlAttributes()) as $attr) {
            if (strtolower($attr) == strtolower($offset)) {
                return true;
            }
        }
        return false;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        foreach (array_merge($this->getXmlElements(), $this->getXmlAttributes()) as $attr) {
            if (strtolower($attr) == strtolower($offset)) {
                return \array_get($this->values, $attr);
            }
        }
        return null;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->values[$offset] = $value;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->values[$offset]);
        }
    }
}
