<?php

namespace Tests\Unit;

use App\Services\ParserService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParserServiceTest extends TestCase
{

    protected ParserService $parserService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parserService = new ParserService();
    }

    public function testCanExtractAccentedTitleAndMultipleAuthors()
    {
        $html = file_get_contents(__DIR__ . '/Fixtures/multiple_authors_accented_title.html');
        Http::fake([
            'https://example.com/page' => Http::response($html, 200),
        ]);

        $links = ['https://example.com/page'];
        $result = $this->parserService->extractTitleAndAuthors($links);

        $this->assertArrayHasKey('https://example.com/page', $result);
        $this->assertEquals('Communiques from the Interministerial Group for Education', $result['https://example.com/page']['title']);
        $this->assertEquals(
            [
                'Department for Education',
                'The Scottish Government',
                'Welsh Government',
                'Department of Education (Northern Ireland)',
                'Department for the Economy (Northern Ireland)',
                'The Rt Hon Michael Gove MP'
            ],
            $result['https://example.com/page']['authors']
        );
    }

    public function testCanExtractSingleAuthor()
    {
        $html = file_get_contents(__DIR__ . '/Fixtures/one_author.html');
        Http::fake([
            'https://example.com/page' => Http::response($html, 200),
        ]);

        $links = ['https://example.com/page'];
        $result = $this->parserService->extractTitleAndAuthors($links);

        $this->assertArrayHasKey('https://example.com/page', $result);
        $this->assertEquals('Online Safety Bill: supporting documents', $result['https://example.com/page']['title']);
        $this->assertEquals(
            ['Department for Digital, Culture, Media & Sport'],
            $result['https://example.com/page']['authors']
        );
    }

    public function testCanExtractMultipleAuthors()
    {
        $html = file_get_contents(__DIR__ . '/Fixtures/two_authors.html');
        Http::fake([
            'https://example.com/page' => Http::response($html, 200),
        ]);

        $links = ['https://example.com/page'];
        $result = $this->parserService->extractTitleAndAuthors($links);

        $this->assertArrayHasKey('https://example.com/page', $result);
        $this->assertEquals('The Excise Duties (Northern Ireland etc. miscellaneous modifications and amendments) (EU Exit) Regulations 2022', $result['https://example.com/page']['title']);
        $this->assertEquals(
            [
                'HM Revenue & Customs',
                'Academy for Justice Commissioning'
            ],
            $result['https://example.com/page']['authors']
        );
    }
    public function testCanHandleFailedRequest()
    {
        Http::fake([
            'https://example.com/page' => Http::response('', 500),
        ]);

        $pages = ['https://example.com/pag1'];
        $result = $this->parserService->parseLinks($pages);

        $this->assertEmpty(actual: $result);
    }

    public function testCanHandleEmptyResponse()
    {
        Http::fake([
            'https://example.com/page' => Http::response('', 200),
        ]);

        $links = ['https://example.com/page'];
        $result = $this->parserService->extractTitleAndAuthors($links);

        $this->assertEmpty(actual: $result);
    }

    public function testCanHandleMalformedHtml()
    {
        $html = '<html>html>';
        Http::fake([
            'https://example.com/page' => Http::response($html, 200),
        ]);

        $links = ['https://example.com/page'];
        $result = $this->parserService->extractTitleAndAuthors($links);

        $this->assertArrayHasKey('https://example.com/page', $result);
        $this->assertEmpty($result['https://example.com/page']);
    }
}
