<?php namespace PHRETS\Http;

use GuzzleHttp\Client as GuzzleClient;

class Client
{
    protected static $client;

    /**
     * @return GuzzleClient
     */
    public static function make()
    {
        if (!self::$client) {
            self::$client = new GuzzleClient;
        }

        return self::$client;
    }

    public static function set(GuzzleClient $client)
    {
        self::$client = $client;
    }
}
