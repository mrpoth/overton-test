<?php

namespace App\Http\Controllers;

use App\Services\ParserService;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;

class ParserController extends Controller
{
    public function index(ParserService $parserService): array
    {

        return $parserService->extractTitleAndAuthors();

    }
}
