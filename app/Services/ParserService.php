<?php

namespace App\Services;


use App\Constants\XPaths;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Log;

class ParserService
{
    public function parseLinks($pages): array
    {
        $extractedLinks = [];

        foreach ($pages as $page) {
            $response = Http::get($page);
            if ($response->failed()) {
                Log::error('Could not parse page: ' . $page);
                continue;
            }

            $body = $response->body();
            $xpathParser = $this->loadDOMXPath($body);

            if ($xpathParser === null) {
                Log::error('Could not parse links', [
                    'body' => $body
                ]);
                return [];
            }

            $links = $xpathParser->evaluate(XPaths::LINKS);
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                $extractedLinks[] = $this->normaliseUrl($href);
            }
        }

        return array_unique($extractedLinks);
    }

    public function extractTitleAndAuthors($links): array
    {
        $linksToProcess = array_slice($links, 0, 50);

        $responses = Http::pool(
            fn(Pool $pool) =>
            array_map(fn($link) => $pool->get($link), $linksToProcess)
        );

        $extractedData = [];

        foreach ($responses as $index => $response) {
            $body = $response->body();
            $xpathParser = $this->loadDOMXPath($body);

            if ($xpathParser === null) {
                Log::error('Could not parse links', [
                    'body' => $body
                ]);
                continue;
            }
            $currentLinkProcessed = $linksToProcess[$index];

            $pageTitle = $xpathParser->evaluate(XPaths::TITLE);
            $authors = $xpathParser->evaluate(XPaths::AUTHORS);

            if (!$pageTitle->item(0)) {
                Log::error('Could not parse title', [
                    'link' => $currentLinkProcessed
                ]);
                $extractedData[$currentLinkProcessed] = [];
                continue;
            }

            $title = $pageTitle->item(0)->getAttribute('content');
            $authorList = [];

            foreach ($authors as $author) {
                if (!isset($author->nodeValue)) {
                    Log::error('Could not parse author', [
                        'link' => $currentLinkProcessed
                    ]);
                    $extractedData[$currentLinkProcessed] = [];
                    continue;
                }
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

    private function loadDOMXPath($body): ?DOMXPath
    {
        if (empty($body) || !is_string($body)) {
            return null;
        }

        $doc = new DOMDocument();
        @$doc->loadHTML($body, LIBXML_NOERROR);
        return new DOMXPath($doc);
    }

    private function cleanUnicodeAndTrimString(string $string): string
    {
        return trim(transliterator_transliterate('Any-Latin; Latin-ASCII;', $string));
    }
}
