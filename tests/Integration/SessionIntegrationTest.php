<?php

use GuzzleHttp\Middleware;

class SessionIntegrationTest extends BaseIntegration
{
    /** @test * */
    public function it_logs_in()
    {
        $this->play('Login/standard_1.7.2');
        $connect = $this->session->Login();

        $this->assertTrue($connect instanceof \PHRETS\Models\Bulletin);
    }

    /** @test **/
    public function it_made_the_request()
    {
        $this->play('Login/standard_1.7.2');
        $this->session->Login();

        $this->assertSame('http://retsgw.flexmls.com/rets2_1/Login', $this->session->getLastRequestURL());
    }

    /** @test **/
    public function it_tracks_the_last_response_body()
    {
        $this->play('Login/standard_1.7.2');
        $this->session->Login();

        // find something in the login response that we can count on
        $this->assertRegExp('/NotificationFeed/', $this->session->getLastResponse());
    }

    /** @test **/
    public function it_disconnects()
    {
        $this->play('Login/it_disconnects');
        $this->session->Login();

        $this->assertTrue($this->session->Disconnect());
    }

    /** @test **/
    public function it_requests_the_servers_action_transaction()
    {
        $this->play('Login/it_requests_the_servers_action_transaction');
        $bulletin = $this->session->Login();

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

        $this->play('Login/it_uses_http_post_method_when_desired', $config);
        $bulletin = $this->session->Login();

        $system = $this->session->GetSystemMetadata();
        $this->assertSame('demomls', $system->getSystemId());

        $results = $this->session->Search('Property', 'A', '*', ['Limit' => 1, 'Select' => 'LIST_1']);
        $this->assertCount(1, $results);
    }

    /** @test **/
    public function it_tracks_a_given_session_id()
    {
        $this->play('Login/it_tracks_a_given_session_id');
        $bulletin = $this->session->Login();

        // mocked request to give back a session ID
        $this->session->GetTableMetadata('Property', 'A');

        $this->assertSame('21AC8993DC98DDCE648423628ECF4AB5', $this->session->getRetsSessionId());
    }

    /** @test **/
    public function it_detects_when_to_use_user_agent_authentication()
    {
        $config = new \PHRETS\Configuration;

        $config->setLoginUrl('http://retsgw.flexmls.com/rets2_1/Login')
                ->setUsername(getenv('PHRETS_TESTING_USERNAME'))
                ->setPassword(getenv('PHRETS_TESTING_PASSWORD'))
                ->setUserAgent('PHRETS/2.0')
                ->setUserAgentPassword('bogus_password')
                ->setRetsVersion('1.7.2');

        $this->play('Login/it_detects_when_to_use_user_agent_authentication', $config);

        /**
         * Attach a history container to Guzzle so we can verify the needed header is sent
         */
        $container = [];
        /** @var \GuzzleHttp\HandlerStack $stack */
        $stack = $this->session->getClient()->getConfig('handler');
        $history = Middleware::history($container);
        $stack->push($history);

        $this->session->Login();

        $this->assertCount(1, $container);
        $last_request = $container[count($container) - 1];
        $this->assertRegExp('/Digest/', implode(', ', $last_request['request']->getHeader('RETS-UA-Authorization')));
        $this->assertArrayHasKey('Accept', $last_request['request']->getHeaders());
    }

    /**
     * @test
     * @expectedException \PHRETS\Exceptions\CapabilityUnavailable
     **/
    public function it_doesnt_allow_requests_to_unsupported_capabilities()
    {
        $this->play('Login/it_doesnt_allow_requests_to_unsupported_capabilities');
        $bulletin = $this->session->Login();

        // make a request for metadata to a server that doesn't support metadata
        $this->session->GetSystemMetadata();
    }

    public function testDetailsAreAvailableFromLogin()
    {
        $this->play('Login/testDetailsAreAvailableFromLogin');
        $connect = $this->session->Login();

        $this->assertTrue($connect instanceof \PHRETS\Models\Bulletin);

        $this->assertSame('UNKNOWN', $connect->getMemberName());
        $this->assertNotNull($connect->getMetadataVersion());
    }
}
