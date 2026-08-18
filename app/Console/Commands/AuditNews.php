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
                        {--show-delete : Show DELETE candidates}
                        {--show-refetch : Show news that need content refetch}
                        {--show-ai : Show news that need AI only}
                        {--show-manual : Show news requiring manual review}
                        {--show-duplicates : Show duplicate/similar groups}
                        {--show-weak : Show news with quality score below 60}';

    protected $description =
        'Professional read-only audit for news quality, completeness, AI readiness, duplicates and publication readiness';

    /*
    |--------------------------------------------------------------------------
    | AI Fields
    |--------------------------------------------------------------------------
    */

    private array $aiFields = [
        'summary_ar',
        'why_it_matters_ar',
        'analysis_ar',
        'context_ar',
        'what_to_watch_ar',
        'limitations_ar',
    ];

    /*
    |--------------------------------------------------------------------------
    | Thresholds
    |--------------------------------------------------------------------------
    */

    private int $veryShortContent = 300;

    private int $shortContent = 700;

    private int $acceptableContent = 1500;

    public function handle(): int
    {
        $this->newLine();

        $this->info('==============================================');
        $this->info('        AQL CRYPTO NEWS QUALITY AUDIT');
        $this->info('==============================================');
        $this->newLine();

        /*
         * =========================================================
         * Load news
         * =========================================================
         */

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

        $this->info(
            "Auditing {$news->count()} news articles..."
        );

        $this->newLine();

        /*
         * =========================================================
         * Duplicate detection
         * =========================================================
         */

        $duplicateMap = $this->detectDuplicates($news);

        /*
         * =========================================================
         * Audit every article
         * =========================================================
         */

        $results = [];

        foreach ($news as $item) {
            $results[] = $this->auditNews(
                $item,
                $duplicateMap
            );
        }

        $resultsCollection = collect($results);

        /*
         * =========================================================
         * General statistics
         * =========================================================
         */

        $total = $resultsCollection->count();

        $ready = $resultsCollection
            ->where('status', 'READY')
            ->count();

        $repair = $resultsCollection
            ->where('status', 'REPAIR')
            ->count();

        $review = $resultsCollection
            ->where('status', 'REVIEW')
            ->count();

        $delete = $resultsCollection
            ->where('status', 'DELETE')
            ->count();

        /*
         * =========================================================
         * Recommendation statistics
         * =========================================================
         */

        $aiOnly = $resultsCollection
            ->where('recommendation', 'AI_ONLY')
            ->count();

        $refetch = $resultsCollection
            ->where('recommendation', 'REFETCH')
            ->count();

        $manualReview = $resultsCollection
            ->where('recommendation', 'MANUAL_REVIEW')
            ->count();

        $duplicateCount = $resultsCollection
            ->filter(fn ($item) => $item['duplicate'])
            ->count();

        /*
         * =========================================================
         * AI statistics
         * =========================================================
         */

        $aiProcessed = $news
            ->where('ai_processed', true)
            ->count();

        $aiNotProcessed = $news
            ->where('ai_processed', false)
            ->count();

        /*
         * =========================================================
         * Missing fields
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
            'what_to_watch_ar' => 0,
            'limitations_ar'   => 0,
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
         * Content statistics
         * =========================================================
         */

        $contentStats = [
            'empty'       => 0,
            'very_short'  => 0,
            'short'       => 0,
            'acceptable'  => 0,
            'strong'      => 0,
        ];

        foreach ($news as $item) {
            $length = $this->originalContentLength($item);

            if ($length === 0) {
                $contentStats['empty']++;
            } elseif ($length < $this->veryShortContent) {
                $contentStats['very_short']++;
            } elseif ($length < $this->shortContent) {
                $contentStats['short']++;
            } elseif ($length < $this->acceptableContent) {
                $contentStats['acceptable']++;
            } else {
                $contentStats['strong']++;
            }
        }

        /*
         * =========================================================
         * Quality statistics
         * =========================================================
         */

        $averageScore = round(
            $resultsCollection->avg('quality_score'),
            1
        );

        $excellent = $resultsCollection
            ->where('quality_score', '>=', 90)
            ->count();

        $good = $resultsCollection
            ->filter(
                fn ($item) =>
                    $item['quality_score'] >= 75 &&
                    $item['quality_score'] < 90
            )
            ->count();

        $medium = $resultsCollection
            ->filter(
                fn ($item) =>
                    $item['quality_score'] >= 60 &&
                    $item['quality_score'] < 75
            )
            ->count();

        $weak = $resultsCollection
            ->filter(
                fn ($item) =>
                    $item['quality_score'] < 60
            )
            ->count();

        /*
         * =========================================================
         * Report
         * =========================================================
         */

        $this->info('----------------------------------------------');
        $this->info('GENERAL STATISTICS');
        $this->info('----------------------------------------------');

        $this->line(
            "Total news:              {$total}"
        );

        $this->line(
            "AI processed:            {$aiProcessed}"
        );

        $this->line(
            "AI not processed:        {$aiNotProcessed}"
        );

        $this->line(
            "Average quality score:   {$averageScore}/100"
        );

        $this->newLine();

        /*
         * =========================================================
         * Classification
         * =========================================================
         */

        $this->info('QUALITY CLASSIFICATION');

        $this->line(
            "🟢 READY:                 {$ready}"
        );

        $this->line(
            "🟡 REPAIR:                {$repair}"
        );

        $this->line(
            "🟠 REVIEW:                {$review}"
        );

        $this->line(
            "🔴 DELETE CANDIDATE:      {$delete}"
        );

        $this->newLine();

        /*
         * =========================================================
         * Quality score
         * =========================================================
         */

        $this->info('QUALITY SCORE');

        $this->line(
            "🟢 Excellent (90-100):    {$excellent}"
        );

        $this->line(
            "🟢 Good (75-89):          {$good}"
        );

        $this->line(
            "🟡 Medium (60-74):        {$medium}"
        );

        $this->line(
            "🔴 Weak (<60):            {$weak}"
        );

        $this->newLine();

        /*
         * =========================================================
         * Action recommendations
         * =========================================================
         */

        $this->info('RECOMMENDED ACTIONS');

        $this->line(
            "🤖 AI ONLY:               {$aiOnly}"
        );

        $this->line(
            "🔄 REFETCH CONTENT:       {$refetch}"
        );

        $this->line(
            "👁 MANUAL REVIEW:         {$manualReview}"
        );

        $this->line(
            "🔁 DUPLICATE/SIMILAR:     {$duplicateCount}"
        );

        $this->newLine();

        /*
         * =========================================================
         * Missing fields
         * =========================================================
         */

        $this->info('----------------------------------------------');
        $this->info('MISSING FIELDS');
        $this->info('----------------------------------------------');

        foreach ($missing as $field => $count) {
            $this->line(
                str_pad($field, 24) . ': ' . $count
            );
        }

        $this->newLine();

        /*
         * =========================================================
         * Original content
         * =========================================================
         */

        $this->info('----------------------------------------------');
        $this->info('ORIGINAL CONTENT LENGTH');
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
         * Duplicate statistics
         * =========================================================
         */

        $this->info('----------------------------------------------');
        $this->info('DUPLICATE ANALYSIS');
        $this->info('----------------------------------------------');

        $duplicateGroups = collect($duplicateMap)
            ->filter(
                fn ($ids) => count($ids) > 1
            )
            ->count();

        $this->line(
            "Duplicate/similar groups: {$duplicateGroups}"
        );

        $this->line(
            "Articles affected:        {$duplicateCount}"
        );

        $this->newLine();

        /*
         * =========================================================
         * Detailed lists
         * =========================================================
         */

        $this->displayResults(
            $results,
            'READY',
            '🟢 READY',
            $this->option('show-ready') || $this->option('show-all')
        );

        $this->displayResults(
            $results,
            'REPAIR',
            '🟡 REPAIR',
            $this->option('show-repair') || $this->option('show-all')
        );

        $this->displayResults(
            $results,
            'REVIEW',
            '🟠 REVIEW',
            $this->option('show-review') || $this->option('show-all')
        );

        $this->displayResults(
            $results,
            'DELETE',
            '🔴 DELETE CANDIDATE',
            $this->option('show-delete') || $this->option('show-all')
        );

        /*
         * =========================================================
         * Duplicate groups
         * =========================================================
         */

        if (
            $this->option('show-duplicates') ||
            $this->option('show-all')
        ) {
            $this->displayDuplicateGroups(
                $news,
                $duplicateMap
            );
        }

        /*
         * =========================================================
         * Final summary
         * =========================================================
         */

        $this->info('==============================================');
        $this->info('AUDIT COMPLETED');
        $this->info('==============================================');

        $this->line(
            "Total:       {$total}"
        );

        $this->line(
            "🟢 Ready:    {$ready}"
        );

        $this->line(
            "🟡 Repair:   {$repair}"
        );

        $this->line(
            "🟠 Review:   {$review}"
        );

        $this->line(
            "🔴 Delete:   {$delete}"
        );

        $this->newLine();

        $this->comment(
            'No database records were modified or deleted.'
        );

        $this->newLine();

        return self::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | Audit one article
    |--------------------------------------------------------------------------
    */

    private function auditNews(
        News $item,
        array $duplicateMap
    ): array {

        $issues = [];

        /*
         * =========================================================
         * Titles
         * =========================================================
         */

        $titleEnMissing = $this->isEmpty($item->title_en);
        $titleArMissing = $this->isEmpty($item->title_ar);

        if ($titleEnMissing) {
            $issues[] = 'missing title_en';
        }

        if ($titleArMissing) {
            $issues[] = 'missing title_ar';
        }

        /*
         * =========================================================
         * Original content
         * =========================================================
         */

        $contentEnLength = mb_strlen(
            trim((string) $item->content_en)
        );

        $contentArLength = mb_strlen(
            trim((string) $item->content_ar)
        );

        $originalContentLength = $contentEnLength;

        /*
         * إذا لم يوجد الإنجليزي،
         * نستخدم العربي كبديل لحساب المحتوى.
         */

        if ($originalContentLength === 0) {
            $originalContentLength = $contentArLength;
        }

        if ($contentEnLength === 0) {
            $issues[] = 'missing original content_en';
        }

        if ($contentArLength === 0) {
            $issues[] = 'missing Arabic content_ar';
        }

        /*
         * المحتوى الأصلي قصير
         */

        if (
            $originalContentLength > 0 &&
            $originalContentLength < $this->veryShortContent
        ) {
            $issues[] =
                'original content very short';
        } elseif (
            $originalContentLength > 0 &&
            $originalContentLength < $this->shortContent
        ) {
            $issues[] =
                'original content short';
        }

        /*
         * =========================================================
         * AI fields
         * =========================================================
         */

        $missingAiFields = [];

        foreach ($this->aiFields as $field) {
            if ($this->isEmpty($item->{$field})) {
                $missingAiFields[] = $field;
            }
        }

        if (!empty($missingAiFields)) {
            $issues[] =
                'missing AI fields: ' .
                implode(', ', $missingAiFields);
        }

        if (!$item->ai_processed) {
            $issues[] = 'ai not processed';
        }

        /*
         * =========================================================
         * Source
         * =========================================================
         */

        $missingUrl = $this->isEmpty($item->url);
        $missingSource = $this->isEmpty($item->source);

        if ($missingSource) {
            $issues[] = 'missing source';
        }

        if ($missingUrl) {
            $issues[] = 'missing source URL';
        }

        /*
         * =========================================================
         * Category
         * =========================================================
         */

        $missingCategory = $this->isEmpty(
            $item->category
        );

        if ($missingCategory) {
            $issues[] = 'missing category';
        }

        /*
         * =========================================================
         * Image
         * =========================================================
         */

        $missingImage = $this->isEmpty(
            $item->image_url
        );

        if ($missingImage) {
            $issues[] = 'missing image';
        }

        /*
         * =========================================================
         * AI + weak original content
         * =========================================================
         */

        $aiPresent = (bool) $item->ai_processed;

        $originalWeak =
            $originalContentLength > 0 &&
            $originalContentLength < $this->veryShortContent;

        $originalShort =
            $originalContentLength >= $this->veryShortContent &&
            $originalContentLength < $this->shortContent;

        if ($aiPresent && $originalWeak) {
            $issues[] =
                'AI exists but original source material is weak';
        }

        /*
         * =========================================================
         * Duplicate
         * =========================================================
         */

        $duplicate = false;
        $duplicateOf = [];

        foreach ($duplicateMap as $group) {

            if (
                count($group) > 1 &&
                in_array($item->id, $group, true)
            ) {

                $duplicate = true;

                $duplicateOf = array_values(
                    array_filter(
                        $group,
                        fn ($id) => $id !== $item->id
                    )
                );

                break;
            }
        }

        if ($duplicate) {
            $issues[] =
                'possible duplicate or very similar article';
        }

        /*
         * =========================================================
         * Quality score
         * =========================================================
         */

        $score = $this->calculateQualityScore(
            $item,
            $originalContentLength,
            $missingAiFields,
            $duplicate
        );

        /*
         * =========================================================
         * Recommendation
         * =========================================================
         */

        $recommendation =
            $this->determineRecommendation(
                $item,
                $originalContentLength,
                $missingAiFields,
                $duplicate
            );

        /*
         * =========================================================
         * Status
         * =========================================================
         */

        $status =
            $this->determineStatus(
                $item,
                $originalContentLength,
                $missingAiFields,
                $duplicate,
                $score,
                $recommendation
            );

        /*
         * =========================================================
         * Detailed diagnosis
         * =========================================================
         */

        $diagnosis = $this->buildDiagnosis(
            $item,
            $originalContentLength,
            $missingAiFields,
            $duplicate,
            $missingUrl,
            $missingCategory
        );

        return [
            'id' => $item->id,

            'title' =>
                $item->title_en
                ?: $item->title_ar
                ?: 'Untitled',

            'status' => $status,

            'quality_score' => $score,

            'recommendation' => $recommendation,

            'diagnosis' => $diagnosis,

            'issues' => array_values(
                array_unique($issues)
            ),

            'content_length' =>
                $originalContentLength,

            'content_ar_length' =>
                $contentArLength,

            'content_en_length' =>
                $contentEnLength,

            'ai_processed' =>
                $aiPresent,

            'source' =>
                $item->source,

            'url' =>
                $item->url,

            'category' =>
                $item->category,

            'duplicate' =>
                $duplicate,

            'duplicate_of' =>
                $duplicateOf,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Quality score
    |--------------------------------------------------------------------------
    */

    private function calculateQualityScore(
        News $item,
        int $contentLength,
        array $missingAiFields,
        bool $duplicate
    ): int {

        $score = 100;

        /*
         * Original content
         */

        if ($contentLength === 0) {
            $score -= 40;
        } elseif ($contentLength < 300) {
            $score -= 30;
        } elseif ($contentLength < 700) {
            $score -= 18;
        } elseif ($contentLength < 1500) {
            $score -= 5;
        }

        /*
         * Arabic content
         */

        if ($this->isEmpty($item->content_ar)) {
            $score -= 10;
        }

        /*
         * Arabic title
         */

        if ($this->isEmpty($item->title_ar)) {
            $score -= 5;
        }

        /*
         * AI
         */

        if (!$item->ai_processed) {
            $score -= 10;
        }

        /*
         * Missing AI fields
         */

        $score -= min(
            count($missingAiFields) * 4,
            24
        );

        /*
         * Source URL
         */

        if ($this->isEmpty($item->url)) {
            $score -= 5;
        }

        /*
         * Category
         */

        if ($this->isEmpty($item->category)) {
            $score -= 4;
        }

        /*
         * Source
         */

        if ($this->isEmpty($item->source)) {
            $score -= 3;
        }

        /*
         * Image
         */

        if ($this->isEmpty($item->image_url)) {
            $score -= 3;
        }

        /*
         * Duplicate
         */

        if ($duplicate) {
            $score -= 20;
        }

        return max(
            0,
            min(100, $score)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Recommendation
    |--------------------------------------------------------------------------
    */

    private function determineRecommendation(
        News $item,
        int $contentLength,
        array $missingAiFields,
        bool $duplicate
    ): string {

        /*
         * لا محتوى أصلي
         */

        if ($contentLength === 0) {
            return 'DELETE_OR_REFETCH';
        }

        /*
         * تكرار محتمل
         */

        if ($duplicate) {
            return 'MANUAL_REVIEW';
        }

        /*
         * محتوى ضعيف جداً
         *
         * هنا AI وحده لا يكفي.
         * الأفضل إعادة جلب المصدر.
         */

        if ($contentLength < 300) {
            return 'REFETCH';
        }

        /*
         * محتوى قصير
         *
         * قد يكون خبراً قصيراً حقيقياً.
         * نحتاج مراجعة قبل الحذف.
         */

        if ($contentLength < 700) {

            if (!empty($missingAiFields)) {
                return 'REFETCH_OR_AI';
            }

            return 'MANUAL_REVIEW';
        }

        /*
         * محتوى جيد + AI ناقص
         *
         * المشكلة في AI فقط.
         */

        if (!empty($missingAiFields)) {
            return 'AI_ONLY';
        }

        /*
         * AI غير منفذ
         */

        if (!$item->ai_processed) {
            return 'AI_ONLY';
        }

        /*
         * مشاكل metadata
         */

        if (
            $this->isEmpty($item->url) ||
            $this->isEmpty($item->category) ||
            $this->isEmpty($item->title_ar)
        ) {
            return 'METADATA_REPAIR';
        }

        return 'NONE';
    }

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    private function determineStatus(
        News $item,
        int $contentLength,
        array $missingAiFields,
        bool $duplicate,
        int $score,
        string $recommendation
    ): string {

        /*
         * لا يوجد أي محتوى.
         */

        if ($contentLength === 0) {
            return 'DELETE';
        }

        /*
         * محتوى ضعيف جداً.
         */

        if ($contentLength < 300) {
            return 'REVIEW';
        }

        /*
         * Duplicate
         */

        if ($duplicate) {
            return 'REVIEW';
        }

        /*
         * Score ضعيف.
         */

        if ($score < 50) {
            return 'REVIEW';
        }

        /*
         * محتوى قصير.
         */

        if ($contentLength < 700) {
            return 'REVIEW';
        }

        /*
         * أي نقص قابل للإصلاح.
         */

        if (
            !empty($missingAiFields) ||
            !$item->ai_processed ||
            $this->isEmpty($item->title_ar) ||
            $this->isEmpty($item->category) ||
            $this->isEmpty($item->url)
        ) {
            return 'REPAIR';
        }

        /*
         * Score جيد
         */

        if ($score >= 75) {
            return 'READY';
        }

        return 'REPAIR';
    }

    /*
    |--------------------------------------------------------------------------
    | Diagnosis
    |--------------------------------------------------------------------------
    */

    private function buildDiagnosis(
        News $item,
        int $contentLength,
        array $missingAiFields,
        bool $duplicate,
        bool $missingUrl,
        bool $missingCategory
    ): string {

        if ($contentLength === 0) {
            return 'No usable original article content.';
        }

        if ($duplicate) {
            return 'Possible duplicate or highly similar article.';
        }

        if ($contentLength < 300) {
            return 'Original source material is too short; refetch is recommended.';
        }

        if (
            $contentLength < 700 &&
            !empty($missingAiFields)
        ) {
            return 'Original article is short and AI fields are incomplete.';
        }

        if (!empty($missingAiFields)) {
            return 'Original article is usable; AI enrichment is incomplete.';
        }

        if (!$item->ai_processed) {
            return 'Article content is usable but AI processing is missing.';
        }

        if ($missingUrl) {
            return 'Article is usable but source URL is missing.';
        }

        if ($missingCategory) {
            return 'Article is usable but category is missing.';
        }

        return 'Article appears complete.';
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicate detection
    |--------------------------------------------------------------------------
    */

    private function detectDuplicates($news): array
    {
        $groups = [];

        $normalizedTitles = [];

        foreach ($news as $item) {

            $title =
                $item->title_en
                ?: $item->title_ar
                ?: '';

            $normalizedTitles[$item->id] =
                $this->normalizeText($title);
        }

        /*
         * =========================================================
         * Exact normalized title
         * =========================================================
         */

        foreach ($normalizedTitles as $id => $title) {

            if ($title === '') {
                continue;
            }

            $groups[$title][] = $id;
        }

        /*
         * =========================================================
         * Similar titles
         * =========================================================
         */

        $ids = array_keys($normalizedTitles);

        $similarGroups = [];

        $count = count($ids);

        for ($i = 0; $i < $count; $i++) {

            $idA = $ids[$i];

            $titleA =
                $normalizedTitles[$idA];

            if ($titleA === '') {
                continue;
            }

            for ($j = $i + 1; $j < $count; $j++) {

                $idB = $ids[$j];

                $titleB =
                    $normalizedTitles[$idB];

                if ($titleB === '') {
                    continue;
                }

                /*
                 * إذا كان أحد العنوانين قصيراً جداً
                 * لا نعتمد التشابه وحده.
                 */

                if (
                    mb_strlen($titleA) < 20 ||
                    mb_strlen($titleB) < 20
                ) {
                    continue;
                }

                similar_text(
                    $titleA,
                    $titleB,
                    $percent
                );

                /*
                 * 85% أو أكثر = شبه مكرر
                 */

                if ($percent >= 85) {

                    $similarGroups[] = [
                        $idA,
                        $idB,
                    ];
                }
            }
        }

        /*
         * =========================================================
         * Merge groups
         * =========================================================
         */

        $allGroups = [];

        foreach ($groups as $group) {

            if (count($group) > 1) {
                $allGroups[] = $group;
            }
        }

        foreach ($similarGroups as $pair) {
            $allGroups[] = $pair;
        }

        /*
         * دمج المجموعات المتداخلة
         */

        $merged = [];

        foreach ($allGroups as $group) {

            $group = array_values(
                array_unique($group)
            );

            $mergedIntoExisting = false;

            foreach ($merged as &$existing) {

                if (
                    count(
                        array_intersect(
                            $existing,
                            $group
                        )
                    ) > 0
                ) {

                    $existing = array_values(
                        array_unique(
                            array_merge(
                                $existing,
                                $group
                            )
                        )
                    );

                    $mergedIntoExisting = true;

                    break;
                }
            }

            unset($existing);

            if (!$mergedIntoExisting) {
                $merged[] = $group;
            }
        }

        /*
         * =========================================================
         * Return map
         * =========================================================
         */

        $map = [];

        foreach ($merged as $group) {

            if (count($group) < 2) {
                continue;
            }

            foreach ($group as $id) {
                $map[] = $group;
            }
        }

        return $map;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize text
    |--------------------------------------------------------------------------
    */

    private function normalizeText(?string $text): string
    {
        if (!$text) {
            return '';
        }

        $text = mb_strtolower($text);

        /*
         * إزالة علامات الترقيم
         */

        $text = preg_replace(
            '/[^\p{L}\p{N}\s]/u',
            ' ',
            $text
        );

        /*
         * توحيد المسافات
         */

        $text = preg_replace(
            '/\s+/u',
            ' ',
            $text
        );

        return trim($text);
    }

    /*
    |--------------------------------------------------------------------------
    | Empty check
    |--------------------------------------------------------------------------
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

    /*
    |--------------------------------------------------------------------------
    | Original content length
    |--------------------------------------------------------------------------
    */

    private function originalContentLength(
        News $item
    ): int {

        $en = mb_strlen(
            trim((string) $item->content_en)
        );

        /*
         * المحتوى الإنجليزي هو المصدر الأصلي.
         */

        if ($en > 0) {
            return $en;
        }

        /*
         * fallback للعربي
         */

        return mb_strlen(
            trim((string) $item->content_ar)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Display results
    |--------------------------------------------------------------------------
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
                "Quality Score: {$item['quality_score']}/100"
            );

            $this->line(
                "Content: {$item['content_length']} chars"
            );

            $this->line(
                "AI: " .
                ($item['ai_processed']
                    ? 'YES'
                    : 'NO')
            );

            $this->line(
                "Recommendation: " .
                $item['recommendation']
            );

            if (!empty($item['diagnosis'])) {

                $this->line(
                    "Diagnosis: " .
                    $item['diagnosis']
                );
            }

            if ($item['source']) {

                $this->line(
                    "Source: " .
                    $item['source']
                );
            }

            if ($item['url']) {

                $this->line(
                    "URL: " .
                    $item['url']
                );
            }

            if ($item['category']) {

                $this->line(
                    "Category: " .
                    $item['category']
                );
            }

            if ($item['duplicate']) {

                $duplicateIds =
                    implode(
                        ', ',
                        $item['duplicate_of']
                    );

                $this->line(
                    "Duplicate of: {$duplicateIds}"
                );
            }

            if (!empty($item['issues'])) {

                $this->line(
                    'Issues:'
                );

                foreach ($item['issues'] as $issue) {

                    $this->line(
                        "  - {$issue}"
                    );
                }
            }

            $this->newLine();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Display duplicate groups
    |--------------------------------------------------------------------------
    */

    private function displayDuplicateGroups(
        $news,
        array $duplicateMap
    ): void {

        $groups = [];

        foreach ($duplicateMap as $group) {

            $key = implode(
                '-',
                $group
            );

            $groups[$key] = $group;
        }

        $groups = array_values($groups);

        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            '🔁 DUPLICATE / SIMILAR GROUPS (' .
            count($groups) .
            ')'
        );

        $this->info(
            '=============================================='
        );

        if (empty($groups)) {

            $this->line(
                'No duplicate or highly similar groups detected.'
            );

            return;
        }

        foreach ($groups as $index => $group) {

            $this->line(
                'Group #' . ($index + 1)
            );

            foreach ($group as $id) {

                $item = $news->firstWhere(
                    'id',
                    $id
                );

                if (!$item) {
                    continue;
                }

                $title =
                    $item->title_en
                    ?: $item->title_ar
                    ?: 'Untitled';

                $this->line(
                    "  ID {$item->id} | {$title}"
                );
            }

            $this->newLine();
        }
    }
}