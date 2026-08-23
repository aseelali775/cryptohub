<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

class AdsenseNewsAudit extends Command
{
    protected $signature = 'adsense:audit-news
        {--limit=0 : Number of news articles to audit, 0 = all}
        {--min-score=70 : Minimum score considered publishable}
        {--show-all : Show all detailed results}
        {--show-ready : Show ADSENSE READY articles}
        {--show-review : Show articles requiring review}
        {--show-risk : Show high-risk articles}
        {--show-weak : Show articles with low content value}
        {--show-duplicates : Show duplicate/similar groups}
        {--show-ai-risk : Show AI/template repetition risks}
        {--json : Output machine-readable JSON report}';

    protected $description =
        'Read-only AdSense-oriented content quality and originality audit for News';

    /*
    |--------------------------------------------------------------------------
    | Internal thresholds
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | These are INTERNAL QUALITY thresholds.
    | They are NOT official Google AdSense requirements.
    |
    */

    private int $minimumContentLength = 700;

    private int $goodContentLength = 1200;

    private int $strongContentLength = 1800;

    private int $minimumArabicLength = 500;

    private int $minimumAnalysisLength = 250;

    private int $minimumContextLength = 180;

    private int $minimumWhyMattersLength = 150;

    private int $minimumWhatToWatchLength = 150;

    private float $highSimilarity = 85.0;

    private float $veryHighSimilarity = 92.0;

    /*
    |--------------------------------------------------------------------------
    | AI / Enrichment fields
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
    | Main
    |--------------------------------------------------------------------------
    */

    public function handle(): int
    {
        $this->printHeader();

        $query = News::query()
            ->orderBy('id');

        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $news = $query->get();

        if ($news->isEmpty()) {
            $this->warn('No news articles found.');

            return self::SUCCESS;
        }

        $this->info(
            "Auditing {$news->count()} news articles..."
        );

        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | Duplicate analysis
        |--------------------------------------------------------------------------
        */

        $duplicateMap = $this->detectDuplicates($news);

        /*
        |--------------------------------------------------------------------------
        | Article audit
        |--------------------------------------------------------------------------
        */

        $results = [];

        foreach ($news as $item) {
            $results[] = $this->auditArticle(
                $item,
                $duplicateMap
            );
        }

        $collection = collect($results);

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $total = $collection->count();

        $ready = $collection
            ->where('status', 'ADSENSE_READY')
            ->count();

        $review = $collection
            ->where('status', 'ADSENSE_REVIEW')
            ->count();

        $risk = $collection
            ->where('status', 'ADSENSE_RISK')
            ->count();

        $weak = $collection
            ->where('status', 'CONTENT_WEAK')
            ->count();

        $averageScore = round(
            (float) $collection->avg('score'),
            1
        );

        /*
        |--------------------------------------------------------------------------
        | Category statistics
        |--------------------------------------------------------------------------
        */

        $originalityPass = $collection
            ->where('checks.originality.status', 'PASS')
            ->count();

        $valuePass = $collection
            ->where('checks.user_value.status', 'PASS')
            ->count();

        $sourcePass = $collection
            ->where('checks.source.status', 'PASS')
            ->count();

        $structurePass = $collection
            ->where('checks.structure.status', 'PASS')
            ->count();

        $aiRiskCount = $collection
            ->where('checks.ai_risk.status', 'RISK')
            ->count();

        $duplicateCount = $collection
            ->where('checks.duplicates.status', 'RISK')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | JSON
        |--------------------------------------------------------------------------
        */

        if ($this->option('json')) {
            $this->outputJson(
                $results,
                $total,
                $averageScore
            );

            return self::SUCCESS;
        }

        /*
        |--------------------------------------------------------------------------
        | Display report
        |--------------------------------------------------------------------------
        */

        $this->displayGeneralStatistics(
            $total,
            $averageScore
        );

        $this->displayClassification(
            $ready,
            $review,
            $risk,
            $weak
        );

        $this->displayQualityDimensions(
            $originalityPass,
            $valuePass,
            $sourcePass,
            $structurePass,
            $aiRiskCount,
            $duplicateCount,
            $total
        );

        $this->displayRecommendations(
            $results
        );

        /*
        |--------------------------------------------------------------------------
        | Detailed sections
        |--------------------------------------------------------------------------
        */

        $showAll = (bool) $this->option('show-all');

        if (
            $showAll ||
            $this->option('show-ready')
        ) {
            $this->displayResults(
                $results,
                'ADSENSE_READY',
                '🟢 ADSENSE READY'
            );
        }

        if (
            $showAll ||
            $this->option('show-review')
        ) {
            $this->displayResults(
                $results,
                'ADSENSE_REVIEW',
                '🟠 ADSENSE REVIEW'
            );
        }

        if (
            $showAll ||
            $this->option('show-risk')
        ) {
            $this->displayResults(
                $results,
                'ADSENSE_RISK',
                '🔴 ADSENSE RISK'
            );
        }

        if (
            $showAll ||
            $this->option('show-weak')
        ) {
            $this->displayResults(
                $results,
                'CONTENT_WEAK',
                '🔴 CONTENT WEAK'
            );
        }

        if (
            $showAll ||
            $this->option('show-ai-risk')
        ) {
            $this->displayAiRiskResults(
                $results
            );
        }

        if (
            $showAll ||
            $this->option('show-duplicates')
        ) {
            $this->displayDuplicateGroups(
                $news,
                $duplicateMap
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Final
        |--------------------------------------------------------------------------
        */

        $this->displayFinalSummary(
            $total,
            $ready,
            $review,
            $risk,
            $weak,
            $averageScore
        );

        return self::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | Header
    |--------------------------------------------------------------------------
    */

    private function printHeader(): void
    {
        $this->newLine();

        $this->info(
            '======================================================'
        );

        $this->info(
            '        CRYPTOHUB ADSENSE NEWS AUDIT'
        );

        $this->info(
            '======================================================'
        );

        $this->comment(
            'READ ONLY - No database records will be modified.'
        );

        $this->comment(
            'Scores are internal quality indicators, not official Google thresholds.'
        );

        $this->newLine();
    }

    /*
    |--------------------------------------------------------------------------
    | Audit Article
    |--------------------------------------------------------------------------
    */

    private function auditArticle(
        News $item,
        array $duplicateMap
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Basic fields
        |--------------------------------------------------------------------------
        */

        $titleEn = trim((string) $item->title_en);
        $titleAr = trim((string) $item->title_ar);

        $contentEn = trim((string) $item->content_en);
        $contentAr = trim((string) $item->content_ar);

        $source = trim((string) $item->source);
        $url = trim((string) $item->url);
        $category = trim((string) $item->category);

        /*
        |--------------------------------------------------------------------------
        | Lengths
        |--------------------------------------------------------------------------
        */

        $contentEnLength = mb_strlen($contentEn);
        $contentArLength = mb_strlen($contentAr);

        $summaryLength =
            mb_strlen(
                trim(
                    (string) $item->summary_ar
                )
            );

        $analysisLength =
            mb_strlen(
                trim(
                    (string) $item->analysis_ar
                )
            );

        $contextLength =
            mb_strlen(
                trim(
                    (string) $item->context_ar
                )
            );

        $whyLength =
            mb_strlen(
                trim(
                    (string) $item->why_it_matters_ar
                )
            );

        $watchLength =
            mb_strlen(
                trim(
                    (string) $item->what_to_watch_ar
                )
            );

        $limitationsLength =
            mb_strlen(
                trim(
                    (string) $item->limitations_ar
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Duplicate
        |--------------------------------------------------------------------------
        */

        $duplicate = isset(
            $duplicateMap[$item->id]
        );

        $duplicateIds = [];

        if ($duplicate) {
            $duplicateIds =
                array_values(
                    array_filter(
                        $duplicateMap[$item->id],
                        fn ($id) =>
                            $id !== $item->id
                    )
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Checks
        |--------------------------------------------------------------------------
        */

        $checks = [

            'originality' =>
                $this->checkOriginality(
                    $item,
                    $contentEn,
                    $contentAr
                ),

            'user_value' =>
                $this->checkUserValue(
                    $item,
                    $contentAr
                ),

            'source' =>
                $this->checkSource(
                    $item,
                    $source,
                    $url
                ),

            'structure' =>
                $this->checkStructure(
                    $item,
                    $contentEn,
                    $contentAr
                ),

            'ai_risk' =>
                $this->checkAiRisk(
                    $item
                ),

            'duplicates' =>
                $this->checkDuplicates(
                    $duplicate
                ),

            'metadata' =>
                $this->checkMetadata(
                    $item
                ),

            'enrichment' =>
                $this->checkEnrichment(
                    $item,
                    $summaryLength,
                    $analysisLength,
                    $contextLength,
                    $whyLength,
                    $watchLength,
                    $limitationsLength
                ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Score
        |--------------------------------------------------------------------------
        */

        $score = $this->calculateScore(
            $checks,
            $contentEnLength,
            $contentArLength,
            $analysisLength,
            $contextLength,
            $whyLength,
            $watchLength
        );

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $status = $this->determineStatus(
            $score,
            $checks
        );

        /*
        |--------------------------------------------------------------------------
        | Recommendation
        |--------------------------------------------------------------------------
        */

        $recommendation =
            $this->buildRecommendation(
                $status,
                $checks
            );

        /*
        |--------------------------------------------------------------------------
        | Issues
        |--------------------------------------------------------------------------
        */

        $issues =
            $this->collectIssues(
                $checks
            );

        /*
        |--------------------------------------------------------------------------
        | Article title
        |--------------------------------------------------------------------------
        */

        $displayTitle =
            $titleAr
            ?: $titleEn
            ?: 'Untitled';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [

            'id' =>
                $item->id,

            'title' =>
                $displayTitle,

            'title_en' =>
                $titleEn,

            'title_ar' =>
                $titleAr,

            'status' =>
                $status,

            'score' =>
                $score,

            'recommendation' =>
                $recommendation,

            'content_en_length' =>
                $contentEnLength,

            'content_ar_length' =>
                $contentArLength,

            'analysis_length' =>
                $analysisLength,

            'context_length' =>
                $contextLength,

            'why_matters_length' =>
                $whyLength,

            'what_to_watch_length' =>
                $watchLength,

            'limitations_length' =>
                $limitationsLength,

            'ai_processed' =>
                (bool) $item->ai_processed,

            'source' =>
                $source,

            'url' =>
                $url,

            'category' =>
                $category,

            'duplicate' =>
                $duplicate,

            'duplicate_of' =>
                $duplicateIds,

            'checks' =>
                $checks,

            'issues' =>
                $issues,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Originality
    |--------------------------------------------------------------------------
    */

    private function checkOriginality(
        News $item,
        string $contentEn,
        string $contentAr
    ): array {

        $issues = [];

        $score = 0;

        /*
        |--------------------------------------------------------------------------
        | Original source content exists
        |--------------------------------------------------------------------------
        */

        if ($contentEn !== '') {
            $score += 20;
        } elseif ($contentAr !== '') {
            $score += 10;
        } else {
            $issues[] =
                'No original source content exists.';
        }

        /*
        |--------------------------------------------------------------------------
        | Arabic content
        |--------------------------------------------------------------------------
        */

        if (
            mb_strlen($contentAr) >=
            $this->minimumArabicLength
        ) {
            $score += 15;
        } elseif ($contentAr !== '') {
            $score += 7;

            $issues[] =
                'Arabic article content is short.';
        } else {
            $issues[] =
                'Arabic article content is missing.';
        }

        /*
        |--------------------------------------------------------------------------
        | AI enrichment
        |--------------------------------------------------------------------------
        */

        $enrichmentCount = 0;

        foreach ($this->aiFields as $field) {
            if (
                !$this->isEmpty(
                    $item->{$field}
                )
            ) {
                $enrichmentCount++;
            }
        }

        if ($enrichmentCount >= 5) {
            $score += 25;
        } elseif ($enrichmentCount >= 3) {
            $score += 15;
        } elseif ($enrichmentCount >= 1) {
            $score += 7;

            $issues[] =
                'Original value enrichment is incomplete.';
        } else {
            $issues[] =
                'No meaningful content enrichment detected.';
        }

        /*
        |--------------------------------------------------------------------------
        | Analysis
        |--------------------------------------------------------------------------
        */

        $analysis =
            trim(
                (string) $item->analysis_ar
            );

        if (
            mb_strlen($analysis) >=
            $this->minimumAnalysisLength
        ) {
            $score += 20;
        } elseif ($analysis !== '') {
            $score += 8;

            $issues[] =
                'Analysis exists but is short.';
        } else {
            $issues[] =
                'Analysis is missing.';
        }

        /*
        |--------------------------------------------------------------------------
        | Why it matters
        |--------------------------------------------------------------------------
        */

        $why =
            trim(
                (string) $item->why_it_matters_ar
            );

        if (
            mb_strlen($why) >=
            $this->minimumWhyMattersLength
        ) {
            $score += 10;
        } elseif ($why !== '') {
            $score += 5;
        } else {
            $issues[] =
                'Why-it-matters section is missing.';
        }

        /*
        |--------------------------------------------------------------------------
        | Final
        |--------------------------------------------------------------------------
        */

        $score =
            min(
                100,
                $score
            );

        $status =
            $score >= 75
            ? 'PASS'
            : (
                $score >= 55
                ? 'REVIEW'
                : 'FAIL'
            );

        return [
            'score' => $score,
            'status' => $status,
            'issues' => $issues,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | User Value
    |--------------------------------------------------------------------------
    */

    private function checkUserValue(
        News $item,
        string $contentAr
    ): array {

        $score = 0;

        $issues = [];

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $summary =
            trim(
                (string) $item->summary_ar
            );

        if (
            mb_strlen($summary) >= 150
        ) {
            $score += 15;
        } elseif ($summary !== '') {
            $score += 7;

            $issues[] =
                'Summary is short.';
        } else {
            $issues[] =
                'Summary is missing.';
        }

        /*
        |--------------------------------------------------------------------------
        | Analysis
        |--------------------------------------------------------------------------
        */

        $analysis =
            trim(
                (string) $item->analysis_ar
            );

        if (
            mb_strlen($analysis) >=
            $this->minimumAnalysisLength
        ) {
            $score += 25;
        } elseif ($analysis !== '') {
            $score += 10;
        } else {
            $issues[] =
                'No meaningful analysis.';
        }

        /*
        |--------------------------------------------------------------------------
        | Context
        |--------------------------------------------------------------------------
        */

        $context =
            trim(
                (string) $item->context_ar
            );

        if (
            mb_strlen($context) >=
            $this->minimumContextLength
        ) {
            $score += 15;
        } elseif ($context !== '') {
            $score += 7;
        } else {
            $issues[] =
                'Context is missing.';
        }

        /*
        |--------------------------------------------------------------------------
        | Why it matters
        |--------------------------------------------------------------------------
        */

        $why =
            trim(
                (string) $item->why_it_matters_ar
            );

        if (
            mb_strlen($why) >=
            $this->minimumWhyMattersLength
        ) {
            $score += 15;
        } elseif ($why !== '') {
            $score += 7;
        } else {
            $issues[] =
                'Why-it-matters is missing.';
        }

        /*
        |--------------------------------------------------------------------------
        | What to watch
        |--------------------------------------------------------------------------
        */

        $watch =
            trim(
                (string) $item->what_to_watch_ar
            );

        if (
            mb_strlen($watch) >=
            $this->minimumWhatToWatchLength
        ) {
            $score += 15;
        } elseif ($watch !== '') {
            $score += 7;
        } else {
            $issues[] =
                'What-to-watch section is missing.';
        }

        /*
        |--------------------------------------------------------------------------
        | Limitations
        |--------------------------------------------------------------------------
        */

        $limitations =
            trim(
                (string) $item->limitations_ar
            );

        if (
            mb_strlen($limitations) >= 100
        ) {
            $score += 10;
        } elseif ($limitations !== '') {
            $score += 5;
        }

        /*
        |--------------------------------------------------------------------------
        | Original article
        |--------------------------------------------------------------------------
        */

        if (
            mb_strlen($contentAr) >=
            $this->minimumArabicLength
        ) {
            $score += 5;
        }

        $score =
            min(
                100,
                $score
            );

        $status =
            $score >= 75
            ? 'PASS'
            : (
                $score >= 55
                ? 'REVIEW'
                : 'FAIL'
            );

        return [
            'score' => $score,
            'status' => $status,
            'issues' => $issues,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Source
    |--------------------------------------------------------------------------
    */

    private function checkSource(
        News $item,
        string $source,
        string $url
    ): array {

        $score = 0;

        $issues = [];

        if ($source !== '') {
            $score += 40;
        } else {
            $issues[] =
                'Source name is missing.';
        }

        if ($url !== '') {
            $score += 40;

            if (
                filter_var(
                    $url,
                    FILTER_VALIDATE_URL
                ) === false
            ) {
                $issues[] =
                    'Source URL does not appear to be valid.';

                $score -= 20;
            }
        } else {
            $issues[] =
                'Source URL is missing.';
        }

        if (
            !empty($item->title_en) ||
            !empty($item->title_ar)
        ) {
            $score += 20;
        }

        $score =
            max(
                0,
                min(
                    100,
                    $score
                )
            );

        $status =
            $score >= 80
            ? 'PASS'
            : (
                $score >= 60
                ? 'REVIEW'
                : 'FAIL'
            );

        return [
            'score' => $score,
            'status' => $status,
            'issues' => $issues,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Structure
    |--------------------------------------------------------------------------
    */

    private function checkStructure(
        News $item,
        string $contentEn,
        string $contentAr
    ): array {

        $score = 0;

        $issues = [];

        /*
        |--------------------------------------------------------------------------
        | Titles
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isEmpty(
                $item->title_ar
            )
        ) {
            $score += 15;
        } else {
            $issues[] =
                'Arabic title missing.';
        }

        if (
            !$this->isEmpty(
                $item->title_en
            )
        ) {
            $score += 10;
        } else {
            $issues[] =
                'English title missing.';
        }

        /*
        |--------------------------------------------------------------------------
        | Content
        |--------------------------------------------------------------------------
        */

        $length =
            max(
                mb_strlen($contentEn),
                mb_strlen($contentAr)
            );

        if (
            $length >=
            $this->strongContentLength
        ) {
            $score += 30;
        } elseif (
            $length >=
            $this->goodContentLength
        ) {
            $score += 25;
        } elseif (
            $length >=
            $this->minimumContentLength
        ) {
            $score += 18;
        } elseif ($length > 0) {
            $score += 8;

            $issues[] =
                'Article content is short.';
        } else {
            $issues[] =
                'Article has no usable content.';
        }

        /*
        |--------------------------------------------------------------------------
        | Paragraph structure
        |--------------------------------------------------------------------------
        */

        $paragraphs =
            $this->countParagraphs(
                $contentAr ?: $contentEn
            );

        if ($paragraphs >= 5) {
            $score += 20;
        } elseif ($paragraphs >= 3) {
            $score += 15;
        } elseif ($paragraphs >= 2) {
            $score += 8;
        } elseif ($paragraphs > 0) {
            $score += 3;

            $issues[] =
                'Article has weak paragraph structure.';
        }

        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isEmpty(
                $item->category
            )
        ) {
            $score += 15;
        } else {
            $issues[] =
                'Category missing.';
        }

        /*
        |--------------------------------------------------------------------------
        | Image
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isEmpty(
                $item->image_url
            )
        ) {
            $score += 10;
        } else {
            $issues[] =
                'Featured image missing.';
        }

        $score =
            min(
                100,
                $score
            );

        $status =
            $score >= 75
            ? 'PASS'
            : (
                $score >= 55
                ? 'REVIEW'
                : 'FAIL'
            );

        return [
            'score' => $score,
            'status' => $status,
            'issues' => $issues,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | AI Risk
    |--------------------------------------------------------------------------
    */

    private function checkAiRisk(
        News $item
    ): array {

        $score = 0;

        $issues = [];

        /*
        |--------------------------------------------------------------------------
        | AI processed
        |--------------------------------------------------------------------------
        |
        | AI itself is NOT treated as a violation.
        | We are checking whether content appears
        | excessively automated or templated.
        |
        */

        if (
            (bool) $item->ai_processed
        ) {
            $score += 20;
        } else {
            $score += 10;
        }

        /*
        |--------------------------------------------------------------------------
        | Enrichment variety
        |--------------------------------------------------------------------------
        */

        $filled = 0;

        foreach ($this->aiFields as $field) {
            if (
                !$this->isEmpty(
                    $item->{$field}
                )
            ) {
                $filled++;
            }
        }

        if ($filled >= 5) {
            $score += 20;
        } elseif ($filled >= 3) {
            $score += 12;
        } elseif ($filled >= 1) {
            $score += 5;
        }

        /*
        |--------------------------------------------------------------------------
        | Detect repeated template phrases
        |--------------------------------------------------------------------------
        */

        $texts = [];

        foreach ($this->aiFields as $field) {
            $value =
                trim(
                    (string) $item->{$field}
                );

            if ($value !== '') {
                $texts[] = $value;
            }
        }

        $repetition =
            $this->detectInternalRepetition(
                $texts
            );

        if ($repetition >= 0.70) {

            $issues[] =
                'AI enrichment contains highly repetitive phrases.';

            $score -= 35;

        } elseif ($repetition >= 0.45) {

            $issues[] =
                'AI enrichment may contain repeated template language.';

            $score -= 15;
        }

        /*
        |--------------------------------------------------------------------------
        | Generic template phrases
        |--------------------------------------------------------------------------
        */

        $templateHits =
            $this->detectTemplateLanguage(
                $texts
            );

        if ($templateHits >= 4) {

            $issues[] =
                'Multiple generic/template phrases detected.';

            $score -= 25;

        } elseif ($templateHits >= 2) {

            $issues[] =
                'Some generic/template phrases detected.';

            $score -= 10;
        }

        $score =
            max(
                0,
                min(
                    100,
                    $score
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if ($score >= 70) {
            $status = 'PASS';
        } elseif ($score >= 45) {
            $status = 'REVIEW';
        } else {
            $status = 'RISK';
        }

        return [
            'score' => $score,
            'status' => $status,
            'issues' => $issues,
            'template_hits' => $templateHits,
            'internal_repetition' => $repetition,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicate Check
    |--------------------------------------------------------------------------
    */

    private function checkDuplicates(
        bool $duplicate
    ): array {

        if ($duplicate) {
            return [
                'score' => 0,
                'status' => 'RISK',
                'issues' => [
                    'Possible duplicate or highly similar article.'
                ],
            ];
        }

        return [
            'score' => 100,
            'status' => 'PASS',
            'issues' => [],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Metadata
    |--------------------------------------------------------------------------
    */

    private function checkMetadata(
        News $item
    ): array {

        $score = 0;

        $issues = [];

        if (
            !$this->isEmpty(
                $item->title_ar
            )
        ) {
            $score += 20;
        } else {
            $issues[] =
                'Missing Arabic title.';
        }

        if (
            !$this->isEmpty(
                $item->title_en
            )
        ) {
            $score += 15;
        }

        if (
            !$this->isEmpty(
                $item->category
            )
        ) {
            $score += 20;
        } else {
            $issues[] =
                'Missing category.';
        }

        if (
            !$this->isEmpty(
                $item->source
            )
        ) {
            $score += 20;
        } else {
            $issues[] =
                'Missing source.';
        }

        if (
            !$this->isEmpty(
                $item->url
            )
        ) {
            $score += 15;
        } else {
            $issues[] =
                'Missing source URL.';
        }

        if (
            !$this->isEmpty(
                $item->image_url
            )
        ) {
            $score += 10;
        } else {
            $issues[] =
                'Missing image.';
        }

        return [
            'score' => $score,
            'status' =>
                $score >= 80
                ? 'PASS'
                : (
                    $score >= 60
                    ? 'REVIEW'
                    : 'FAIL'
                ),
            'issues' => $issues,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Enrichment
    |--------------------------------------------------------------------------
    */

    private function checkEnrichment(
        News $item,
        int $summaryLength,
        int $analysisLength,
        int $contextLength,
        int $whyLength,
        int $watchLength,
        int $limitationsLength
    ): array {

        $score = 0;

        $issues = [];

        $fields = [

            'summary' => [
                $summaryLength,
                120,
            ],

            'analysis' => [
                $analysisLength,
                $this->minimumAnalysisLength,
            ],

            'context' => [
                $contextLength,
                $this->minimumContextLength,
            ],

            'why_it_matters' => [
                $whyLength,
                $this->minimumWhyMattersLength,
            ],

            'what_to_watch' => [
                $watchLength,
                $this->minimumWhatToWatchLength,
            ],

            'limitations' => [
                $limitationsLength,
                100,
            ],
        ];

        foreach ($fields as $name => $data) {

            [$length, $minimum] = $data;

            if ($length >= $minimum) {

                $score += 100 / count($fields);

            } elseif ($length > 0) {

                $score +=
                    (100 / count($fields)) * 0.5;

                $issues[] =
                    "{$name} is short.";

            } else {

                $issues[] =
                    "{$name} is missing.";
            }
        }

        $score =
            (int) round(
                $score
            );

        return [
            'score' => $score,
            'status' =>
                $score >= 75
                ? 'PASS'
                : (
                    $score >= 50
                    ? 'REVIEW'
                    : 'FAIL'
                ),
            'issues' => $issues,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Overall Score
    |--------------------------------------------------------------------------
    */

    private function calculateScore(
        array $checks,
        int $contentEnLength,
        int $contentArLength,
        int $analysisLength,
        int $contextLength,
        int $whyLength,
        int $watchLength
    ): int {

        /*
        |--------------------------------------------------------------------------
        | Weighted model
        |--------------------------------------------------------------------------
        */

        $weights = [

            'originality' => 25,

            'user_value' => 25,

            'source' => 10,

            'structure' => 10,

            'ai_risk' => 10,

            'duplicates' => 10,

            'metadata' => 5,

            'enrichment' => 5,
        ];

        $score = 0;

        foreach ($weights as $key => $weight) {

            $score +=
                (
                    $checks[$key]['score']
                    * $weight
                ) / 100;
        }

        /*
        |--------------------------------------------------------------------------
        | Content length modifier
        |--------------------------------------------------------------------------
        */

        if (
            $contentEnLength === 0 &&
            $contentArLength === 0
        ) {
            $score -= 25;
        } elseif (
            max(
                $contentEnLength,
                $contentArLength
            ) < $this->minimumContentLength
        ) {
            $score -= 10;
        }

        /*
        |--------------------------------------------------------------------------
        | Analysis modifier
        |--------------------------------------------------------------------------
        */

        if (
            $analysisLength <
            $this->minimumAnalysisLength
        ) {
            $score -= 5;
        }

        /*
        |--------------------------------------------------------------------------
        | Context modifier
        |--------------------------------------------------------------------------
        */

        if (
            $contextLength <
            $this->minimumContextLength
        ) {
            $score -= 3;
        }

        /*
        |--------------------------------------------------------------------------
        | Why matters modifier
        |--------------------------------------------------------------------------
        */

        if (
            $whyLength <
            $this->minimumWhyMattersLength
        ) {
            $score -= 3;
        }

        /*
        |--------------------------------------------------------------------------
        | What to watch modifier
        |--------------------------------------------------------------------------
        */

        if (
            $watchLength <
            $this->minimumWhatToWatchLength
        ) {
            $score -= 3;
        }

        return max(
            0,
            min(
                100,
                (int) round($score)
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Determine Status
    |--------------------------------------------------------------------------
    */

    private function determineStatus(
        int $score,
        array $checks
    ): string {

        /*
        |--------------------------------------------------------------------------
        | Hard risk conditions
        |--------------------------------------------------------------------------
        */

        if (
            $checks['duplicates']['status'] === 'RISK'
        ) {
            return 'ADSENSE_RISK';
        }

        if (
            $checks['originality']['status'] === 'FAIL'
        ) {
            return 'ADSENSE_RISK';
        }

        if (
            $checks['user_value']['status'] === 'FAIL'
        ) {
            return 'CONTENT_WEAK';
        }

        if (
            $checks['source']['status'] === 'FAIL'
        ) {
            return 'ADSENSE_REVIEW';
        }

        if (
            $checks['ai_risk']['status'] === 'RISK'
        ) {
            return 'ADSENSE_REVIEW';
        }

        /*
        |--------------------------------------------------------------------------
        | Score
        |--------------------------------------------------------------------------
        */

        $minimum =
            (int) $this->option(
                'min-score'
            );

        if ($score >= $minimum) {

            if (
                $checks['ai_risk']['status'] !== 'RISK' &&
                $checks['duplicates']['status'] !== 'RISK'
            ) {
                return 'ADSENSE_READY';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Weak
        |--------------------------------------------------------------------------
        */

        if ($score < 50) {
            return 'CONTENT_WEAK';
        }

        return 'ADSENSE_REVIEW';
    }

    /*
    |--------------------------------------------------------------------------
    | Recommendation
    |--------------------------------------------------------------------------
    */

    private function buildRecommendation(
        string $status,
        array $checks
    ): string {

        if (
            $status === 'ADSENSE_READY'
        ) {
            return
                'Suitable for publication from this internal content-quality audit.';
        }

        if (
            $checks['duplicates']['status'] === 'RISK'
        ) {
            return
                'Manual review required. Keep only the strongest distinct article.';
        }

        if (
            $checks['originality']['status'] === 'FAIL'
        ) {
            return
                'Do not publish until meaningful original value is added.';
        }

        if (
            $checks['ai_risk']['status'] === 'RISK'
        ) {
            return
                'Manual editorial review required. Check for repetitive or templated AI output.';
        }

        if (
            $checks['user_value']['status'] === 'FAIL'
        ) {
            return
                'Improve analysis, context, significance and reader value.';
        }

        if (
            $checks['source']['status'] === 'FAIL'
        ) {
            return
                'Repair source attribution and source URL.';
        }

        return
            'Improve the flagged areas before publication.';
    }

    /*
    |--------------------------------------------------------------------------
    | Collect Issues
    |--------------------------------------------------------------------------
    */

    private function collectIssues(
        array $checks
    ): array {

        $issues = [];

        foreach ($checks as $checkName => $check) {

            foreach (
                $check['issues']
                as $issue
            ) {

                $issues[] =
                    strtoupper(
                        $checkName
                    ) .
                    ': ' .
                    $issue;
            }
        }

        return array_values(
            array_unique(
                $issues
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicate Detection
    |--------------------------------------------------------------------------
    */

    private function detectDuplicates(
        $news
    ): array {

        $titles = [];

        foreach ($news as $item) {

            $title =
                $item->title_en
                ?: $item->title_ar
                ?: '';

            $normalized =
                $this->normalizeText(
                    $title
                );

            if ($normalized !== '') {
                $titles[$item->id] =
                    $normalized;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Exact duplicate titles
        |--------------------------------------------------------------------------
        */

        $groups = [];

        foreach ($titles as $id => $title) {
            $groups[$title][] = $id;
        }

        $rawGroups = [];

        foreach ($groups as $group) {

            if (
                count($group) > 1
            ) {
                $rawGroups[] = $group;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Similar titles
        |--------------------------------------------------------------------------
        */

        $ids = array_keys($titles);

        $count = count($ids);

        for (
            $i = 0;
            $i < $count;
            $i++
        ) {

            $idA = $ids[$i];

            $titleA =
                $titles[$idA];

            if (
                mb_strlen($titleA) < 20
            ) {
                continue;
            }

            for (
                $j = $i + 1;
                $j < $count;
                $j++
            ) {

                $idB = $ids[$j];

                $titleB =
                    $titles[$idB];

                if (
                    mb_strlen($titleB) < 20
                ) {
                    continue;
                }

                similar_text(
                    $titleA,
                    $titleB,
                    $percent
                );

                if (
                    $percent >=
                    $this->highSimilarity
                ) {

                    $rawGroups[] = [
                        $idA,
                        $idB,
                    ];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Merge groups
        |--------------------------------------------------------------------------
        */

        $merged = [];

        foreach ($rawGroups as $group) {

            $group =
                array_values(
                    array_unique(
                        $group
                    )
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

                    $existing =
                        array_values(
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
        |--------------------------------------------------------------------------
        | Article map
        |--------------------------------------------------------------------------
        */

        $map = [];

        foreach ($merged as $group) {

            if (
                count($group) < 2
            ) {
                continue;
            }

            sort($group);

            foreach ($group as $id) {
                $map[$id] = $group;
            }
        }

        return $map;
    }

    /*
    |--------------------------------------------------------------------------
    | Internal repetition
    |--------------------------------------------------------------------------
    */

    private function detectInternalRepetition(
        array $texts
    ): float {

        if (
            count($texts) < 2
        ) {
            return 0.0;
        }

        $similarities = [];

        $count = count($texts);

        for (
            $i = 0;
            $i < $count;
            $i++
        ) {

            $a =
                $this->normalizeText(
                    $texts[$i]
                );

            if ($a === '') {
                continue;
            }

            for (
                $j = $i + 1;
                $j < $count;
                $j++
            ) {

                $b =
                    $this->normalizeText(
                        $texts[$j]
                    );

                if ($b === '') {
                    continue;
                }

                similar_text(
                    $a,
                    $b,
                    $percent
                );

                $similarities[] =
                    $percent / 100;
            }
        }

        if (
            empty($similarities)
        ) {
            return 0.0;
        }

        return round(
            max($similarities),
            3
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Template language
    |--------------------------------------------------------------------------
    */

    private function detectTemplateLanguage(
        array $texts
    ): int {

        $templates = [

            'في هذا التقرير',

            'في هذا المقال',

            'من المهم ملاحظة',

            'يجدر بالذكر',

            'تجدر الإشارة',

            'بشكل عام',

            'من ناحية أخرى',

            'في نهاية المطاف',

            'قد يكون من المهم',

            'ينبغي للمستثمرين',

            'على المستثمرين مراقبة',

            'يبقى من المهم مراقبة',

            'من المتوقع أن',

            'قد يؤدي ذلك إلى',

            'هذا التطور قد',

            'what investors should watch',

            'it is important to note',

            'in conclusion',

            'overall',

            'on the other hand',
        ];

        $hits = 0;

        foreach ($texts as $text) {

            $normalized =
                $this->normalizeText(
                    $text
                );

            foreach ($templates as $template) {

                $normalizedTemplate =
                    $this->normalizeText(
                        $template
                    );

                if (
                    str_contains(
                        $normalized,
                        $normalizedTemplate
                    )
                ) {
                    $hits++;
                }
            }
        }

        return $hits;
    }

    /*
    |--------------------------------------------------------------------------
    | Paragraph count
    |--------------------------------------------------------------------------
    */

    private function countParagraphs(
        string $text
    ): int {

        if (
            trim($text) === ''
        ) {
            return 0;
        }

        $paragraphs =
            preg_split(
                '/\R\s*\R/u',
                trim($text)
            );

        $paragraphs =
            array_filter(
                $paragraphs,
                fn ($paragraph) =>
                    trim($paragraph) !== ''
            );

        return count($paragraphs);
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize text
    |--------------------------------------------------------------------------
    */

    private function normalizeText(
        ?string $text
    ): string {

        if (!$text) {
            return '';
        }

        $text =
            mb_strtolower(
                trim($text)
            );

        /*
        |--------------------------------------------------------------------------
        | Arabic normalization
        |--------------------------------------------------------------------------
        */

        $text =
            str_replace(
                [
                    'أ',
                    'إ',
                    'آ',
                    'ٱ',
                ],
                'ا',
                $text
            );

        $text =
            str_replace(
                'ى',
                'ي',
                $text
            );

        $text =
            str_replace(
                'ة',
                'ه',
                $text
            );

        /*
        |--------------------------------------------------------------------------
        | Remove punctuation
        |--------------------------------------------------------------------------
        */

        $text =
            preg_replace(
                '/[^\p{L}\p{N}\s]/u',
                ' ',
                $text
            );

        /*
        |--------------------------------------------------------------------------
        | Normalize spaces
        |--------------------------------------------------------------------------
        */

        $text =
            preg_replace(
                '/\s+/u',
                ' ',
                $text
            );

        return trim(
            $text
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Empty
    |--------------------------------------------------------------------------
    */

    private function isEmpty(
        $value
    ): bool {

        if (
            $value === null
        ) {
            return true;
        }

        if (
            is_string($value)
        ) {
            return trim($value) === '';
        }

        if (
            is_array($value)
        ) {
            return empty($value);
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | General Statistics
    |--------------------------------------------------------------------------
    */

    private function displayGeneralStatistics(
        int $total,
        float $average
    ): void {

        $this->info(
            '------------------------------------------------------'
        );

        $this->info(
            'GENERAL STATISTICS'
        );

        $this->info(
            '------------------------------------------------------'
        );

        $this->line(
            "Total articles:          {$total}"
        );

        $this->line(
            "Average content score:   {$average}/100"
        );

        $this->newLine();
    }

    /*
    |--------------------------------------------------------------------------
    | Classification
    |--------------------------------------------------------------------------
    */

    private function displayClassification(
        int $ready,
        int $review,
        int $risk,
        int $weak
    ): void {

        $this->info(
            'ARTICLE CLASSIFICATION'
        );

        $this->line(
            "🟢 ADSENSE READY:         {$ready}"
        );

        $this->line(
            "🟠 ADSENSE REVIEW:        {$review}"
        );

        $this->line(
            "🔴 ADSENSE RISK:          {$risk}"
        );

        $this->line(
            "🔴 CONTENT WEAK:          {$weak}"
        );

        $this->newLine();
    }

    /*
    |--------------------------------------------------------------------------
    | Quality Dimensions
    |--------------------------------------------------------------------------
    */

    private function displayQualityDimensions(
        int $originality,
        int $value,
        int $source,
        int $structure,
        int $aiRisk,
        int $duplicates,
        int $total
    ): void {

        $this->info(
            'QUALITY DIMENSIONS'
        );

        $this->line(
            "Originality PASS:         {$originality}/{$total}"
        );

        $this->line(
            "User value PASS:          {$value}/{$total}"
        );

        $this->line(
            "Source transparency PASS: {$source}/{$total}"
        );

        $this->line(
            "Structure PASS:           {$structure}/{$total}"
        );

        $this->line(
            "AI/template risks:        {$aiRisk}"
        );

        $this->line(
            "Duplicate risks:          {$duplicates}"
        );

        $this->newLine();
    }

    /*
    |--------------------------------------------------------------------------
    | Recommendations
    |--------------------------------------------------------------------------
    */

    private function displayRecommendations(
        array $results
    ): void {

        $collection =
            collect($results);

        $actions = [

            'ADD_ORIGINAL_VALUE' => 0,

            'IMPROVE_ANALYSIS' => 0,

            'REPAIR_SOURCE' => 0,

            'MANUAL_REVIEW' => 0,

            'REMOVE_DUPLICATE' => 0,

            'REDUCE_TEMPLATE_REPETITION' => 0,

            'NONE' => 0,
        ];

        foreach ($collection as $item) {

            $recommendation =
                $this->recommendationCode(
                    $item
                );

            if (
                isset(
                    $actions[$recommendation]
                )
            ) {
                $actions[$recommendation]++;
            }
        }

        $this->info(
            'RECOMMENDED ACTIONS'
        );

        foreach ($actions as $action => $count) {

            $this->line(
                str_pad(
                    $action,
                    32
                ) .
                ': ' .
                $count
            );
        }

        $this->newLine();
    }

    /*
    |--------------------------------------------------------------------------
    | Recommendation code
    |--------------------------------------------------------------------------
    */

    private function recommendationCode(
        array $item
    ): string {

        $checks =
            $item['checks'];

        if (
            $checks['duplicates']['status'] === 'RISK'
        ) {
            return 'REMOVE_DUPLICATE';
        }

        if (
            $checks['originality']['status'] === 'FAIL'
        ) {
            return 'ADD_ORIGINAL_VALUE';
        }

        if (
            $checks['ai_risk']['status'] === 'RISK'
        ) {
            return 'REDUCE_TEMPLATE_REPETITION';
        }

        if (
            $checks['user_value']['status'] !== 'PASS'
        ) {
            return 'IMPROVE_ANALYSIS';
        }

        if (
            $checks['source']['status'] !== 'PASS'
        ) {
            return 'REPAIR_SOURCE';
        }

        if (
            $item['status'] === 'ADSENSE_REVIEW'
        ) {
            return 'MANUAL_REVIEW';
        }

        return 'NONE';
    }

    /*
    |--------------------------------------------------------------------------
    | Detailed results
    |--------------------------------------------------------------------------
    */

    private function displayResults(
        array $results,
        string $status,
        string $label
    ): void {

        $items =
            collect($results)
                ->where(
                    'status',
                    $status
                )
                ->values();

        $this->newLine();

        $this->info(
            '======================================================'
        );

        $this->info(
            $label .
            ' (' .
            $items->count() .
            ')'
        );

        $this->info(
            '======================================================'
        );

        if (
            $items->isEmpty()
        ) {
            $this->line('None.');

            return;
        }

        foreach ($items as $item) {
            $this->displaySingleResult(
                $item
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Single result
    |--------------------------------------------------------------------------
    */

    private function displaySingleResult(
        array $item
    ): void {

        $this->line(
            "ID {$item['id']} | {$item['title']}"
        );

        $this->line(
            "Score: {$item['score']}/100"
        );

        $this->line(
            "Status: {$item['status']}"
        );

        $this->line(
            "EN Content: {$item['content_en_length']} chars"
        );

        $this->line(
            "AR Content: {$item['content_ar_length']} chars"
        );

        $this->line(
            "Analysis: {$item['analysis_length']} chars"
        );

        $this->line(
            "Context: {$item['context_length']} chars"
        );

        $this->line(
            "Why matters: {$item['why_matters_length']} chars"
        );

        $this->line(
            "What to watch: {$item['what_to_watch_length']} chars"
        );

        $this->line(
            "AI processed: " .
            (
                $item['ai_processed']
                ? 'YES'
                : 'NO'
            )
        );

        $this->line(
            "Source: " .
            (
                $item['source']
                ?: 'MISSING'
            )
        );

        $this->line(
            "Category: " .
            (
                $item['category']
                ?: 'MISSING'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Checks
        |--------------------------------------------------------------------------
        */

        $this->line('');

        $this->line(
            'CHECKS:'
        );

        foreach (
            $item['checks']
            as $name => $check
        ) {

            $this->line(
                '  ' .
                strtoupper($name) .
                ': ' .
                $check['score'] .
                '/100 [' .
                $check['status'] .
                ']'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate
        |--------------------------------------------------------------------------
        */

        if (
            $item['duplicate']
        ) {

            $this->line(
                'Duplicate of: ' .
                implode(
                    ', ',
                    $item['duplicate_of']
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Issues
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $item['issues']
            )
        ) {

            $this->line(
                'ISSUES:'
            );

            foreach (
                $item['issues']
                as $issue
            ) {

                $this->line(
                    '  - ' .
                    $issue
                );
            }
        }

        $this->line(
            'RECOMMENDATION: ' .
            $item['recommendation']
        );

        $this->newLine();
    }

    /*
    |--------------------------------------------------------------------------
    | AI Risk Results
    |--------------------------------------------------------------------------
    */

    private function displayAiRiskResults(
        array $results
    ): void {

        $items =
            collect($results)
                ->filter(
                    fn ($item) =>
                        $item['checks']['ai_risk']['status']
                        === 'RISK'
                        ||
                        $item['checks']['ai_risk']['status']
                        === 'REVIEW'
                )
                ->values();

        $this->newLine();

        $this->info(
            '======================================================'
        );

        $this->info(
            '🤖 AI / TEMPLATE RISK (' .
            $items->count() .
            ')'
        );

        $this->info(
            '======================================================'
        );

        if (
            $items->isEmpty()
        ) {
            $this->line(
                'No significant AI/template risks detected.'
            );

            return;
        }

        foreach ($items as $item) {

            $ai =
                $item['checks']['ai_risk'];

            $this->line(
                "ID {$item['id']} | {$item['title']}"
            );

            $this->line(
                "AI score: {$ai['score']}/100"
            );

            $this->line(
                "Status: {$ai['status']}"
            );

            $this->line(
                "Template hits: {$ai['template_hits']}"
            );

            $this->line(
                "Internal repetition: " .
                $ai['internal_repetition']
            );

            foreach (
                $ai['issues']
                as $issue
            ) {
                $this->line(
                    "  - {$issue}"
                );
            }

            $this->newLine();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicate Groups
    |--------------------------------------------------------------------------
    */

    private function displayDuplicateGroups(
        $news,
        array $duplicateMap
    ): void {

        $groups = [];

        foreach ($duplicateMap as $group) {

            $group =
                array_values(
                    array_unique(
                        $group
                    )
                );

            sort($group);

            $key =
                implode(
                    '-',
                    $group
                );

            $groups[$key] = $group;
        }

        $this->newLine();

        $this->info(
            '======================================================'
        );

        $this->info(
            '🔁 DUPLICATE / SIMILAR GROUPS (' .
            count($groups) .
            ')'
        );

        $this->info(
            '======================================================'
        );

        if (
            empty($groups)
        ) {
            $this->line(
                'No duplicate groups detected.'
            );

            return;
        }

        foreach (
            array_values($groups)
            as $index => $group
        ) {

            $this->line(
                'Group #' .
                ($index + 1)
            );

            foreach ($group as $id) {

                $item =
                    $news->firstWhere(
                        'id',
                        $id
                    );

                if (!$item) {
                    continue;
                }

                $title =
                    $item->title_ar
                    ?: $item->title_en
                    ?: 'Untitled';

                $this->line(
                    "  ID {$id} | {$title}"
                );
            }

            $this->newLine();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Final Summary
    |--------------------------------------------------------------------------
    */

    private function displayFinalSummary(
        int $total,
        int $ready,
        int $review,
        int $risk,
        int $weak,
        float $average
    ): void {

        $this->newLine();

        $this->info(
            '======================================================'
        );

        $this->info(
            'ADSENSE NEWS AUDIT COMPLETED'
        );

        $this->info(
            '======================================================'
        );

        $this->line(
            "Total articles:       {$total}"
        );

        $this->line(
            "Average score:        {$average}/100"
        );

        $this->line(
            "🟢 ADSENSE READY:     {$ready}"
        );

        $this->line(
            "🟠 ADSENSE REVIEW:    {$review}"
        );

        $this->line(
            "🔴 ADSENSE RISK:      {$risk}"
        );

        $this->line(
            "🔴 CONTENT WEAK:      {$weak}"
        );

        $this->newLine();

        if (
            $risk > 0
        ) {

            $this->error(
                'High-risk articles detected. Do not treat the whole news collection as ready.'
            );

        } elseif (
            $review > 0
        ) {

            $this->warn(
                'Some articles require improvement or manual editorial review.'
            );

        } else {

            $this->info(
                'All audited articles passed the internal content-quality threshold.'
            );
        }

        $this->newLine();

        $this->comment(
            'IMPORTANT: This audit cannot guarantee Google AdSense approval.'
        );

        $this->comment(
            'It is an internal quality/originality/readiness tool.'
        );

        $this->newLine();
    }

    /*
    |--------------------------------------------------------------------------
    | JSON
    |--------------------------------------------------------------------------
    */

    private function outputJson(
        array $results,
        int $total,
        float $average
    ): void {

        $collection =
            collect($results);

        $output = [

            'audit' =>
                'adsense_news',

            'generated_at' =>
                now()->toIso8601String(),

            'total' =>
                $total,

            'average_score' =>
                $average,

            'classification' => [

                'adsense_ready' =>
                    $collection
                        ->where(
                            'status',
                            'ADSENSE_READY'
                        )
                        ->count(),

                'adsense_review' =>
                    $collection
                        ->where(
                            'status',
                            'ADSENSE_REVIEW'
                        )
                        ->count(),

                'adsense_risk' =>
                    $collection
                        ->where(
                            'status',
                            'ADSENSE_RISK'
                        )
                        ->count(),

                'content_weak' =>
                    $collection
                        ->where(
                            'status',
                            'CONTENT_WEAK'
                        )
                        ->count(),
            ],

            'articles' =>
                $results,
        ];

        $this->line(
            json_encode(
                $output,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            )
        );
    }
}