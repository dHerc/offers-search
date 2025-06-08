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
use App\Services\QueryTransformers\RemoveWordQueryTransformer;
use App\Services\QueryTransformers\ReorderWordsQueryTransformer;
use App\Services\QueryTransformers\SnippetsQueryTransformer;
use App\Services\QueryTransformers\ToBaseWordQueryTransformer;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OfferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        return $this->renderPage('Search/BaseSearch', 'Default Offers Search', $search, null);
    }

    public function removeWordIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $suggestions = null;
        if ($search) {
            $suggestions = (new RemoveWordQueryTransformer())->getWordsToRemove($search, QueryService::getOffers($search));
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Remove Word Offers Search', $search, $suggestions);
    }

    public function bringWordsToBaseIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $suggestions = null;
        if ($search) {
            $suggestions = (new ToBaseWordQueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Words to Base Offers Search', $search, $suggestions);
    }

    public function chatbotParaphraseIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $suggestions = null;
        if ($search) {
            $suggestions = (new ChatbotParaphraseQueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Chatbot Paraphrase Offers Search', $search, $suggestions);
    }

    public function chatbotRemoveWordsIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $suggestions = null;
        if ($search) {
            $suggestions = (new ChatbotRemoveWordsQueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Chatbot Remove Words Offers Search', $search, $suggestions);
    }

    public function chatbotAddWordsIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $suggestions = null;
        if ($search) {
            $suggestions = (new ChatbotAddWordsQueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Chatbot Add Words Offers Search', $search, $suggestions);
    }

    public function externalBM25RM3Index(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $suggestions = null;
        if ($search) {
            $suggestions = (new BM25RM3QueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'BM25RM3 Offers Search', $search, $suggestions);
    }

    public function flanQRIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $suggestions = null;
        if ($search) {
            $suggestions = (new FlanQRQueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'FlanQR Offers Search', $search, $suggestions);
    }

    public function flanPRFIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $suggestions = null;
        if ($search) {
            $suggestions = (new FlanPRFQueryTransformer())->transform($search, QueryService::getOffers($search));
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'FlanPRF Offers Search', $search, $suggestions);
    }

    public function snippetsIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $suggestions = null;
        if ($search) {
            $suggestions = (new SnippetsQueryTransformer())->transform($search, QueryService::getOffers($search));
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'Snippets Offers Search', $search, $suggestions);
    }

    public function ngramsIndex(OfferSearchRequest $request)
    {
        $search = $request->get('search');
        $suggestions = null;
        if ($search) {
            $suggestions = (new NGramsQueryTransformer())->transform($search);
        }
        return $this->renderPage('Search/FullPhraseSuggestionsSearch', 'N-grams Offers Search', $search, $suggestions);
    }

    private function renderPage(string $page, string $title, ?string $query, ?array $suggestions)
    {
        $offers = null;
        [$start] = hrtime();
        if ($query) {
            $offers = QueryService::getOffers($query, 10);
        }
        [$end] = hrtime();
        $time = (int)($end - $start);
        return Inertia::render($page, [
            'offers' => $offers,
            'search' => $query,
            'suggestions' => $suggestions,
            'time' => $time,
            'title' => $title,
        ]);
    }
}
