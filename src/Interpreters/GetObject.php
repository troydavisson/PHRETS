<?php namespace PHRETS\Interpreters;

class GetObject
{

    /**
     * @param $content_ids
     * @param $object_ids
     * @returns array
     */
    public static function ids($content_ids, $object_ids)
    {
        $result = [];

        $content_ids = self::split($content_ids);
        $object_ids = self::split($object_ids);

        foreach ($content_ids as $cid) {
            $result[] = $cid . ':' . implode(':', $object_ids);
        }

        return $result;
    }

    /**
     * @param $value
     * @return array
     */
    protected static function split($value)
    {
        if (!is_array($value)) {
            if (preg_match('/\:/', $value)) {
                $value = array_map('trim', explode(':', $value));
            } elseif (preg_match('/\,/', $value)) {
                $value = array_map('trim', explode(',', $value));
            } elseif (preg_match('/(\d+)\-(\d+)/', $value, $matches)) {
                $value = range($matches[1], $matches[2]);
            } else {
                $value = [$value];
            }
        }

        return $value;
    }
}
