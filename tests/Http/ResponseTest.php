<?php

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase {

    /** @test **/
    public function it_creates_valid_xml()
    {
        $body = "<?xml version='1.0' encoding='UTF-8'?><guestbook><guest><fname>First Name</fname><lname>Last Name</lname></guest></guestbook>";
        $guzzleResponse = new GuzzleHttp\Psr7\Response(200, ['X-Foo' => 'Bar'], $body);

        $response = new PHRETS\Http\Response($guzzleResponse);

        $this->assertEquals(1, $response->xml()->count());
    }

    /** @test **/
    public function it_creates_valid_xml_with_new_lines()
    {
        $body = "\n\n\r<?xml version='1.0' encoding='UTF-8'?><guestbook><guest><fname>First Name</fname><lname>Last Name</lname></guest></guestbook>\r\n\n";
        $guzzleResponse = new GuzzleHttp\Psr7\Response(200, ['X-Foo' => 'Bar'], $body);

        $response = new PHRETS\Http\Response($guzzleResponse);

        $this->assertEquals(1, $response->xml()->count());
    }
}
