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
}
