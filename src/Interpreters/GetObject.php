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

        $content_ids = self::split($content_ids, false);
        $object_ids = self::split($object_ids);

        foreach ($content_ids as $cid) {
            $result[] = $cid . ':' . implode(':', $object_ids);
        }

        return $result;
    }

    /**
     * @param $value
     * @param bool $dash_ranges
     * @return array
     */
    protected static function split($value, $dash_ranges = true)
    {
        if (!is_array($value)) {
            if (preg_match('/\:/', $value)) {
                $value = array_map('trim', explode(':', $value));
            } elseif (preg_match('/\,/', $value)) {
                $value = array_map('trim', explode(',', $value));
            } elseif ($dash_ranges and preg_match('/(\d+)\-(\d+)/', $value, $matches)) {
                $value = range($matches[1], $matches[2]);
            } else {
                $value = [$value];
            }
        }

        return $value;
    }
}
