<?php

namespace App\Services;


use App\Constants\XPaths;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Log;

class ParserService
{

    private array $pages = [
        'https://www.gov.uk/search/policy-papers-and-consultations?content_store_document_type%5B%5D=policy_papers&order=updated-newest',
        'https://www.gov.uk/search/policy-papers-and-consultations?content_store_document_type%5B%5D=policy_papers&order=updated-newest&page=2',
        'https://www.gov.uk/search/policy-papers-and-consultations?content_store_document_type%5B%5D=policy_papers&order=updated-newest&page=3',
    ];
    public function parseLinks(): array
    {

        $extractedLinks = [];

        foreach ($this->pages as $page) {
            $response = Http::get($page);
            if ($response->failed()) {
                Log::error('Could not parse page: ' . $page);
                continue;
            }

            $body = $response->body();
            $xpathParser = $this->loadDOMXPath($body);

            $links = $xpathParser->evaluate(XPaths::LINKS);
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                $extractedLinks[] = $this->normaliseUrl($href);
            }
        }

        return array_unique($extractedLinks);
    }

    public function extractTitleAndAuthors(): array
    {
        $links = $this->parseLinks();

        $linksToProcess = array_slice($links, 0, 50);

        $responses = Http::pool(
            fn(Pool $pool) =>
            array_map(fn($link) => $pool->get($link), $linksToProcess)
        );

        $extractedData = [];

        foreach ($responses as $index => $response) {
            $body = $response->body();
            $xpathParser = $this->loadDOMXPath($body);
            $currentLinkProcessed = $linksToProcess[$index];

            $pageTitle = $xpathParser->evaluate(XPaths::TITLE);
            $authors = $xpathParser->evaluate(XPaths::AUTHORS);

            $title = $pageTitle->item(0)->getAttribute('content');
            $authorList = [];
            foreach ($authors as $author) {
                $authorList[] = $author->nodeValue;
            }

            $extractedData[$currentLinkProcessed] = [
                'title' => $this->cleanUnicodeAndTrimString($title),
                'authors' => $authorList,
            ];
        }

        return $extractedData;
    }

    private function normaliseUrl(string $url): string
    {
        $domain = 'https://www.gov.uk';
        $normalisedUrl =  strpos($url, $domain) === 0 ? $url : $domain . $url;
        return trim($normalisedUrl);
    }

    private function loadDOMXPath($body): DOMXPath
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($body, LIBXML_NOERROR);
        return new DOMXPath($doc);
    }

    private function cleanUnicodeAndTrimString(string $string): string
    {
        return trim(preg_replace('/\p{C}+/u', '', subject: $string));
    }
}
