<?php namespace PHRETS\Interpreters;

class Search
{
    public static function dmql($query)
    {
        // automatically surround the given query with parentheses if it doesn't have them already
        if (!empty($query) and $query != "*" and !preg_match('/^\((.*)\)$/', $query)) {
            $query = '(' . $query . ')';
        }

        return $query;
    }
}
