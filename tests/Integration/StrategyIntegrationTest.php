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

    /** @test */
    public function it_supports_custom_xml_parser()
    {
        $this->session->Login();

        /**
         * set a custom parser
         */
        $this->session->setParser(
            \PHRETS\Strategies\Strategy::PARSER_XML,
            new CustomXMLParser()
        );

        /** @var PHRETS\Models\Search\Results $results */
        $results = $this->session->Search('Property', 'A', '*', ['Limit' => 3, 'Select' => ['LIST_1', 'LIST_105']]);
        $this->assertContains('LIST_10000', $results->getHeaders());
        $this->assertNotContains('LIST_1', $results->getHeaders());
    }
}
