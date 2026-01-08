<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfferSearchRequest;
use App\Models\Offer;
use App\Services\EmbeddingService;
use App\Services\QueryService;
use App\Services\QueryTransformers\BM25RM3QueryTransformer;
use App\Services\QueryTransformers\ChatbotAddWordsQueryTransformer;
use App\Services\QueryTransformers\ChatbotParaphraseQueryTransformer;
use App\Services\QueryTransformers\ChatbotRemoveWordsQueryTransformer;
use App\Services\QueryTransformers\FlanPRFQueryTransformer;
use App\Services\QueryTransformers\FlanQRQueryTransformer;
use App\Services\QueryTransformers\NGramsQueryTransformer;
use App\Services\QueryTransformers\QueryWordCountCBZHybridTransformer;
use App\Services\QueryTransformers\RemoveWordQueryTransformer;
use App\Services\QueryTransformers\ReorderWordsQueryTransformer;
use App\Services\QueryTransformers\SnippetsQueryTransformer;
use App\Services\QueryTransformers\ToBaseWordQueryTransformer;
use App\Services\ScoringService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OfferController extends Controller
{
    private const PER_PAGE = 10;
    /**
     * Display a listing of the resource.
     */
    public function index(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $page = (int)$request->get('page', 1);
        return $this->renderPage('Search/BaseSearch', 'Default Offers Search', $page, $search, null);
    }

    public function removeWordIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $page = (int)$request->get('page', 1);
        $suggestions = null;
        if ($search) {
            $suggestions = (new RemoveWordQueryTransformer())->transform($search, QueryService::getOffers($search));
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Remove Word Offers Search', $page, $search, $suggestions);
    }

    public function bringWordsToBaseIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $page = (int)$request->get('page', 1);
        $suggestions = null;
        if ($search) {
            $suggestions = (new ToBaseWordQueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Words to Base Offers Search', $page, $search, $suggestions);
    }

    public function chatbotParaphraseIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $page = (int)$request->get('page', 1);
        $suggestions = null;
        if ($search) {
            $suggestions = (new ChatbotParaphraseQueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Chatbot Paraphrase Offers Search', $page, $search, $suggestions);
    }

    public function chatbotRemoveWordsIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $page = (int)$request->get('page', 1);
        $suggestions = null;
        if ($search) {
            $suggestions = (new ChatbotRemoveWordsQueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Chatbot Remove Words Offers Search', $page, $search, $suggestions);
    }

    public function chatbotAddWordsIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $page = (int)$request->get('page', 1);
        $suggestions = null;
        if ($search) {
            $suggestions = (new ChatbotAddWordsQueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Chatbot Add Words Offers Search', $page, $search, $suggestions);
    }

    public function externalBM25RM3Index(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $page = (int)$request->get('page', 1);
        $suggestions = null;
        if ($search) {
            $suggestions = (new BM25RM3QueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'BM25RM3 Offers Search', $page, $search, $suggestions);
    }

    public function flanQRIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $page = (int)$request->get('page', 1);
        $suggestions = null;
        if ($search) {
            $suggestions = (new FlanQRQueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'FlanQR Offers Search', $page, $search, $suggestions);
    }

    public function flanPRFIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $page = (int)$request->get('page', 1);
        $suggestions = null;
        if ($search) {
            $suggestions = (new FlanPRFQueryTransformer())->transform($search, QueryService::getOffers($search));
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'FlanPRF Offers Search', $page, $search, $suggestions);
    }

    public function snippetsIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $page = (int)$request->get('page', 1);
        $suggestions = null;
        if ($search) {
            $suggestions = (new SnippetsQueryTransformer())->transform($search, QueryService::getOffers($search));
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Snippets Offers Search', $page, $search, $suggestions);
    }

    public function ngramsIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $page = (int)$request->get('page', 1);
        $suggestions = null;
        if ($search) {
            $suggestions = (new NGramsQueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'N-grams Offers Search', $page, $search, $suggestions);
    }

    public function hybridEmbeddingsIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $page = (int)$request->get('page', 1);
        $suggestions = null;
        if ($search) {
            $suggestions = (new QueryWordCountCBZHybridTransformer())->transform($search, QueryService::getOffers($search));
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Hybrid Embeddingsth Offers Search', $page, $search, $suggestions);
    }

    public function hybridFulltextIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $page = (int)$request->get('page', 1);
        $suggestions = null;
        if ($search) {
            $suggestions = (new QueryWordCountCBZHybridTransformer())->transform($search, QueryService::getOffers($search));
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Hybrid Fulltext Offers Search', $page, $search, $suggestions, true);
    }

    private function renderPage(string $component, string $title, int $page, ?string $query, ?array $suggestions, bool $useFulltext = false)
    {
        $offersPage = null;
        $score = null;
        [$start] = hrtime();
        if ($query) {
            $offers = $useFulltext ? QueryService::getOffersByFullText($query) : QueryService::getOffers($query);
            $score = ScoringService::scoreResults($query, $offers->all())->score10;
            $offersPage = [
                'data' => $offers->skip(($page - 1) * self::PER_PAGE)->take(self::PER_PAGE)->values(),
                'last_page' => (int)ceil((float)($offers->count())/self::PER_PAGE),
                'current_page' => $page,
                'total' => $offers->count(),
            ];
        }
        [$end] = hrtime();
        $time = $end - $start;
        return Inertia::render($component, [
            'offers' => $offersPage,
            'search' => $query,
            'suggestions' => $suggestions,
            'time' => $time,
            'title' => $title,
            'score' => $score
        ]);
    }
}
