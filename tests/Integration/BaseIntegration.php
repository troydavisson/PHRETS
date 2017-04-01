<?php

use Dshafik\GuzzleHttp\VcrHandler;

class BaseIntegration extends PHPUnit_Framework_TestCase
{

    protected $client;
    /** @var \PHRETS\Session */
    protected $session;
    protected $search_select = [
        'LIST_0',
        'LIST_1',
        'LIST_5',
        'LIST_106',
        'LIST_105',
        'LIST_15',
        'LIST_22',
        'LIST_10',
        'LIST_30',
    ];

    public function tearDown()
    {
        // reset the client, just to be safe
        $client = new \GuzzleHttp\Client();
        PHRETS\Http\Client::set($client);
    }

    protected function play($cassette, \PHRETS\Configuration $config = null)
    {
        if (strpos($cassette, '.json') === false) {
            $cassette .= '.json';
        }
        $vcr = VcrHandler::turnOn(FIXTUREDIR . '/' . $cassette);

        $client = new \GuzzleHttp\Client(['handler' => $vcr]);
        PHRETS\Http\Client::set($client);

        if (!$config) {
            $config = new \PHRETS\Configuration;
            $config->setLoginUrl('http://retsgw.flexmls.com/rets2_1/Login')
                ->setUsername(getenv('PHRETS_TESTING_USERNAME'))
                ->setPassword(getenv('PHRETS_TESTING_PASSWORD'))
                ->setRetsVersion('1.7.2')
                ->setUserAgent('PHRETS/2.0');
        }

        $this->session = new PHRETS\Session($config);
    }
}
