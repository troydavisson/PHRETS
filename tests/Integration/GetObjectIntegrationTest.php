<?php

class GetObjectIntegrationTest extends PHPUnit_Framework_TestCase
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

    /** @test */
    public function it_fetches_objects()
    {
        $objects = $this->session->GetObject('Property', 'Photo', '00-1669', '*', 0);
        $this->assertTrue($objects instanceof \Illuminate\Support\Collection);
        $this->assertSame(2, $objects->count());
    }

    /** @test */
    public function it_fetches_primary_object()
    {
        $objects = $this->session->GetObject('Property', 'Photo', '00-1669', 0, 1);
        $this->assertTrue($objects instanceof \Illuminate\Support\Collection);
        $this->assertSame(1, $objects->count());

        $primary = $objects->first();

        $object = $this->session->GetPreferredObject('Property', 'Photo', '00-1669', 1);
        $this->assertTrue($object instanceof \PHRETS\Models\Object);
        $this->assertEquals($primary, $object);
    }

    /** @test **/
    public function it_sees_primary_as_preferred()
    {
        $object = $this->session->GetPreferredObject('Property', 'Photo', '00-1669', 1);
        $this->assertTrue($object->isPreferred());
    }
}
