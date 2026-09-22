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
                    $query
                        ->where('status', 'published')
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now());
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        /*
         * Replace the generic fields with the localized values
         * before sending the data to Vue.
         *
         * This keeps the Vue pages simple:
         * topic.name
         * topic.description
         */
        $topics->each(function ($topic) {
            $topic->setAttribute(
                'name',
                $topic->localized_name
            );

            $topic->setAttribute(
                'description',
                $topic->localized_description
            );
        });

        return Inertia::render('Academy/Index', [
            'topics' => $topics,
        ]);
    }

    /**
     * Academy topic page.
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

        /*
         * Localize topic fields.
         */
        $academyTopic->setAttribute(
            'name',
            $academyTopic->localized_name
        );

        $academyTopic->setAttribute(
            'description',
            $academyTopic->localized_description
        );

        /*
         * Localize article fields.
         */
        $academyTopic->articles->each(function ($article) {
            $article->setAttribute(
                'title',
                $article->localized_title
            );

            $article->setAttribute(
                'excerpt',
                $article->localized_excerpt
            );

            $article->setAttribute(
                'content',
                $article->localized_content
            );

            $article->setAttribute(
                'seo_title',
                $article->localized_seo_title
            );

            $article->setAttribute(
                'meta_description',
                $article->localized_meta_description
            );
        });

        return Inertia::render('Academy/Topic', [
            'topic' => $academyTopic,
        ]);
    }

    /**
     * Academy article page.
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

        /*
         * Localize article fields.
         */
        $academyArticle->setAttribute(
            'title',
            $academyArticle->localized_title
        );

        $academyArticle->setAttribute(
            'excerpt',
            $academyArticle->localized_excerpt
        );

        $academyArticle->setAttribute(
            'content',
            $academyArticle->localized_content
        );

        $academyArticle->setAttribute(
            'seo_title',
            $academyArticle->localized_seo_title
        );

        $academyArticle->setAttribute(
            'meta_description',
            $academyArticle->localized_meta_description
        );

        /*
         * Localize the related topic.
         */
        $academyArticle->topic->setAttribute(
            'name',
            $academyArticle->topic->localized_name
        );

        $academyArticle->topic->setAttribute(
            'description',
            $academyArticle->topic->localized_description
        );

        return Inertia::render('Academy/Article', [
            'article' => $academyArticle,
        ]);
    }
}