<?php

class SessionIntegrationTest extends BaseIntegration
{
    /** @test * */
    public function it_logs_in()
    {
        $connect = $this->session->Login();
        $this->assertTrue($connect instanceof \PHRETS\Models\Bulletin);
    }

    /** @test **/
    public function it_made_the_request()
    {
        $connect = $this->session->Login();
        $this->assertSame('http://retsgw.flexmls.com:80/rets2_1/Login', $this->session->getLastRequestURL());
    }

    /** @test **/
    public function it_throws_an_exception_when_making_a_bad_request()
    {
        $this->session->Login();
        $this->setExpectedException('\PHRETS\Exceptions\RETSException', null, 20203);

        $this->session->Search('Property', 'Z', '*'); // no such class by that name
    }

    /** @test **/
    public function it_tracks_the_last_response_body()
    {
        $this->session->Login();

        // find something in the login response that we can count on
        $this->assertRegExp('/NotificationFeed/', $this->session->getLastResponse());
    }

    /** @test **/
    public function it_disconnects()
    {
        $this->session->Login();

        $this->assertTrue($this->session->Disconnect());
    }

    /** @test **/
    public function it_requests_the_servers_action_transaction()
    {
        $config = new \PHRETS\Configuration;

        // this endpoint doesn't actually exist, but the response is mocked, so...
        $config->setLoginUrl('http://retsgwaction.flexmls.com/rets2_1/Login')
                ->setUsername(getenv('PHRETS_TESTING_USERNAME'))
                ->setPassword(getenv('PHRETS_TESTING_PASSWORD'))
                ->setRetsVersion('1.7.2');

        $session = new \PHRETS\Session($config);
        $bulletin = $session->Login();

        $this->assertInstanceOf('\PHRETS\Models\Bulletin', $bulletin);
        $this->assertRegExp('/found an Action/', $bulletin->getBody());
    }

    /** @test **/
    public function it_uses_http_post_method_when_desired()
    {
        $config = new \PHRETS\Configuration;

        // this endpoint doesn't actually exist, but the response is mocked, so...
        $config->setLoginUrl('http://retsgw.flexmls.com/rets2_1/Login')
                ->setUsername(getenv('PHRETS_TESTING_USERNAME'))
                ->setPassword(getenv('PHRETS_TESTING_PASSWORD'))
                ->setRetsVersion('1.7.2')
                ->setOption('use_post_method', true);

        $session = new \PHRETS\Session($config);
        $session->Login();

        $system = $session->GetSystemMetadata();
        $this->assertSame('demomls', $system->getSystemId());

        $results = $session->Search('Property', 'A', '*', ['Limit' => 1, 'Select' => 'LIST_1']);
        $this->assertCount(1, $results);
    }
}
