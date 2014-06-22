<?php namespace PHRETS\Models\Metadata;

abstract class Base
{
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

    public function getXmlElements()
    {
        return $this->elements;
    }

    public function getXmlAttributes()
    {
        return $this->attributes;
    }
}
