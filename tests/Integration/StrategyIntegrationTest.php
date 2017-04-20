<?php

class StrategyIntegrationTest extends BaseIntegration
{
    /** @test */
    public function it_supports_custom_parsers()
    {
        $this->session->Login();

        /**
         * set a custom parser
         */
        $this->session->setParser(
            \PHRETS\Strategies\Strategy::PARSER_METADATA_SYSTEM,
            new CustomSystemParser
        );

        $system = $this->session->GetSystemMetadata();

        $this->assertEquals('custom', $system->getSystemID());
    }
}
