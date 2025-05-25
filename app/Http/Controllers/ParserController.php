<?php

namespace App\Http\Controllers;

use App\Services\ParserService;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;

class ParserController extends Controller
{
    /**
     * @return array<string, array{}|array{title: bool|string, authors: list<mixed>}> $extractedData
     */
    public function index(ParserService $parserService): array
    {

        $pages = [
            'https://www.gov.uk/search/policy-papers-and-consultations?content_store_document_type%5B%5D=policy_papers&order=updated-newest',
            'https://www.gov.uk/search/policy-papers-and-consultations?content_store_document_type%5B%5D=policy_papers&order=updated-newest&page=2',
            'https://www.gov.uk/search/policy-papers-and-consultations?content_store_document_type%5B%5D=policy_papers&order=updated-newest&page=3'
        ];
        $links = $parserService->parseLinks($pages);
        return $parserService->extractTitleAndAuthors($links);
    }
}
