<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

class DeleteWeakNews extends Command
{
    protected $signature = 'news:delete-weak
                            {--limit=20 : Number of weak news to delete}';

    protected $description =
        'Safely delete weak news after explicit YES confirmation';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        if ($limit <= 0) {
            $this->error(
                'Limit must be greater than zero.'
            );

            return self::FAILURE;
        }

        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            '        AQL DELETE WEAK NEWS'
        );

        $this->info(
            '=============================================='
        );

        $this->warn(
            'WARNING: This operation will permanently delete news.'
        );

        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | Get Weak News
        |--------------------------------------------------------------------------
        |
        | Same logic used by the audit:
        | Original content is English first,
        | Arabic is used as fallback.
        |
        */

        $news = News::query()
            ->orderBy('id')
            ->get()
            ->filter(function (News $item) {

                $contentEnLength = mb_strlen(
                    trim((string) $item->content_en)
                );

                $contentArLength = mb_strlen(
                    trim((string) $item->content_ar)
                );

                $originalContentLength =
                    $contentEnLength > 0
                        ? $contentEnLength
                        : $contentArLength;

                return $originalContentLength > 0
                    && $originalContentLength < 300;
            })
            ->take($limit)
            ->values();

        if ($news->isEmpty()) {

            $this->info(
                'No weak news found.'
            );

            return self::SUCCESS;
        }

        /*
        |--------------------------------------------------------------------------
        | Display Candidates
        |--------------------------------------------------------------------------
        */

        $this->info(
            "Found {$news->count()} weak news:"
        );

        $this->newLine();

        foreach ($news as $item) {

            $contentEnLength = mb_strlen(
                trim((string) $item->content_en)
            );

            $contentArLength = mb_strlen(
                trim((string) $item->content_ar)
            );

            $contentLength =
                $contentEnLength > 0
                    ? $contentEnLength
                    : $contentArLength;

            $title =
                $item->title_en
                ?: $item->title_ar
                ?: 'Untitled';

            $this->line(
                "ID {$item->id}"
            );

            $this->line(
                "Title: {$title}"
            );

            $this->line(
                "Content: {$contentLength} chars"
            );

            $this->line(
                "AI: " .
                ((bool) $item->ai_processed
                    ? 'YES'
                    : 'NO')
            );

            $this->newLine();
        }

        /*
        |--------------------------------------------------------------------------
        | Confirmation
        |--------------------------------------------------------------------------
        */

        $this->warn(
            'These records will be permanently deleted.'
        );

        $this->warn(
            'This action cannot be automatically undone.'
        );

        $this->newLine();

        $confirmation = $this->ask(
            'Type YES to permanently delete these news'
        );

        if ($confirmation !== 'YES') {

            $this->info(
                'Deletion cancelled.'
            );

            return self::SUCCESS;
        }

        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        $ids = $news
            ->pluck('id')
            ->values();

        $deleted = News::query()
            ->whereIn('id', $ids)
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | Result
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            'DELETION COMPLETED'
        );

        $this->info(
            '=============================================='
        );

        $this->line(
            "Requested: {$news->count()}"
        );

        $this->line(
            "Deleted:   {$deleted}"
        );

        $this->line(
            'IDs:       ' .
            $ids->implode(', ')
        );

        $this->newLine();

        return self::SUCCESS;
    }
}
