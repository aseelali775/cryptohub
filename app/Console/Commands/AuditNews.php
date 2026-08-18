<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

class AuditNews extends Command
{
    protected $signature = 'news:audit
                            {--limit=0 : Number of news to audit, 0 = all}
                            {--show-ready : Show READY news}
                            {--show-repair : Show REPAIR news}
                            {--show-review : Show REVIEW news}
                            {--show-delete : Show DELETE candidates}';

    protected $description = 'Audit all news for completeness, quality and AI readiness without modifying data';

    public function handle(): int
    {
        $this->newLine();

        $this->info('==============================================');
        $this->info('        AQL CRYPTO NEWS QUALITY AUDIT');
        $this->info('==============================================');
        $this->newLine();

        $query = News::query()
            ->orderBy('id');

        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $news = $query->get();

        if ($news->isEmpty()) {
            $this->warn('No news found.');
            return self::SUCCESS;
        }

        $this->info("Auditing {$news->count()} news articles...");
        $this->newLine();

        $results = [];

        foreach ($news as $item) {
            $results[] = $this->auditNews($item);
        }

        /*
         * =========================================================
         * الإحصائيات العامة
         * =========================================================
         */

        $total = count($results);

        $ready = collect($results)
            ->where('status', 'READY')
            ->count();

        $repair = collect($results)
            ->where('status', 'REPAIR')
            ->count();

        $review = collect($results)
            ->where('status', 'REVIEW')
            ->count();

        $delete = collect($results)
            ->where('status', 'DELETE')
            ->count();

        /*
         * =========================================================
         * الحقول الناقصة
         * =========================================================
         */

        $missing = [
            'title_ar'          => 0,
            'title_en'          => 0,
            'content_ar'        => 0,
            'content_en'        => 0,
            'summary_ar'        => 0,
            'why_it_matters_ar' => 0,
            'analysis_ar'       => 0,
            'context_ar'        => 0,
            'what_to_watch_ar'  => 0,
            'limitations_ar'    => 0,
            'source'            => 0,
            'url'               => 0,
            'image_url'         => 0,
            'category'          => 0,
        ];

        foreach ($news as $item) {

            foreach ($missing as $field => $count) {

                if ($this->isEmpty($item->{$field})) {
                    $missing[$field]++;
                }
            }
        }

        /*
         * =========================================================
         * التقرير العام
         * =========================================================
         */

        $this->info('----------------------------------------------');
        $this->info('GENERAL STATISTICS');
        $this->info('----------------------------------------------');

        $this->line("Total news:              {$total}");

        $this->line(
            'AI processed:            ' .
            $news->where('ai_processed', true)->count()
        );

        $this->line(
            'AI not processed:        ' .
            $news->where('ai_processed', false)->count()
        );

        $this->newLine();

        $this->info('QUALITY CLASSIFICATION');

        $this->line("🟢 READY:                 {$ready}");
        $this->line("🟡 REPAIR:                {$repair}");
        $this->line("🟠 REVIEW:                {$review}");
        $this->line("🔴 DELETE CANDIDATE:      {$delete}");

        $this->newLine();

        /*
         * =========================================================
         * الحقول الناقصة
         * =========================================================
         */

        $this->info('----------------------------------------------');
        $this->info('MISSING FIELDS');
        $this->info('----------------------------------------------');

        foreach ($missing as $field => $count) {

            $this->line(
                str_pad($field, 24) .
                ': ' .
                $count
            );
        }

        $this->newLine();

        /*
         * =========================================================
         * طول المحتوى
         * =========================================================
         */

        $contentStats = [
            'empty' => 0,
            'very_short' => 0,
            'short' => 0,
            'acceptable' => 0,
            'strong' => 0,
        ];

        foreach ($news as $item) {

            $length = $this->contentLength($item);

            if ($length === 0) {
                $contentStats['empty']++;
            } elseif ($length < 300) {
                $contentStats['very_short']++;
            } elseif ($length < 700) {
                $contentStats['short']++;
            } elseif ($length < 1500) {
                $contentStats['acceptable']++;
            } else {
                $contentStats['strong']++;
            }
        }

        $this->info('----------------------------------------------');
        $this->info('CONTENT LENGTH');
        $this->info('----------------------------------------------');

        $this->line(
            "Empty:                   {$contentStats['empty']}"
        );

        $this->line(
            "Very short (<300):       {$contentStats['very_short']}"
        );

        $this->line(
            "Short (300-699):         {$contentStats['short']}"
        );

        $this->line(
            "Acceptable (700-1499):   {$contentStats['acceptable']}"
        );

        $this->line(
            "Strong (1500+):          {$contentStats['strong']}"
        );

        $this->newLine();

        /*
         * =========================================================
         * عرض القوائم
         * =========================================================
         */

        $this->displayResults(
            $results,
            'READY',
            '🟢 READY',
            $this->option('show-ready')
        );

        $this->displayResults(
            $results,
            'REPAIR',
            '🟡 REPAIR',
            $this->option('show-repair')
        );

        $this->displayResults(
            $results,
            'REVIEW',
            '🟠 REVIEW',
            $this->option('show-review')
        );

        $this->displayResults(
            $results,
            'DELETE',
            '🔴 DELETE CANDIDATE',
            $this->option('show-delete')
        );

        /*
         * =========================================================
         * ملخص نهائي
         * =========================================================
         */

        $this->info('==============================================');
        $this->info('AUDIT COMPLETED');
        $this->info('==============================================');

        $this->line("Total:       {$total}");
        $this->line("🟢 Ready:    {$ready}");
        $this->line("🟡 Repair:   {$repair}");
        $this->line("🟠 Review:   {$review}");
        $this->line("🔴 Delete:   {$delete}");

        $this->newLine();

        $this->comment(
            'No database records were modified or deleted.'
        );

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * تحليل خبر واحد.
     */
    private function auditNews(News $item): array
    {
        $issues = [];

        /*
         * ---------------------------------------------------------
         * العنوان
         * ---------------------------------------------------------
         */

        if ($this->isEmpty($item->title_en)) {
            $issues[] = 'missing title_en';
        }

        if ($this->isEmpty($item->title_ar)) {
            $issues[] = 'missing title_ar';
        }

        /*
         * ---------------------------------------------------------
         * المحتوى
         * ---------------------------------------------------------
         */

        $contentArLength = mb_strlen(
            trim((string) $item->content_ar)
        );

        $contentEnLength = mb_strlen(
            trim((string) $item->content_en)
        );

        if ($contentArLength === 0) {
            $issues[] = 'missing content_ar';
        }

        if ($contentEnLength === 0) {
            $issues[] = 'missing content_en';
        }

        /*
         * ---------------------------------------------------------
         * جودة طول المحتوى
         * ---------------------------------------------------------
         */

        $mainContentLength = max(
            $contentArLength,
            $contentEnLength
        );

        if ($mainContentLength > 0 && $mainContentLength < 300) {
            $issues[] = 'content very short';
        } elseif ($mainContentLength > 0 && $mainContentLength < 700) {
            $issues[] = 'content short';
        }

        /*
         * ---------------------------------------------------------
         * AI
         * ---------------------------------------------------------
         */

        if (!$item->ai_processed) {
            $issues[] = 'ai not processed';
        }

        $aiFields = [
            'summary_ar',
            'why_it_matters_ar',
            'analysis_ar',
            'context_ar',
            'what_to_watch_ar',
            'limitations_ar',
        ];

        $missingAiFields = [];

        foreach ($aiFields as $field) {

            if ($this->isEmpty($item->{$field})) {

                $missingAiFields[] = $field;
            }
        }

        if (!empty($missingAiFields)) {

            $issues[] =
                'missing AI fields: ' .
                implode(', ', $missingAiFields);
        }

        /*
         * ---------------------------------------------------------
         * المصدر
         * ---------------------------------------------------------
         */

        if ($this->isEmpty($item->source)) {
            $issues[] = 'missing source';
        }

        if ($this->isEmpty($item->url)) {
            $issues[] = 'missing source URL';
        }

        /*
         * ---------------------------------------------------------
         * الصورة
         * ---------------------------------------------------------
         */

        if ($this->isEmpty($item->image_url)) {
            $issues[] = 'missing image';
        }

        /*
         * ---------------------------------------------------------
         * التصنيف
         * ---------------------------------------------------------
         */

        if ($this->isEmpty($item->category)) {
            $issues[] = 'missing category';
        }

        /*
         * =========================================================
         * تحديد الحالة
         * =========================================================
         */

        $status = 'READY';

        /*
         * DELETE
         *
         * إذا لم يكن لدينا أي محتوى حقيقي.
         *
         * لا نحذف فعلياً.
         */
        if (
            $contentArLength === 0 &&
            $contentEnLength === 0
        ) {

            $status = 'DELETE';

            $issues[] =
                'no usable article content';
        }

        /*
         * REVIEW
         *
         * محتوى قصير جداً + مشاكل إضافية.
         */
        elseif (
            $mainContentLength > 0 &&
            $mainContentLength < 300
        ) {

            $status = 'REVIEW';

            $issues[] =
                'article may be too short for publication';
        }

        /*
         * REPAIR
         *
         * إذا كان المحتوى موجوداً
         * ولكن توجد حقول قابلة للإصلاح.
         */
        elseif (
            !$item->ai_processed ||
            !empty($missingAiFields) ||
            in_array('missing title_ar', $issues, true) ||
            in_array('missing image', $issues, true) ||
            in_array('missing category', $issues, true)
        ) {

            $status = 'REPAIR';
        }

        /*
         * REVIEW
         *
         * مشاكل المصدر.
         */
        if (
            $this->isEmpty($item->source) ||
            $this->isEmpty($item->url)
        ) {

            if ($status !== 'DELETE') {
                $status = 'REVIEW';
            }
        }

        /*
         * =========================================================
         * النتيجة
         * ========================================================= */

        return [
            'id' => $item->id,

            'title' => $item->title_en
                ?: $item->title_ar
                ?: 'Untitled',

            'status' => $status,

            'issues' => $issues,

            'content_length' => $mainContentLength,

            'ai_processed' => (bool) $item->ai_processed,

            'source' => $item->source,
        ];
    }

    /**
     * فحص الحقول الفارغة.
     */
    private function isEmpty($value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return empty($value);
        }

        return false;
    }

    /**
     * حساب طول المحتوى الحقيقي.
     */
    private function contentLength(News $item): int
    {
        $ar = mb_strlen(
            trim((string) $item->content_ar)
        );

        $en = mb_strlen(
            trim((string) $item->content_en)
        );

        return max($ar, $en);
    }

    /**
     * عرض نتائج مجموعة معينة.
     */
    private function displayResults(
        array $results,
        string $status,
        string $label,
        bool $show
    ): void {

        if (!$show) {
            return;
        }

        $items = collect($results)
            ->where('status', $status)
            ->values();

        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            $label . ' (' . $items->count() . ')'
        );

        $this->info(
            '=============================================='
        );

        if ($items->isEmpty()) {

            $this->line('None.');

            return;
        }

        foreach ($items as $item) {

            $this->line(
                "ID {$item['id']} | " .
                $item['title']
            );

            $this->line(
                "Content length: " .
                $item['content_length'] .
                " | AI: " .
                ($item['ai_processed'] ? 'YES' : 'NO')
            );

            if ($item['source']) {

                $this->line(
                    "Source: " .
                    $item['source']
                );
            }

            if (!empty($item['issues'])) {

                foreach ($item['issues'] as $issue) {

                    $this->line(
                        "  - {$issue}"
                    );
                }
            }

            $this->newLine();
        }
    }
}