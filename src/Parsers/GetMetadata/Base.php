<?php namespace PHRETS\Parsers\GetMetadata;

class Base
{
    protected function loadFromAttributes($model, $xml)
    {
        foreach ($model->attributes as $attr) {
            if (isset($xml->$attr)) {
                $method = 'set' . $attr;
                $model->$method((string)$xml->$attr);
            }
        }
        return $model;
    }
}
