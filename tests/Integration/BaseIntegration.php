<?php

class BaseIntegration extends PHPUnit_Framework_TestCase
{

    protected $client;
    /** @var \PHRETS\Session */
    protected $session;
    protected $search_select = [
        'LIST_0', 'LIST_1', 'LIST_5', 'LIST_106', 'LIST_105', 'LIST_15', 'LIST_22', 'LIST_10', 'LIST_30'
    ];

    public function setUp()
    {
        $client = new GuzzleHttp\Client;
        $watcher = new Gsaulmon\GuzzleRecorder\GuzzleRecorder(__DIR__ . '/Fixtures/Http');
        $watcher->addIgnoredHeader('Accept');
        $watcher->addIgnoredHeader('Cookie');

        $client->getEmitter()->attach($watcher);
        \PHRETS\Http\Client::set($client);

        $config = new \PHRETS\Configuration;
        $config->setLoginUrl('http://retsgw.flexmls.com/rets2_1/Login')
                ->setUsername(getenv('PHRETS_TESTING_USERNAME'))
                ->setPassword(getenv('PHRETS_TESTING_PASSWORD'))
                ->setRetsVersion('1.7.2');

        $this->session = new PHRETS\Session($config);
        $this->session->Login();
    }
}
