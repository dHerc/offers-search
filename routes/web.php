<?php

use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RawController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/', function () {
    return redirect('/offers');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('offers', [OfferController::class, 'index'])->name('offers');
Route::get('offers/remove_word', [OfferController::class, 'removeWordIndex'])->name('offers.remove_word');
Route::get('offers/words_to_base', [OfferController::class, 'bringWordsToBaseIndex'])->name('offers.words_to_base');
Route::get('offers/chatbot_rephrase', [OfferController::class, 'chatbotParaphraseIndex'])->name('offers.chatbot_rephrase');
Route::get('offers/chatbot_add', [OfferController::class, 'chatbotAddWordsIndex'])->name('offers.chatbot_add');
Route::get('offers/chatbot_remove', [OfferController::class, 'chatbotRemoveWordsIndex'])->name('offers.chatbot_remove');
Route::get('offers/bm25rm3', [OfferController::class, 'externalBM25RM3Index'])->name('offers.bm25rm3');
Route::get('offers/flanqr', [OfferController::class, 'flanQRIndex'])->name('offers.flanqr');
Route::get('offers/flanprf', [OfferController::class, 'flanPRFIndex'])->name('offers.flanprf');
Route::get('offers/snippets', [OfferController::class, 'snippetsIndex'])->name('offers.snippets');
Route::get('offers/ngrams', [OfferController::class, 'ngramsIndex'])->name('offers.ngrams');
Route::get('offers/hybrid-embeddings', [OfferController::class, 'hybridEmbeddingsIndex'])->name('offers.hybrid-embeddings');
Route::get('offers/hybrid-fulltext', [OfferController::class, 'hybridFulltextIndex'])->name('offers.hybrid-fulltext');

Route::get('raw', [RawController::class, 'getEmbeddings']);
Route::get('compare', [RawController::class, 'compareEmbeddings']);

require __DIR__.'/auth.php';
