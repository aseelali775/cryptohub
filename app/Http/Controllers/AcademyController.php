<?php

namespace App\Http\Controllers;

use App\Models\AcademyArticle;
use App\Models\AcademyTopic;
use Inertia\Inertia;
use Inertia\Response;

class AcademyController extends Controller
{
    /**
     * Academy homepage.
     */
    public function index(): Response
    {
        $topics = AcademyTopic::query()
            ->where('is_active', true)
            ->withCount([
                'articles' => function ($query) {
                    $query->where('status', 'published');
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Academy/Index', [
            'topics' => $topics,
        ]);
    }

    /**
     * Single academy topic.
     */
    public function topic(string $topic): Response
    {
        $academyTopic = AcademyTopic::query()
            ->where('slug', $topic)
            ->where('is_active', true)
            ->with([
                'articles' => function ($query) {
                    $query
                        ->where('status', 'published')
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now())
                        ->orderBy('sort_order')
                        ->orderBy('published_at');
                },
            ])
            ->firstOrFail();

        return Inertia::render('Academy/Topic', [
            'topic' => $academyTopic,
        ]);
    }

    /**
     * Single academy article.
     */
    public function article(string $topic, string $article): Response
    {
        $academyArticle = AcademyArticle::query()
            ->where('slug', $article)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('topic', function ($query) use ($topic) {
                $query
                    ->where('slug', $topic)
                    ->where('is_active', true);
            })
            ->with('topic')
            ->firstOrFail();

        return Inertia::render('Academy/Article', [
            'article' => $academyArticle,
        ]);
    }
}