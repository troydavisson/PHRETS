<?php

class SessionIntegrationTest extends BaseIntegration
{
    /** @test * */
    public function it_logs_in()
    {
        $connect = $this->session->Login();
        $this->assertTrue($connect instanceof \PHRETS\Models\Bulletin);
    }
}
