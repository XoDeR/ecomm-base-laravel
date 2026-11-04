<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\UrlRedirect;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function show(string $slug): Response|RedirectResponse
    {
        $page = Page::active()->where('slug', $slug)->first();

        if (! $page) {
            $redirect = UrlRedirect::query()
                ->where('entity_type', 'page')
                ->where('old_slug', $slug)
                ->where('is_active', true)
                ->first();

            abort_unless($redirect, 404);

            return redirect('/'.$redirect->new_slug, $redirect->status_code);
        }

        return Inertia::render('page', [
            'page' => ['title' => $page->title, 'content' => $page->content],
            'seo' => SeoService::forPage($page),
        ]);
    }
}
