<?php

use Crawler\Service\Crawler;
use Crawler\Service\Repository;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;

class CrawlerTest extends PHPUnit\Framework\TestCase
{
    private $repo;
    public function setUp(): void
    {
        $db = new SQLite3(':memory:');
        $db->exec("CREATE TABLE IF NOT EXISTS page_links (page TEXT, parent_page TEXT, domain TEXT)");
        $this->repo = new Repository($db);
    }

    public function testCrawlNoDomain()
    {
        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $httpClient = $this->getMockBuilder(\GuzzleHttp\Client::class)->disableOriginalConstructor()->getMock();
        $service = new Crawler($logger, $httpClient, $this->repo);
        $service->setDomain('');
        try {
            $service->crawl('');
            $this->fail();
        } catch (Exception $ex) {
            $this->assertEquals("No domain specified", $ex->getMessage());
        }
    }

    public function testCrawlBlankPath()
    {
        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $httpClient = $this->getMockBuilder(\GuzzleHttp\Client::class)->disableOriginalConstructor()->getMock();
        $service = new Crawler($logger, $httpClient, $this->repo);
        $service->setDomain('http://www.dominicorme.me.uk');
        try {
            $service->crawl('http://www.example.com');
            $links = $service->getLinks();
            $this->assertEmpty($links);
        } catch (Exception $ex) {
            $this->fail();
        }
    }

    public function testCrawlRelativePathNoLinks()
    {
        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $mockResponses = new \GuzzleHttp\Handler\MockHandler([
            new Response(200, [], "<div></div>")
        ]);
        $handlerStack = \GuzzleHttp\HandlerStack::create($mockResponses);
        $httpClient = new Client(['handler' => $handlerStack]);
        $service = new Crawler($logger, $httpClient, $this->repo);
        $service->setDomain('http://www.dominicorme.me.uk');
        try {
            $service->crawl('/x.ext');
            $links = $service->getLinks();
            $this->assertEmpty($links, "Was not empty. Got: ".print_r($links, true));
        } catch (Exception $ex) {
            $this->fail();
        }
    }

    public function testCrawlRelativePathOneLink()
    {
        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $mockResponses = new \GuzzleHttp\Handler\MockHandler([
            new Response(200, [], "<div href='/foo.htm'></div>"),
            new Response(200, [], "<div href='/bar.xml'></div>")
        ]);
        $handlerStack = \GuzzleHttp\HandlerStack::create($mockResponses);
        $httpClient = new Client(['handler' => $handlerStack]);

        $service = new Crawler($logger, $httpClient, $this->repo);
        $service->setDomain('http://www.dominicorme.me.uk');
        try {
            $service->crawl('/x.htm');
            $links = $service->getLinks();
            $this->assertNotEmpty($links);
            $expected = [
                [
                    'page' => 'http://www.dominicorme.me.uk/x.htm',
                    'parent_page' => ''
                ],
                [
                    'page' => 'http://www.dominicorme.me.uk/foo.htm',
                    'parent_page' => 'http://www.dominicorme.me.uk/x.htm'
                ]
            ];
            $this->assertEquals($expected, $links);
        } catch (Exception $ex) {
            $this->fail($ex->getMessage());
        }
    }
}
