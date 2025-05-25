<?php

namespace App\Console\Commands;

use App\Constants\Links;
use App\Services\ParserService;
use Illuminate\Console\Command;

class ParseLinks extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parser:links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(ParserService $parserService)
    {
        $pages = [
            'https://www.gov.uk/search/policy-papers-and-consultations?content_store_document_type%5B%5D=policy_papers&order=updated-newest',
            'https://www.gov.uk/search/policy-papers-and-consultations?content_store_document_type%5B%5D=policy_papers&order=updated-newest&page=2',
            'https://www.gov.uk/search/policy-papers-and-consultations?content_store_document_type%5B%5D=policy_papers&order=updated-newest&page=3'
        ];
        $pages = Links::PAGES;
        echo 'Now getting links for ' . count($pages) . ' pages' . PHP_EOL;
        $links = $parserService->parseLinks($pages);
        echo 'Now parsing titles and authors for ' . count($links) . ' links' . PHP_EOL;
        print_r($parserService->extractTitleAndAuthors($links));
    }
}
