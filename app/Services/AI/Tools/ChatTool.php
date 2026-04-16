<?php

namespace App\Services\AI\Tools;

use Prism\Prism\Facades\Tool;
use Prism\Prism\Tool as PrismTool;
use App\Services\AI\Chat\HybridSearchService;

class ChatTool
{
    public function __construct(
        protected HybridSearchService $searchService
    ) {}

    /**
     * Exposes this domain logic as a callable Prism Tool.
     */
    public function asPrismTool(): PrismTool
    {
        return Tool::as('support_document_search')
            ->for('Search the internal ERP manuals, Standard Operating Procedures (SOPs), and error documentation to help answer employee questions.')
            ->withStringParameter('search_query', 'The exact question or keywords to extract from the manuals.')
            ->using(function (string $search_query): string {
                return "DOCUMENTATION RESULTS:\n" . $this->searchService->search($search_query);
            });
    }
}
