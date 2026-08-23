<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

class AdsenseNewsAudit extends Command
{
    protected $signature = 'adsense:audit-news
        {--limit=0 : Number of news articles to audit, 0 = all}
        {--min-score=85 : Minimum score considered publishable}
        {--show-all : Show all detailed results}
        {--show-ready : Show ADSENSE READY articles}
        {--show-review : Show articles requiring review}
        {--show-risk : Show high-risk articles}
        {--show-weak : Show articles with low content value}
        {--show-duplicates : Show duplicate/similar groups}
        {--show-clusters : Show topic/event clusters}
        {--show-ai-risk : Show AI/template repetition risks}
        {--json : Output machine-readable JSON report}';

    protected $description =
        'Read-only AdSense-oriented content quality, originality, topic clustering and editorial readiness audit for News';

    /*
    |--------------------------------------------------------------------------
    | Internal Quality Thresholds
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

    private int $veryShortContentLength = 350;

    private int $minimumArabicLength = 500;

    private int $minimumAnalysisLength = 250;

    private int $minimumContextLength = 180;

    private int $minimumWhyMattersLength = 150;

    private int $minimumWhatToWatchLength = 150;

    /*
    |--------------------------------------------------------------------------
    | Similarity Thresholds
    |--------------------------------------------------------------------------
    */

    private float $highSimilarity = 85.0;

    private float $veryHighSimilarity = 92.0;

    /*
    |--------------------------------------------------------------------------
    | Topic/Event clustering threshold
    |--------------------------------------------------------------------------
    |
    | Lower than duplicate threshold deliberately.
    |
    | Example:
    |
    | Article A:
    | Bitcoin rises after ETF inflows
    |
    | Article B:
    | Bitcoin gains as institutional demand increases
    |
    | These may belong to the same EVENT/TOPIC cluster,
    | but they are not necessarily duplicates.
    |
    */

    private float $topicSimilarity = 58.0;

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
        | Duplicate Detection
        |--------------------------------------------------------------------------
        */

        $duplicateMap = $this->detectDuplicates($news);

        /*
        |--------------------------------------------------------------------------
        | Topic/Event Clustering
        |--------------------------------------------------------------------------
        */

        $topicClusters = $this->detectTopicClusters($news);

        $topicMap = $this->buildTopicMap(
            $topicClusters
        );

        /*
        |--------------------------------------------------------------------------
        | Article Audit
        |--------------------------------------------------------------------------
        */

        $results = [];

        foreach ($news as $item) {
            $results[] = $this->auditArticle(
                $item,
                $duplicateMap,
                $topicMap
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
        | Quality Statistics
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

        /*
        |--------------------------------------------------------------------------
        | AI Risk
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | AI risk is informational/editorial.
        | It does NOT automatically mean AdSense risk.
        |
        */

        $aiRiskCount = $collection
            ->where('checks.ai_risk.status', 'RISK')
            ->count();

        $aiReviewCount = $collection
            ->where('checks.ai_risk.status', 'REVIEW')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Duplicate Statistics
        |--------------------------------------------------------------------------
        */

        $duplicateCount = $collection
            ->where('checks.duplicates.status', 'RISK')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Topic Statistics
        |--------------------------------------------------------------------------
        */

        $clusteredArticles = $collection
            ->filter(
                fn ($item) =>
                    !empty($item['topic_cluster'])
            )
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
                $averageScore,
                $topicClusters
            );

            return self::SUCCESS;
        }

        /*
        |--------------------------------------------------------------------------
        | Display Report
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

        $this->displayTopicStatistics(
            count($topicClusters),
            $clusteredArticles
        );

        $this->displayRecommendations(
            $results
        );

        /*
        |--------------------------------------------------------------------------
        | Detailed Sections
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

        if (
            $showAll ||
            $this->option('show-clusters')
        ) {
            $this->displayTopicClusters(
                $news,
                $topicClusters
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
            $averageScore,
            $aiRiskCount,
            $duplicateCount,
            count($topicClusters)
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

        $this->comment(
            'AI/template warnings are editorial signals, not automatic AdSense rejection signals.'
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
        array $duplicateMap,
        array $topicMap
    ): array {

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

        $summaryLength = mb_strlen(
            trim((string) $item->summary_ar)
        );

        $analysisLength = mb_strlen(
            trim((string) $item->analysis_ar)
        );

        $contextLength = mb_strlen(
            trim((string) $item->context_ar)
        );

        $whyLength = mb_strlen(
            trim((string) $item->why_it_matters_ar)
        );

        $watchLength = mb_strlen(
            trim((string) $item->what_to_watch_ar)
        );

        $limitationsLength = mb_strlen(
            trim((string) $item->limitations_ar)
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
            $duplicateIds = array_values(
                array_filter(
                    $duplicateMap[$item->id],
                    fn ($id) =>
                        $id !== $item->id
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Topic/Event Cluster
        |--------------------------------------------------------------------------
        */

        $topicCluster = $topicMap[$item->id] ?? null;

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

            'content_depth' =>
                $this->checkContentDepth(
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
        | Core Issues
        |--------------------------------------------------------------------------
        */

        $coreIssues =
            $this->collectCoreIssues(
                $checks,
                $contentEnLength,
                $contentArLength
            );

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $status = $this->determineStatus(
            $score,
            $checks,
            $coreIssues,
            $contentEnLength,
            $contentArLength
        );

        /*
        |--------------------------------------------------------------------------
        | Recommendation
        |--------------------------------------------------------------------------
        */

        $recommendation =
            $this->buildRecommendation(
                $status,
                $checks,
                $coreIssues
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

            'core_issues' =>
                $coreIssues,

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

            'topic_cluster' =>
                $topicCluster,

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

        if ($contentEn !== '') {
            $score += 20;
        } elseif ($contentAr !== '') {
            $score += 10;
        } else {
            $issues[] =
                'No article content exists.';
        }

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

        $score = min(100, $score);

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

            $issues[] =
                'Analysis provides limited depth.';
        } else {
            $issues[] =
                'No meaningful analysis.';
        }

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

            $issues[] =
                'Context is short.';
        } else {
            $issues[] =
                'Context is missing.';
        }

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
        } elseif (
            $length > 0
        ) {
            $score += 8;

            $issues[] =
                'Article content is short.';
        } else {
            $issues[] =
                'Article has no usable content.';
        }

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
    | Content Depth
    |--------------------------------------------------------------------------
    |
    | Specifically identifies very short articles.
    |
    | This is deliberately separate from AI risk.
    |
    */

    private function checkContentDepth(
        News $item,
        string $contentEn,
        string $contentAr
    ): array {

        $length =
            max(
                mb_strlen($contentEn),
                mb_strlen($contentAr)
            );

        $issues = [];

        if ($length === 0) {

            return [
                'score' => 0,
                'status' => 'FAIL',
                'issues' => [
                    'Article contains no usable body content.'
                ],
                'length' => 0,
            ];
        }

        if (
            $length <
            $this->veryShortContentLength
        ) {

            $issues[] =
                "Article is extremely short ({$length} characters).";

            return [
                'score' => 15,
                'status' => 'FAIL',
                'issues' => $issues,
                'length' => $length,
            ];
        }

        if (
            $length <
            $this->minimumContentLength
        ) {

            $issues[] =
                "Article is below the internal minimum content length ({$length} characters).";

            return [
                'score' => 45,
                'status' => 'REVIEW',
                'issues' => $issues,
                'length' => $length,
            ];
        }

        if (
            $length >=
            $this->strongContentLength
        ) {

            return [
                'score' => 100,
                'status' => 'PASS',
                'issues' => [],
                'length' => $length,
            ];
        }

        if (
            $length >=
            $this->goodContentLength
        ) {

            return [
                'score' => 90,
                'status' => 'PASS',
                'issues' => [],
                'length' => $length,
            ];
        }

        return [
            'score' => 75,
            'status' => 'PASS',
            'issues' => [],
            'length' => $length,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | AI Risk
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | AI usage itself is NOT considered a rejection condition.
    |
    | This check only attempts to identify:
    |
    | - excessive templating
    | - repetitive enrichment
    | - generic language
    |
    | It is an editorial warning.
    |
    */

    private function checkAiRisk(
        News $item
    ): array {

        $score = 70;

        $issues = [];

        if (
            (bool) $item->ai_processed
        ) {
            $score += 10;
        }

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

            $score += 15;

        } elseif ($filled >= 3) {

            $score += 8;

        } elseif ($filled === 0) {

            $score -= 20;

            $issues[] =
                'No enrichment fields detected.';
        }

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
                'AI enrichment contains highly repetitive language.';

            $score -= 30;

        } elseif ($repetition >= 0.45) {

            $issues[] =
                'AI enrichment may contain repeated template language.';

            $score -= 15;
        }

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

            /*
            |--------------------------------------------------------------------------
            | Explicit semantic meaning
            |--------------------------------------------------------------------------
            */

            'ad_sense_blocking' => false,

            'meaning' =>
                'Editorial warning only. AI usage or template similarity is not treated as automatic AdSense rejection.',
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

                $score +=
                    100 / count($fields);

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
            (int) round($score);

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
    | Overall Score
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

            'originality' => 22,

            'user_value' => 25,

            'source' => 10,

            'structure' => 8,

            'content_depth' => 10,

            'ai_risk' => 5,

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
        | Very short content penalty
        |--------------------------------------------------------------------------
        */

        $contentLength =
            max(
                $contentEnLength,
                $contentArLength
            );

        if ($contentLength === 0) {

            $score -= 30;

        } elseif (
            $contentLength <
            $this->veryShortContentLength
        ) {

            $score -= 25;

        } elseif (
            $contentLength <
            $this->minimumContentLength
        ) {

            $score -= 10;
        }

        /*
        |--------------------------------------------------------------------------
        | Analysis
        |--------------------------------------------------------------------------
        */

        if (
            $analysisLength <
            $this->minimumAnalysisLength
        ) {
            $score -= 4;
        }

        /*
        |--------------------------------------------------------------------------
        | Context
        |--------------------------------------------------------------------------
        */

        if (
            $contextLength <
            $this->minimumContextLength
        ) {
            $score -= 2;
        }

        /*
        |--------------------------------------------------------------------------
        | Why matters
        |--------------------------------------------------------------------------
        */

        if (
            $whyLength <
            $this->minimumWhyMattersLength
        ) {
            $score -= 2;
        }

        /*
        |--------------------------------------------------------------------------
        | What to watch
        |--------------------------------------------------------------------------
        */

        if (
            $watchLength <
            $this->minimumWhatToWatchLength
        ) {
            $score -= 2;
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
    | Core Issues
    |--------------------------------------------------------------------------
    |
    | These are the issues that can actually affect classification.
    |
    | AI warnings deliberately do NOT appear here.
    |
    */

    private function collectCoreIssues(
        array $checks,
        int $contentEnLength,
        int $contentArLength
    ): array {

        $issues = [];

        $contentLength =
            max(
                $contentEnLength,
                $contentArLength
            );

        if ($contentLength === 0) {

            $issues[] =
                'NO_CONTENT';

        } elseif (
            $contentLength <
            $this->veryShortContentLength
        ) {

            $issues[] =
                'VERY_SHORT_CONTENT';
        }

        if (
            $checks['duplicates']['status'] === 'RISK'
        ) {
            $issues[] =
                'DUPLICATE_CONTENT';
        }

        if (
            $checks['originality']['status'] === 'FAIL'
        ) {
            $issues[] =
                'LOW_ORIGINAL_VALUE';
        }

        if (
            $checks['user_value']['status'] === 'FAIL'
        ) {
            $issues[] =
                'LOW_USER_VALUE';
        }

        if (
            $checks['source']['status'] === 'FAIL'
        ) {
            $issues[] =
                'SOURCE_PROBLEM';
        }

        return array_values(
            array_unique($issues)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Determine Status
    |--------------------------------------------------------------------------
    */

    private function determineStatus(
        int $score,
        array $checks,
        array $coreIssues,
        int $contentEnLength,
        int $contentArLength
    ): string {

        /*
        |--------------------------------------------------------------------------
        | CONTENT WEAK
        |--------------------------------------------------------------------------
        |
        | Extremely short articles are weak content,
        | not automatically AdSense risk.
        |
        */

        $contentLength =
            max(
                $contentEnLength,
                $contentArLength
            );

        if (
            $contentLength <
            $this->veryShortContentLength
        ) {
            return 'CONTENT_WEAK';
        }

        /*
        |--------------------------------------------------------------------------
        | True hard problems
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

        /*
        |--------------------------------------------------------------------------
        | Source problem
        |--------------------------------------------------------------------------
        */

        if (
            $checks['source']['status'] === 'FAIL'
        ) {
            return 'ADSENSE_REVIEW';
        }

        /*
        |--------------------------------------------------------------------------
        | Score
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | 85+ is publishable if there is no core problem.
        |
        | AI risk does NOT block readiness.
        |
        */

        $minimum =
            (int) $this->option(
                'min-score'
            );

        if (
            $score >= $minimum &&
            empty($coreIssues)
        ) {

            return 'ADSENSE_READY';
        }

        /*
        |--------------------------------------------------------------------------
        | Very weak overall score
        |--------------------------------------------------------------------------
        */

        if (
            $score < 50
        ) {
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
        array $checks,
        array $coreIssues
    ): string {

        /*
        |--------------------------------------------------------------------------
        | Real problems first
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                'DUPLICATE_CONTENT',
                $coreIssues,
                true
            )
        ) {
            return
                'REMOVE_DUPLICATE';
        }

        if (
            in_array(
                'NO_CONTENT',
                $coreIssues,
                true
            )
        ) {
            return
                'ADD_ARTICLE_CONTENT';
        }

        if (
            in_array(
                'VERY_SHORT_CONTENT',
                $coreIssues,
                true
            )
        ) {
            return
                'EXPAND_ARTICLE';
        }

        if (
            in_array(
                'LOW_ORIGINAL_VALUE',
                $coreIssues,
                true
            )
        ) {
            return
                'ADD_ORIGINAL_VALUE';
        }

        if (
            in_array(
                'LOW_USER_VALUE',
                $coreIssues,
                true
            )
        ) {
            return
                'IMPROVE_ANALYSIS';
        }

        if (
            in_array(
                'SOURCE_PROBLEM',
                $coreIssues,
                true
            )
        ) {
            return
                'REPAIR_SOURCE';
        }

        /*
        |--------------------------------------------------------------------------
        | Non-core editorial issues
        |--------------------------------------------------------------------------
        */

        if (
            $checks['ai_risk']['status'] === 'RISK'
        ) {
            return
                'EDITORIAL_REVIEW_AI_PATTERN';
        }

        if (
            $checks['ai_risk']['status'] === 'REVIEW'
        ) {
            return
                'REVIEW_TEMPLATE_LANGUAGE';
        }

        if (
            $checks['user_value']['status'] !== 'PASS'
        ) {
            return
                'IMPROVE_ANALYSIS';
        }

        if (
            $checks['enrichment']['status'] !== 'PASS'
        ) {
            return
                'COMPLETE_ENRICHMENT';
        }

        if (
            $status === 'ADSENSE_REVIEW'
        ) {
            return
                'MANUAL_REVIEW';
        }

        return 'NONE';
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
                    strtoupper($checkName) .
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

        $ids =
            array_keys($titles);

        $count =
            count($ids);

        for (
            $i = 0;
            $i < $count;
            $i++
        ) {

            $idA =
                $ids[$i];

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

                $idB =
                    $ids[$j];

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

        return $this->mergeGroups(
            $rawGroups
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Topic/Event Clustering
    |--------------------------------------------------------------------------
    |
    | This is NOT duplicate detection.
    |
    | It groups articles that appear to discuss
    | the same event/topic.
    |
    */

    private function detectTopicClusters(
        $news
    ): array {

        $documents = [];

        foreach ($news as $item) {

            $title =
                trim(
                    (string) (
                        $item->title_ar
                        ?: $item->title_en
                    )
                );

            $content =
                trim(
                    (string) (
                        $item->content_ar
                        ?: $item->content_en
                    )
                );

            if ($title === '') {
                continue;
            }

            $document =
                $this->normalizeText(
                    $title . ' ' .
                    mb_substr(
                        $content,
                        0,
                        1200
                    )
                );

            $tokens =
                $this->extractTopicTokens(
                    $document
                );

            if (
                count($tokens) < 3
            ) {
                continue;
            }

            $documents[$item->id] = [
                'title' => $title,
                'tokens' => $tokens,
            ];
        }

        $rawGroups = [];

        $ids =
            array_keys($documents);

        $count =
            count($ids);

        for (
            $i = 0;
            $i < $count;
            $i++
        ) {

            $idA =
                $ids[$i];

            for (
                $j = $i + 1;
                $j < $count;
                $j++
            ) {

                $idB =
                    $ids[$j];

                $tokensA =
                    $documents[$idA]['tokens'];

                $tokensB =
                    $documents[$idB]['tokens'];

                $similarity =
                    $this->tokenSimilarity(
                        $tokensA,
                        $tokensB
                    );

                similar_text(
                    $this->normalizeText(
                        $documents[$idA]['title']
                    ),
                    $this->normalizeText(
                        $documents[$idB]['title']
                    ),
                    $titlePercent
                );

                /*
                |--------------------------------------------------------------------------
                | Topic cluster
                |--------------------------------------------------------------------------
                |
                | Either:
                |
                | - strong token overlap
                | - moderate token overlap + title similarity
                |
                */

                if (
                    $similarity >=
                    $this->topicSimilarity
                    ||
                    (
                        $similarity >= 45 &&
                        $titlePercent >= 55
                    )
                ) {

                    $rawGroups[] = [
                        $idA,
                        $idB,
                    ];
                }
            }
        }

        $groups =
            $this->mergeGroups(
                $rawGroups
            );

        /*
        |--------------------------------------------------------------------------
        | Only meaningful clusters
        |--------------------------------------------------------------------------
        */

        return array_values(
            array_filter(
                $groups,
                fn ($group) =>
                    count($group) >= 2
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Topic Map
    |--------------------------------------------------------------------------
    */

    private function buildTopicMap(
        array $clusters
    ): array {

        $map = [];

        foreach (
            $clusters as $index => $cluster
        ) {

            $clusterId =
                'TOPIC-' .
                str_pad(
                    (string) ($index + 1),
                    4,
                    '0',
                    STR_PAD_LEFT
                );

            foreach ($cluster as $id) {

                $map[$id] = [
                    'id' =>
                        $clusterId,

                    'size' =>
                        count($cluster),

                    'articles' =>
                        $cluster,
                ];
            }
        }

        return $map;
    }

    /*
    |--------------------------------------------------------------------------
    | Extract Topic Tokens
    |--------------------------------------------------------------------------
    */

    private function extractTopicTokens(
        string $text
    ): array {

        $stopWords = [

            'من',
            'في',
            'على',
            'الى',
            'إلى',
            'عن',
            'مع',
            'هذا',
            'هذه',
            'ذلك',
            'تلك',
            'التي',
            'الذي',
            'الذين',
            'بعد',
            'قبل',
            'خلال',
            'بين',
            'حول',
            'أمام',
            'ضمن',
            'قد',
            'تم',
            'يتم',
            'كان',
            'كانت',
            'هو',
            'هي',
            'هم',
            'و',
            'او',
            'أو',
            'ثم',
            'كما',
            'لكن',
            'لكنها',
            'بسبب',
            'بشكل',
            'اليوم',
            'أمس',
            'غدا',

            'the',
            'a',
            'an',
            'of',
            'to',
            'in',
            'on',
            'for',
            'and',
            'or',
            'with',
            'from',
            'after',
            'before',
            'this',
            'that',
            'is',
            'are',
        ];

        $words =
            preg_split(
                '/\s+/u',
                trim($text)
            );

        $tokens = [];

        foreach ($words as $word) {

            $word =
                trim($word);

            if ($word === '') {
                continue;
            }

            if (
                mb_strlen($word) < 3
            ) {
                continue;
            }

            if (
                in_array(
                    $word,
                    $stopWords,
                    true
                )
            ) {
                continue;
            }

            $tokens[$word] = true;
        }

        return array_keys($tokens);
    }

    /*
    |--------------------------------------------------------------------------
    | Token Similarity
    |--------------------------------------------------------------------------
    */

    private function tokenSimilarity(
        array $a,
        array $b
    ): float {

        if (
            empty($a) ||
            empty($b)
        ) {
            return 0.0;
        }

        $intersection =
            count(
                array_intersect(
                    $a,
                    $b
                )
            );

        $union =
            count(
                array_unique(
                    array_merge(
                        $a,
                        $b
                    )
                )
            );

        if ($union === 0) {
            return 0.0;
        }

        return round(
            ($intersection / $union) * 100,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Merge Groups
    |--------------------------------------------------------------------------
    */

    private function mergeGroups(
        array $rawGroups
    ): array {

        $merged = [];

        foreach ($rawGroups as $group) {

            $group =
                array_values(
                    array_unique(
                        $group
                    )
                );

            if (
                count($group) < 2
            ) {
                continue;
            }

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

            if (
                !$mergedIntoExisting
            ) {
                $merged[] = $group;
            }
        }

        foreach ($merged as &$group) {
            sort($group);
        }

        unset($group);

        return $merged;
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Repetition
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

        $count =
            count($texts);

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
    | Template Language
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
    | Paragraph Count
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
    | Normalize Text
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

        $text =
            preg_replace(
                '/[^\p{L}\p{N}\s]/u',
                ' ',
                $text
            );

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
            "AI/template warnings:     {$aiRisk}"
        );

        $this->line(
            "Duplicate risks:          {$duplicates}"
        );

        $this->newLine();
    }

    /*
    |--------------------------------------------------------------------------
    | Topic Statistics
    |--------------------------------------------------------------------------
    */

    private function displayTopicStatistics(
        int $clusters,
        int $articles
    ): void {

        $this->info(
            'TOPIC / EVENT CLUSTERING'
        );

        $this->line(
            "Topic/event clusters:     {$clusters}"
        );

        $this->line(
            "Articles in clusters:     {$articles}"
        );

        $this->comment(
            'Note: Topic clustering does not mean duplicate content.'
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

            'ADD_ARTICLE_CONTENT' => 0,

            'EXPAND_ARTICLE' => 0,

            'ADD_ORIGINAL_VALUE' => 0,

            'IMPROVE_ANALYSIS' => 0,

            'REPAIR_SOURCE' => 0,

            'MANUAL_REVIEW' => 0,

            'REMOVE_DUPLICATE' => 0,

            'COMPLETE_ENRICHMENT' => 0,

            'EDITORIAL_REVIEW_AI_PATTERN' => 0,

            'REVIEW_TEMPLATE_LANGUAGE' => 0,

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
                    38
                ) .
                ': ' .
                $count
            );
        }

        $this->newLine();
    }

    /*
    |--------------------------------------------------------------------------
    | Recommendation Code
    |--------------------------------------------------------------------------
    */

    private function recommendationCode(
        array $item
    ): string {

        $checks =
            $item['checks'];

        $coreIssues =
            $item['core_issues'] ?? [];

        if (
            in_array(
                'DUPLICATE_CONTENT',
                $coreIssues,
                true
            )
        ) {
            return 'REMOVE_DUPLICATE';
        }

        if (
            in_array(
                'NO_CONTENT',
                $coreIssues,
                true
            )
        ) {
            return 'ADD_ARTICLE_CONTENT';
        }

        if (
            in_array(
                'VERY_SHORT_CONTENT',
                $coreIssues,
                true
            )
        ) {
            return 'EXPAND_ARTICLE';
        }

        if (
            in_array(
                'LOW_ORIGINAL_VALUE',
                $coreIssues,
                true
            )
        ) {
            return 'ADD_ORIGINAL_VALUE';
        }

        if (
            in_array(
                'LOW_USER_VALUE',
                $coreIssues,
                true
            )
        ) {
            return 'IMPROVE_ANALYSIS';
        }

        if (
            in_array(
                'SOURCE_PROBLEM',
                $coreIssues,
                true
            )
        ) {
            return 'REPAIR_SOURCE';
        }

        /*
        |--------------------------------------------------------------------------
        | AI is editorial only
        |--------------------------------------------------------------------------
        */

        if (
            $checks['ai_risk']['status'] === 'RISK'
        ) {
            return 'EDITORIAL_REVIEW_AI_PATTERN';
        }

        if (
            $checks['ai_risk']['status'] === 'REVIEW'
        ) {
            return 'REVIEW_TEMPLATE_LANGUAGE';
        }

        if (
            $checks['enrichment']['status'] !== 'PASS'
        ) {
            return 'COMPLETE_ENRICHMENT';
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
    | Detailed Results
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
    | Single Result
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
        | Topic
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $item['topic_cluster']
            )
        ) {

            $cluster =
                $item['topic_cluster'];

            $this->line(
                "Topic cluster: {$cluster['id']} ({$cluster['size']} articles)"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Core Issues
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $item['core_issues']
            )
        ) {

            $this->line(
                'CORE ISSUES:'
            );

            foreach (
                $item['core_issues']
                as $issue
            ) {

                $this->line(
                    '  - ' .
                    $issue
                );
            }
        }

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
        | AI Warning
        |--------------------------------------------------------------------------
        */

        $ai =
            $item['checks']['ai_risk'];

        if (
            $ai['status'] !== 'PASS'
        ) {

            $this->line(
                'AI/TEMPLATE WARNING: ' .
                $ai['status']
            );

            $this->line(
                'AI warning blocks AdSense readiness: NO'
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
            '🤖 AI / TEMPLATE WARNINGS (' .
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
                'No significant AI/template warnings detected.'
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
                "AdSense blocking: NO"
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
    | Topic/Event Clusters
    |--------------------------------------------------------------------------
    */

    private function displayTopicClusters(
        $news,
        array $clusters
    ): void {

        $this->newLine();

        $this->info(
            '======================================================'
        );

        $this->info(
            '🧩 TOPIC / EVENT CLUSTERS (' .
            count($clusters) .
            ')'
        );

        $this->info(
            '======================================================'
        );

        if (
            empty($clusters)
        ) {

            $this->line(
                'No meaningful topic/event clusters detected.'
            );

            return;
        }

        foreach (
            $clusters as $index => $cluster
        ) {

            $clusterId =
                'TOPIC-' .
                str_pad(
                    (string) ($index + 1),
                    4,
                    '0',
                    STR_PAD_LEFT
                );

            $this->line(
                "{$clusterId} | " .
                count($cluster) .
                " related articles"
            );

            foreach ($cluster as $id) {

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
        float $average,
        int $aiRisk,
        int $duplicates,
        int $clusters
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

        $this->line(
            "🤖 AI warnings:       {$aiRisk}"
        );

        $this->line(
            "🔁 Duplicate risks:   {$duplicates}"
        );

        $this->line(
            "🧩 Topic clusters:    {$clusters}"
        );

        $this->newLine();

        if (
            $risk > 0
        ) {

            $this->error(
                'High-risk articles detected. Do not treat the whole news collection as ready.'
            );

        } elseif (
            $weak > 0
        ) {

            $this->warn(
                'Some articles have insufficient content depth and should be improved.'
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
            'AI/template warnings are editorial signals and do not automatically block publication.'
        );

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
        float $average,
        array $topicClusters
    ): void {

        $collection =
            collect($results);

        $output = [

            'audit' =>
                'adsense_news',

            'version' =>
                '2.0',

            'generated_at' =>
                now()->toIso8601String(),

            'read_only' =>
                true,

            'note' =>
                'Internal editorial quality audit. Not an official Google AdSense approval test.',

            'thresholds' => [

                'minimum_publishable_score' =>
                    (int) $this->option(
                        'min-score'
                    ),

                'very_short_content' =>
                    $this->veryShortContentLength,

                'minimum_content' =>
                    $this->minimumContentLength,

                'topic_similarity' =>
                    $this->topicSimilarity,

                'duplicate_similarity' =>
                    $this->highSimilarity,
            ],

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

            'signals' => [

                'ai_warnings' =>
                    $collection
                        ->filter(
                            fn ($item) =>
                                in_array(
                                    $item['checks']['ai_risk']['status'],
                                    [
                                        'RISK',
                                        'REVIEW',
                                    ],
                                    true
                                )
                        )
                        ->count(),

                'duplicate_risks' =>
                    $collection
                        ->where(
                            'checks.duplicates.status',
                            'RISK'
                        )
                        ->count(),

                'topic_clusters' =>
                    count($topicClusters),
            ],

            'articles' =>
                $results,

            'topic_clusters' =>
                $topicClusters,
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