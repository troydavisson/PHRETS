<?php namespace PHRETS\Models;

class Bulletin
{
    protected $body = null;

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->body;
    }
}
