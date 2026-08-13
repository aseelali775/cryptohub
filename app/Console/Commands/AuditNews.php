<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AuditNews extends Command
{
    protected $signature = 'news:audit';

    protected $description =
        'Audit all news articles for missing content, short content, duplicate titles and similar articles.';

    public function handle(): int
    {
        $this->info('🔍 Starting Aql Crypto News Audit...');

        $news = News::query()
            ->orderBy('id')
            ->get([
                'id',
                'title_en',
                'title_ar',
                'content_en',
                'content_ar',
                'summary_ar',
                'why_it_matters_ar',
                'analysis_ar',
                'context_ar',
                'what_to_watch_ar',
                'limitations_ar',
                'ai_processed',
                'slug',
                'created_at',
            ]);

        $this->info("📰 Total articles: {$news->count()}");

        /*
        |--------------------------------------------------------------------------
        | 1. Missing AI fields
        |--------------------------------------------------------------------------
        */

        $incomplete = $news->filter(function ($article) {
            $fields = [
                'title_ar',
                'content_ar',
                'summary_ar',
                'why_it_matters_ar',
                'analysis_ar',
                'context_ar',
                'what_to_watch_ar',
                'limitations_ar',
            ];

            foreach ($fields as $field) {
                if (
                    is_null($article->{$field}) ||
                    trim((string) $article->{$field}) === ''
                ) {
                    return true;
                }
            }

            return false;
        });

        /*
        |--------------------------------------------------------------------------
        | 2. Short Arabic content
        |--------------------------------------------------------------------------
        */

        $shortContent = $news->filter(function ($article) {
            return mb_strlen(
                trim((string) $article->content_ar)
            ) > 0
            &&
            mb_strlen(
                trim((string) $article->content_ar)
            ) < 800;
        });

        /*
        |--------------------------------------------------------------------------
        | 3. Short English source
        |--------------------------------------------------------------------------
        */

        $shortSource = $news->filter(function ($article) {
            return mb_strlen(
                trim((string) $article->content_en)
            ) > 0
            &&
            mb_strlen(
                trim((string) $article->content_en)
            ) < 200;
        });

        /*
        |--------------------------------------------------------------------------
        | 4. Duplicate titles
        |--------------------------------------------------------------------------
        */

        $titleGroups = $news
            ->filter(fn ($article) =>
                trim((string) $article->title_en) !== ''
            )
            ->groupBy(function ($article) {
                return Str::lower(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        trim((string) $article->title_en)
                    )
                );
            })
            ->filter(fn ($group) => $group->count() > 1);

        /*
        |--------------------------------------------------------------------------
        | 5. Similar titles
        |--------------------------------------------------------------------------
        */

        $similarTitles = [];

        $articles = $news->values();

        for ($i = 0; $i < $articles->count(); $i++) {

            $titleA = $this->normalizeTitle(
                $articles[$i]->title_en
            );

            if ($titleA === '') {
                continue;
            }

            for ($j = $i + 1; $j < $articles->count(); $j++) {

                $titleB = $this->normalizeTitle(
                    $articles[$j]->title_en
                );

                if ($titleB === '') {
                    continue;
                }

                similar_text(
                    $titleA,
                    $titleB,
                    $percentage
                );

                if ($percentage >= 75) {
                    $similarTitles[] = [
                        'id_a' => $articles[$i]->id,
                        'title_a' => $articles[$i]->title_en,

                        'id_b' => $articles[$j]->id,
                        'title_b' => $articles[$j]->title_en,

                        'similarity' => round($percentage, 2),
                    ];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Missing slugs
        |--------------------------------------------------------------------------
        */

        $missingSlugs = $news->filter(function ($article) {
            return is_null($article->slug)
                || trim((string) $article->slug) === '';
        });

        /*
        |--------------------------------------------------------------------------
        | Report
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info('==============================');
        $this->info('📊 AQL CRYPTO NEWS AUDIT');
        $this->info('==============================');

        $this->line(
            "Total articles: {$news->count()}"
        );

        $this->line(
            "Incomplete AI articles: {$incomplete->count()}"
        );

        $this->line(
            "Short Arabic articles: {$shortContent->count()}"
        );

        $this->line(
            "Short source articles: {$shortSource->count()}"
        );

        $this->line(
            "Duplicate title groups: {$titleGroups->count()}"
        );

        $this->line(
            "Similar title pairs: " . count($similarTitles)
        );

        $this->line(
            "Missing slugs: {$missingSlugs->count()}"
        );

        /*
        |--------------------------------------------------------------------------
        | Incomplete articles
        |--------------------------------------------------------------------------
        */

        if ($incomplete->isNotEmpty()) {

            $this->newLine();
            $this->warn('⚠️ INCOMPLETE ARTICLES');

            foreach ($incomplete as $article) {

                $this->line(
                    "#{$article->id} — {$article->title_en}"
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Short content
        |--------------------------------------------------------------------------
        */

        if ($shortContent->isNotEmpty()) {

            $this->newLine();
            $this->warn('⚠️ SHORT ARABIC CONTENT');

            foreach ($shortContent as $article) {

                $length = mb_strlen(
                    trim((string) $article->content_ar)
                );

                $this->line(
                    "#{$article->id} — {$length} chars — {$article->title_en}"
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate titles
        |--------------------------------------------------------------------------
        */

        if ($titleGroups->isNotEmpty()) {

            $this->newLine();
            $this->warn('⚠️ DUPLICATE TITLES');

            foreach ($titleGroups as $group) {

                $this->line('');

                foreach ($group as $article) {

                    $this->line(
                        "#{$article->id} — {$article->title_en}"
                    );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Similar titles
        |--------------------------------------------------------------------------
        */

        if (!empty($similarTitles)) {

            $this->newLine();
            $this->warn('⚠️ SIMILAR TITLES');

            foreach ($similarTitles as $pair) {

                $this->line(
                    "[{$pair['similarity']}%]"
                );

                $this->line(
                    "#{$pair['id_a']} — {$pair['title_a']}"
                );

                $this->line(
                    "#{$pair['id_b']} — {$pair['title_b']}"
                );

                $this->line('');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Missing slugs
        |--------------------------------------------------------------------------
        */

        if ($missingSlugs->isNotEmpty()) {

            $this->newLine();
            $this->warn('⚠️ MISSING SLUGS');

            foreach ($missingSlugs as $article) {

                $this->line(
                    "#{$article->id} — {$article->title_en}"
                );
            }
        }

        $this->newLine();

        $this->info('✅ News audit completed.');

        return self::SUCCESS;
    }

    private function normalizeTitle(?string $title): string
    {
        $title = Str::lower(
            trim((string) $title)
        );

        $title = preg_replace(
            '/[^\p{L}\p{N}\s]/u',
            ' ',
            $title
        );

        $title = preg_replace(
            '/\s+/',
            ' ',
            $title
        );

        return trim($title);
    }
}