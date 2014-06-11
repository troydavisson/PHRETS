<?php

class SessionIntegrationTest extends PHPUnit_Framework_TestCase
{
    protected $client;
    /** @var \PHRETS\Session */
    protected $session;

    public function setUp()
    {
        $client = new GuzzleHttp\Client;
        $watcher = new Gsaulmon\GuzzleRecorder\GuzzleRecorder(__DIR__ . '/Fixtures/Http');
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

    /** @test * */
    public function it_logs_in()
    {
        $connect = $this->session->Login();
        $this->assertTrue($connect instanceof \PHRETS\Models\Bulletin);
    }
}
