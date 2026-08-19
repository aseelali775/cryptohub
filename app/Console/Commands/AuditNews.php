<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;

class AuditNews extends Command
{
    protected $signature = 'news:audit
        {--limit=0 : Number of news to audit, 0 = all}
        {--show-all : Show all detailed audit results}
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
    | Content Thresholds
    |--------------------------------------------------------------------------
    */

    private int $veryShortContent = 300;

    private int $shortContent = 700;

    private int $acceptableContent = 1500;


    /*
    |--------------------------------------------------------------------------
    | Duplicate Thresholds
    |--------------------------------------------------------------------------
    */

    private float $similarTitleThreshold = 85.0;

    private float $highlySimilarTitleThreshold = 92.0;


    /*
    |--------------------------------------------------------------------------
    | Main Handler
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
            $this->warn('No news found.');

            return self::SUCCESS;
        }

        $this->info(
            "Auditing {$news->count()} news articles..."
        );

        $this->newLine();

        $duplicateMap = $this->detectDuplicates(
            $news
        );

        $results = [];

        foreach ($news as $item) {
            $results[] = $this->auditNews(
                $item,
                $duplicateMap
            );
        }

        $resultsCollection = collect(
            $results
        );

        $total = $resultsCollection->count();

        $ready = $resultsCollection
            ->where(
                'status',
                'READY'
            )
            ->count();

        $repair = $resultsCollection
            ->where(
                'status',
                'REPAIR'
            )
            ->count();

        $review = $resultsCollection
            ->where(
                'status',
                'REVIEW'
            )
            ->count();

        $delete = $resultsCollection
            ->where(
                'status',
                'DELETE_CANDIDATE'
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Recommendation Statistics
        |--------------------------------------------------------------------------
        */

        $recommendationStats = [];

        $recommendations = [
            'AI_ONLY',
            'REFETCH',
            'REFETCH_OR_AI',
            'MANUAL_REVIEW',
            'METADATA_REPAIR',
            'DELETE_OR_REFETCH',
            'NONE',
        ];

        foreach ($recommendations as $recommendation) {
            $recommendationStats[$recommendation] =
                $resultsCollection
                    ->where(
                        'recommendation',
                        $recommendation
                    )
                    ->count();
        }


        /*
        |--------------------------------------------------------------------------
        | Duplicate Statistics
        |--------------------------------------------------------------------------
        */

        $duplicateArticles =
            $resultsCollection
                ->filter(
                    fn ($item) =>
                        $item['duplicate']
                )
                ->count();

        $duplicateGroups =
            $this->countDuplicateGroups(
                $duplicateMap
            );


        /*
        |--------------------------------------------------------------------------
        | AI Statistics
        |--------------------------------------------------------------------------
        */

        $aiProcessed =
            $news
                ->filter(
                    fn ($item) =>
                        (bool) $item->ai_processed
                )
                ->count();

        $aiNotProcessed =
            $news
                ->filter(
                    fn ($item) =>
                        !(bool) $item->ai_processed
                )
                ->count();


        /*
        |--------------------------------------------------------------------------
        | Quality Statistics
        |--------------------------------------------------------------------------
        */

        $averageScore = round(
            (float) $resultsCollection
                ->avg(
                    'quality_score'
                ),
            1
        );

        $excellent =
            $resultsCollection
                ->where(
                    'quality_score',
                    '>=',
                    90
                )
                ->count();

        $good =
            $resultsCollection
                ->filter(
                    fn ($item) =>
                        $item['quality_score'] >= 75 &&
                        $item['quality_score'] < 90
                )
                ->count();

        $medium =
            $resultsCollection
                ->filter(
                    fn ($item) =>
                        $item['quality_score'] >= 60 &&
                        $item['quality_score'] < 75
                )
                ->count();

        $weak =
            $resultsCollection
                ->filter(
                    fn ($item) =>
                        $item['quality_score'] < 60
                )
                ->count();


        /*
        |--------------------------------------------------------------------------
        | Missing Fields
        |--------------------------------------------------------------------------
        */

        $missing =
            $this->calculateMissingFields(
                $news
            );


        /*
        |--------------------------------------------------------------------------
        | Content Statistics
        |--------------------------------------------------------------------------
        */

        $contentStats =
            $this->calculateContentStatistics(
                $news
            );


        /*
        |--------------------------------------------------------------------------
        | Display Report
        |--------------------------------------------------------------------------
        */

        $this->displayGeneralStatistics(
            $total,
            $aiProcessed,
            $aiNotProcessed,
            $averageScore
        );

        $this->displayClassificationStatistics(
            $ready,
            $repair,
            $review,
            $delete
        );

        $this->displayQualityStatistics(
            $excellent,
            $good,
            $medium,
            $weak
        );

        $this->displayRecommendationStatistics(
            $recommendationStats,
            $duplicateArticles
        );

        $this->displayMissingFields(
            $missing
        );

        $this->displayContentStatistics(
            $contentStats
        );

        $this->displayDuplicateStatistics(
            $duplicateGroups,
            $duplicateArticles
        );


        /*
        |--------------------------------------------------------------------------
        | Detailed Results
        |--------------------------------------------------------------------------
        */

        $showAll =
            (bool) $this->option(
                'show-all'
            );


        $this->displayResults(
            $results,
            'READY',
            '🟢 READY',
            $showAll ||
            $this->option(
                'show-ready'
            )
        );

        $this->displayResults(
            $results,
            'REPAIR',
            '🟡 REPAIR',
            $showAll ||
            $this->option(
                'show-repair'
            )
        );

        $this->displayResults(
            $results,
            'REVIEW',
            '🟠 REVIEW',
            $showAll ||
            $this->option(
                'show-review'
            )
        );

        $this->displayResults(
            $results,
            'DELETE_CANDIDATE',
            '🔴 DELETE CANDIDATE',
            $showAll ||
            $this->option(
                'show-delete'
            )
        );


        /*
        |--------------------------------------------------------------------------
        | AI Results
        |--------------------------------------------------------------------------
        */

        if (
            $showAll ||
            $this->option(
                'show-ai'
            )
        ) {
            $this->displayRecommendationResults(
                $results,
                'AI_ONLY',
                '🤖 AI ONLY'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Refetch Results
        |--------------------------------------------------------------------------
        */

        if (
            $showAll ||
            $this->option(
                'show-refetch'
            )
        ) {
            $this->displayRefetchResults(
                $results
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Manual Review
        |--------------------------------------------------------------------------
        */

        if (
            $showAll ||
            $this->option(
                'show-manual'
            )
        ) {
            $this->displayRecommendationResults(
                $results,
                'MANUAL_REVIEW',
                '👁 MANUAL REVIEW'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Weak News
        |--------------------------------------------------------------------------
        */

        if (
            $showAll ||
            $this->option(
                'show-weak'
            )
        ) {
            $this->displayWeakResults(
                $results
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Duplicate Groups
        |--------------------------------------------------------------------------
        */

        if (
            $showAll ||
            $this->option(
                'show-duplicates'
            )
        ) {
            $this->displayDuplicateGroups(
                $news,
                $duplicateMap
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Final Summary
        |--------------------------------------------------------------------------
        */

        $this->displayFinalSummary(
            $total,
            $ready,
            $repair,
            $review,
            $delete,
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
            '=============================================='
        );

        $this->info(
            '        AQL CRYPTO NEWS QUALITY AUDIT'
        );

        $this->info(
            '=============================================='
        );

        $this->comment(
            'READ ONLY - No database records will be modified.'
        );

        $this->newLine();
    }


    /*
    |--------------------------------------------------------------------------
    | Audit One Article
    |--------------------------------------------------------------------------
    */

    private function auditNews(
        News $item,
        array $duplicateMap
    ): array {

        $issues = [];


        /*
        |--------------------------------------------------------------------------
        | Titles
        |--------------------------------------------------------------------------
        */

        $titleEnMissing =
            $this->isEmpty(
                $item->title_en
            );

        $titleArMissing =
            $this->isEmpty(
                $item->title_ar
            );


        if ($titleEnMissing) {
            $issues[] =
                'missing title_en';
        }


        if ($titleArMissing) {
            $issues[] =
                'missing title_ar';
        }


        /*
        |--------------------------------------------------------------------------
        | Original Content
        |--------------------------------------------------------------------------
        */

        $contentEnLength =
            mb_strlen(
                trim(
                    (string) $item->content_en
                )
            );

        $contentArLength =
            mb_strlen(
                trim(
                    (string) $item->content_ar
                )
            );


        $originalContentLength =
            $contentEnLength;


        if (
            $originalContentLength === 0
        ) {
            $originalContentLength =
                $contentArLength;
        }


        if (
            $contentEnLength === 0
        ) {
            $issues[] =
                'missing original content_en';
        }


        if (
            $contentArLength === 0
        ) {
            $issues[] =
                'missing Arabic content_ar';
        }


        /*
        |--------------------------------------------------------------------------
        | Content Quality
        |--------------------------------------------------------------------------
        */

        if (
            $originalContentLength > 0 &&
            $originalContentLength <
            $this->veryShortContent
        ) {
            $issues[] =
                'original content very short';
        }
        elseif (
            $originalContentLength > 0 &&
            $originalContentLength <
            $this->shortContent
        ) {
            $issues[] =
                'original content short';
        }


        /*
        |--------------------------------------------------------------------------
        | AI Fields
        |--------------------------------------------------------------------------
        */

        $missingAiFields = [];


        foreach (
            $this->aiFields
            as $field
        ) {

            if (
                $this->isEmpty(
                    $item->{$field}
                )
            ) {

                $missingAiFields[] =
                    $field;
            }
        }


        if (
            !empty(
                $missingAiFields
            )
        ) {

            $issues[] =
                'missing AI fields: ' .
                implode(
                    ', ',
                    $missingAiFields
                );
        }


        if (
            !(bool) $item->ai_processed
        ) {

            $issues[] =
                'ai not processed';
        }


        /*
        |--------------------------------------------------------------------------
        | Metadata
        |--------------------------------------------------------------------------
        */

        $missingUrl =
            $this->isEmpty(
                $item->url
            );

        $missingSource =
            $this->isEmpty(
                $item->source
            );

        $missingCategory =
            $this->isEmpty(
                $item->category
            );

        $missingImage =
            $this->isEmpty(
                $item->image_url
            );


        if ($missingSource) {
            $issues[] =
                'missing source';
        }


        if ($missingUrl) {
            $issues[] =
                'missing source URL';
        }


        if ($missingCategory) {
            $issues[] =
                'missing category';
        }


        if ($missingImage) {
            $issues[] =
                'missing image';
        }


        /*
        |--------------------------------------------------------------------------
        | Duplicate
        |--------------------------------------------------------------------------
        */

        $duplicate = false;

        $duplicateOf = [];


        if (
            isset(
                $duplicateMap[$item->id]
            )
        ) {

            $group =
                $duplicateMap[$item->id];


            if (
                count($group) > 1
            ) {

                $duplicate =
                    true;


                $duplicateOf =
                    array_values(
                        array_filter(
                            $group,
                            fn ($id) =>
                                $id !==
                                $item->id
                        )
                    );


                $issues[] =
                    'possible duplicate or very similar article';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Weak Original Content
        |--------------------------------------------------------------------------
        */

        if (
            (bool) $item->ai_processed &&
            $originalContentLength > 0 &&
            $originalContentLength <
            $this->veryShortContent
        ) {

            $issues[] =
                'AI exists but original source material is weak';
        }


        /*
        |--------------------------------------------------------------------------
        | Quality Score
        |--------------------------------------------------------------------------
        */

        $score =
            $this->calculateQualityScore(
                $item,
                $originalContentLength,
                $missingAiFields,
                $duplicate
            );


        /*
        |--------------------------------------------------------------------------
        | Recommendation
        |--------------------------------------------------------------------------
        */

        $recommendation =
            $this->determineRecommendation(
                $item,
                $originalContentLength,
                $missingAiFields,
                $duplicate
            );


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Diagnosis
        |--------------------------------------------------------------------------
        */

        $diagnosis =
            $this->buildDiagnosis(
                $item,
                $originalContentLength,
                $missingAiFields,
                $duplicate,
                $missingUrl,
                $missingCategory,
                $missingSource
            );


        return [

            'id' =>
                $item->id,

            'title' =>
                $item->title_en
                ?:
                $item->title_ar
                ?:
                'Untitled',

            'status' =>
                $status,

            'quality_score' =>
                $score,

            'recommendation' =>
                $recommendation,

            'diagnosis' =>
                $diagnosis,

            'issues' =>
                array_values(
                    array_unique(
                        $issues
                    )
                ),

            'content_length' =>
                $originalContentLength,

            'content_ar_length' =>
                $contentArLength,

            'content_en_length' =>
                $contentEnLength,

            'ai_processed' =>
                (bool) $item->ai_processed,

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
    | Quality Score
    |--------------------------------------------------------------------------
    */

    private function calculateQualityScore(
        News $item,
        int $contentLength,
        array $missingAiFields,
        bool $duplicate
    ): int {

        $score = 0;


        /*
        |--------------------------------------------------------------------------
        | Original Content - 30
        |--------------------------------------------------------------------------
        */

        if (
            $contentLength >=
            $this->acceptableContent
        ) {

            $score += 30;
        }
        elseif (
            $contentLength >=
            $this->shortContent
        ) {

            $score += 24;
        }
        elseif (
            $contentLength >=
            $this->veryShortContent
        ) {

            $score += 15;
        }
        elseif (
            $contentLength > 0
        ) {

            $score += 5;
        }


        /*
        |--------------------------------------------------------------------------
        | Arabic - 10
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isEmpty(
                $item->content_ar
            )
        ) {

            $score += 5;
        }


        if (
            !$this->isEmpty(
                $item->title_ar
            )
        ) {

            $score += 5;
        }


        /*
        |--------------------------------------------------------------------------
        | Metadata - 15
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isEmpty(
                $item->url
            )
        ) {

            $score += 4;
        }


        if (
            !$this->isEmpty(
                $item->category
            )
        ) {

            $score += 4;
        }


        if (
            !$this->isEmpty(
                $item->source
            )
        ) {

            $score += 4;
        }


        if (
            !$this->isEmpty(
                $item->image_url
            )
        ) {

            $score += 3;
        }


        /*
        |--------------------------------------------------------------------------
        | AI - 20
        |--------------------------------------------------------------------------
        */

        if (
            (bool) $item->ai_processed
        ) {

            $score += 8;
        }


        $totalAiFields =
            count(
                $this->aiFields
            );


        $completedAiFields =
            $totalAiFields -
            count(
                $missingAiFields
            );


        if (
            $totalAiFields > 0
        ) {

            $score +=
                (int) round(
                    (
                        $completedAiFields /
                        $totalAiFields
                    ) * 12
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Source Integrity - 15
        |--------------------------------------------------------------------------
        */

        if (
            !$this->isEmpty(
                $item->title_en
            )
        ) {

            $score += 3;
        }


        if (
            !$this->isEmpty(
                $item->content_en
            )
        ) {

            $score += 5;
        }


        if (
            !$this->isEmpty(
                $item->source
            )
        ) {

            $score += 3;
        }


        if (
            !$this->isEmpty(
                $item->url
            )
        ) {

            $score += 4;
        }


        /*
        |--------------------------------------------------------------------------
        | Duplicate Risk - 10
        |--------------------------------------------------------------------------
        */

        if (
            !$duplicate
        ) {

            $score += 10;
        }


        return max(
            0,
            min(
                100,
                $score
            )
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

        if (
            $contentLength === 0
        ) {

            return 'DELETE_OR_REFETCH';
        }


        if (
            $duplicate
        ) {

            return 'MANUAL_REVIEW';
        }


        if (
            $contentLength <
            $this->veryShortContent
        ) {

            return 'REFETCH';
        }


        if (
            $contentLength <
            $this->shortContent
        ) {

            if (
                !empty(
                    $missingAiFields
                )
            ) {

                return 'REFETCH_OR_AI';
            }


            if (
                !(bool) $item->ai_processed
            ) {

                return 'AI_ONLY';
            }


            return 'MANUAL_REVIEW';
        }


        if (
            !empty(
                $missingAiFields
            )
        ) {

            return 'AI_ONLY';
        }


        if (
            !(bool) $item->ai_processed
        ) {

            return 'AI_ONLY';
        }


        if (
            $this->isEmpty(
                $item->url
            )
            ||
            $this->isEmpty(
                $item->category
            )
            ||
            $this->isEmpty(
                $item->title_ar
            )
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

        if (
            $contentLength === 0
        ) {

            return 'DELETE_CANDIDATE';
        }


        if (
            $contentLength <
            $this->veryShortContent
        ) {

            return 'REVIEW';
        }


        if (
            $duplicate
        ) {

            return 'REVIEW';
        }


        if (
            $score < 50
        ) {

            return 'REVIEW';
        }


        if (
            $contentLength <
            $this->shortContent
        ) {

            return 'REVIEW';
        }


        if (
            !empty(
                $missingAiFields
            )
            ||
            !(bool) $item->ai_processed
            ||
            $this->isEmpty(
                $item->title_ar
            )
            ||
            $this->isEmpty(
                $item->category
            )
            ||
            $this->isEmpty(
                $item->url
            )
        ) {

            return 'REPAIR';
        }


        if (
            $score >= 75
        ) {

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
        bool $missingCategory,
        bool $missingSource
    ): string {

        if (
            $contentLength === 0
        ) {

            return
                'No usable original article content. Refetch should be attempted before deletion.';
        }


        if (
            $duplicate
        ) {

            return
                'Possible duplicate or highly similar article. Manual review is required.';
        }


        if (
            $contentLength <
            $this->veryShortContent
        ) {

            return
                'Original source material is too short; content refetch is recommended.';
        }


        if (
            $contentLength <
            $this->shortContent
            &&
            !empty(
                $missingAiFields
            )
        ) {

            return
                'Original article is short and AI fields are incomplete.';
        }


        if (
            !empty(
                $missingAiFields
            )
        ) {

            return
                'Original article is usable; AI enrichment is incomplete.';
        }


        if (
            !(bool) $item->ai_processed
        ) {

            return
                'Article content is usable but AI processing is missing.';
        }


        if (
            $missingUrl
        ) {

            return
                'Article is usable but source URL is missing.';
        }


        if (
            $missingCategory
        ) {

            return
                'Article is usable but category is missing.';
        }


        if (
            $missingSource
        ) {

            return
                'Article is usable but source metadata is missing.';
        }


        return
            'Article appears complete.';
    }


    /*
    |--------------------------------------------------------------------------
    | Duplicate Detection
    |--------------------------------------------------------------------------
    */

    private function detectDuplicates(
        $news
    ): array {

        $normalizedTitles = [];


        foreach (
            $news as $item
        ) {

            $title =
                $item->title_en
                ?:
                $item->title_ar
                ?:
                '';


            $normalizedTitles[
                $item->id
            ] =
                $this->normalizeText(
                    $title
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Exact Titles
        |--------------------------------------------------------------------------
        */

        $exactGroups = [];


        foreach (
            $normalizedTitles
            as $id => $title
        ) {

            if (
                $title === ''
            ) {

                continue;
            }


            $exactGroups[
                $title
            ][] =
                $id;
        }


        /*
        |--------------------------------------------------------------------------
        | Candidate Buckets
        |--------------------------------------------------------------------------
        */

        $buckets = [];


        foreach (
            $normalizedTitles
            as $id => $title
        ) {

            if (
                $title === ''
            ) {

                continue;
            }


            $words =
                $this->significantWords(
                    $title
                );


            if (
                count($words) < 2
            ) {

                continue;
            }


            $key =
                implode(
                    '|',
                    array_slice(
                        $words,
                        0,
                        2
                    )
                );


            $buckets[
                $key
            ][] =
                $id;
        }


        /*
        |--------------------------------------------------------------------------
        | Similar Titles
        |--------------------------------------------------------------------------
        */

        $similarPairs = [];


        foreach (
            $buckets
            as $candidateIds
        ) {

            $count =
                count(
                    $candidateIds
                );


            if (
                $count < 2
            ) {

                continue;
            }


            for (
                $i = 0;
                $i < $count;
                $i++
            ) {

                $idA =
                    $candidateIds[$i];


                $titleA =
                    $normalizedTitles[
                        $idA
                    ];


                if (
                    $titleA === ''
                ) {

                    continue;
                }


                for (
                    $j = $i + 1;
                    $j < $count;
                    $j++
                ) {

                    $idB =
                        $candidateIds[$j];


                    $titleB =
                        $normalizedTitles[
                            $idB
                        ];


                    if (
                        $titleB === ''
                    ) {

                        continue;
                    }


                    if (
                        $titleA ===
                        $titleB
                    ) {

                        continue;
                    }


                    if (
                        mb_strlen(
                            $titleA
                        ) < 20
                        ||
                        mb_strlen(
                            $titleB
                        ) < 20
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
                        $this->similarTitleThreshold
                    ) {

                        $similarPairs[] =
                            [
                                $idA,
                                $idB,
                                $percent,
                            ];
                    }
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Raw Groups
        |--------------------------------------------------------------------------
        */

        $rawGroups = [];


        foreach (
            $exactGroups
            as $group
        ) {

            if (
                count($group) > 1
            ) {

                $rawGroups[] =
                    $group;
            }
        }


        foreach (
            $similarPairs
            as $pair
        ) {

            $rawGroups[] =
                [
                    $pair[0],
                    $pair[1],
                ];
        }


        /*
        |--------------------------------------------------------------------------
        | Merge Groups
        |--------------------------------------------------------------------------
        */

        $merged = [];


        foreach (
            $rawGroups
            as $group
        ) {

            $group =
                array_values(
                    array_unique(
                        $group
                    )
                );


            $mergedIntoExisting =
                false;


            foreach (
                $merged
                as &$existing
            ) {

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


                    $mergedIntoExisting =
                        true;


                    break;
                }
            }


            unset(
                $existing
            );


            if (
                !$mergedIntoExisting
            ) {

                $merged[] =
                    $group;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Article Map
        |--------------------------------------------------------------------------
        */

        $map = [];


        foreach (
            $merged
            as $group
        ) {

            if (
                count($group) < 2
            ) {

                continue;
            }


            sort(
                $group
            );


            foreach (
                $group
                as $id
            ) {

                $map[$id] =
                    $group;
            }
        }


        return $map;
    }


    /*
    |--------------------------------------------------------------------------
    | Significant Words
    |--------------------------------------------------------------------------
    */

    private function significantWords(
        string $text
    ): array {

        $words =
            preg_split(
                '/\s+/u',
                trim($text)
            );


        $stopWords = [

            'the',
            'a',
            'an',
            'and',
            'or',
            'of',
            'to',
            'in',
            'on',
            'for',
            'with',
            'after',
            'before',
            'from',
            'by',
            'is',
            'are',
            'was',
            'were',

            'من',
            'في',
            'إلى',
            'الى',
            'على',
            'عن',
            'مع',
            'بعد',
            'قبل',
            'هذا',
            'هذه',
            'ذلك',
            'تلك',
            'و',
            'أو',
            'او',
        ];


        $words =
            array_filter(
                $words,
                function (
                    $word
                ) use (
                    $stopWords
                ) {

                    $word =
                        trim(
                            $word
                        );


                    if (
                        $word === ''
                    ) {

                        return false;
                    }


                    if (
                        mb_strlen(
                            $word
                        ) < 3
                    ) {

                        return false;
                    }


                    return
                        !in_array(
                            $word,
                            $stopWords,
                            true
                        );
                }
            );


        return
            array_values(
                array_unique(
                    $words
                )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Text
    |--------------------------------------------------------------------------
    */

    private function normalizeText(
        ?string $text
    ): string {

        if (
            !$text
        ) {

            return '';
        }


        $text =
            mb_strtolower(
                $text
            );


        /*
        |--------------------------------------------------------------------------
        | Arabic Normalization
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
                [
                    'ى',
                ],
                'ي',
                $text
            );


        $text =
            str_replace(
                [
                    'ة',
                ],
                'ه',
                $text
            );


        /*
        |--------------------------------------------------------------------------
        | Remove Punctuation
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
        | Normalize Whitespace
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
    | Missing Fields
    |--------------------------------------------------------------------------
    */

    private function calculateMissingFields(
        $news
    ): array {

        $missing = [

            'title_ar' =>
                0,

            'title_en' =>
                0,

            'content_ar' =>
                0,

            'content_en' =>
                0,

            'summary_ar' =>
                0,

            'why_it_matters_ar' =>
                0,

            'analysis_ar' =>
                0,

            'context_ar' =>
                0,

            'what_to_watch_ar' =>
                0,

            'limitations_ar' =>
                0,

            'source' =>
                0,

            'url' =>
                0,

            'image_url' =>
                0,

            'category' =>
                0,
        ];


        foreach (
            $news as $item
        ) {

            foreach (
                $missing
                as $field => $count
            ) {

                if (
                    $this->isEmpty(
                        $item->{$field}
                    )
                ) {

                    $missing[$field]++;
                }
            }
        }


        return $missing;
    }


    /*
    |--------------------------------------------------------------------------
    | Content Statistics
    |--------------------------------------------------------------------------
    */

    private function calculateContentStatistics(
        $news
    ): array {

        $stats = [

            'empty' =>
                0,

            'very_short' =>
                0,

            'short' =>
                0,

            'acceptable' =>
                0,

            'strong' =>
                0,
        ];


        foreach (
            $news as $item
        ) {

            $length =
                $this->originalContentLength(
                    $item
                );


            if (
                $length === 0
            ) {

                $stats['empty']++;
            }
            elseif (
                $length <
                $this->veryShortContent
            ) {

                $stats['very_short']++;
            }
            elseif (
                $length <
                $this->shortContent
            ) {

                $stats['short']++;
            }
            elseif (
                $length <
                $this->acceptableContent
            ) {

                $stats['acceptable']++;
            }
            else {

                $stats['strong']++;
            }
        }


        return $stats;
    }


    /*
    |--------------------------------------------------------------------------
    | Original Content Length
    |--------------------------------------------------------------------------
    */

    private function originalContentLength(
        News $item
    ): int {

        $en =
            mb_strlen(
                trim(
                    (string) $item->content_en
                )
            );


        if (
            $en > 0
        ) {

            return $en;
        }


        return
            mb_strlen(
                trim(
                    (string) $item->content_ar
                )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Empty Check
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
            is_string(
                $value
            )
        ) {

            return
                trim(
                    $value
                ) === '';
        }


        if (
            is_array(
                $value
            )
        ) {

            return empty(
                $value
            );
        }


        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Display General Statistics
    |--------------------------------------------------------------------------
    */

    private function displayGeneralStatistics(
        int $total,
        int $aiProcessed,
        int $aiNotProcessed,
        float $averageScore
    ): void {

        $this->info(
            '----------------------------------------------'
        );

        $this->info(
            'GENERAL STATISTICS'
        );

        $this->info(
            '----------------------------------------------'
        );

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
    }


    /*
    |--------------------------------------------------------------------------
    | Display Classification
    |--------------------------------------------------------------------------
    */

    private function displayClassificationStatistics(
        int $ready,
        int $repair,
        int $review,
        int $delete
    ): void {

        $this->info(
            'QUALITY CLASSIFICATION'
        );

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
    }


    /*
    |--------------------------------------------------------------------------
    | Display Quality
    |--------------------------------------------------------------------------
    */

    private function displayQualityStatistics(
        int $excellent,
        int $good,
        int $medium,
        int $weak
    ): void {

        $this->info(
            'QUALITY SCORE'
        );

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
    }


    /*
    |--------------------------------------------------------------------------
    | Display Recommendations
    |--------------------------------------------------------------------------
    */

    private function displayRecommendationStatistics(
        array $stats,
        int $duplicateArticles
    ): void {

        $this->info(
            'RECOMMENDED ACTIONS'
        );

        $this->line(
            "🤖 AI ONLY:               {$stats['AI_ONLY']}"
        );

        $this->line(
            "🔄 REFETCH:               {$stats['REFETCH']}"
        );

        $this->line(
            "🔄 REFETCH OR AI:         {$stats['REFETCH_OR_AI']}"
        );

        $this->line(
            "👁 MANUAL REVIEW:         {$stats['MANUAL_REVIEW']}"
        );

        $this->line(
            "🔧 METADATA REPAIR:       {$stats['METADATA_REPAIR']}"
        );

        $this->line(
            "🗑 DELETE OR REFETCH:     {$stats['DELETE_OR_REFETCH']}"
        );

        $this->line(
            "✅ NONE:                  {$stats['NONE']}"
        );

        $this->line(
            "🔁 DUPLICATE/SIMILAR:     {$duplicateArticles}"
        );

        $this->newLine();
    }


    /*
    |--------------------------------------------------------------------------
    | Display Missing Fields
    |--------------------------------------------------------------------------
    */

    private function displayMissingFields(
        array $missing
    ): void {

        $this->info(
            '----------------------------------------------'
        );

        $this->info(
            'MISSING FIELDS'
        );

        $this->info(
            '----------------------------------------------'
        );


        foreach (
            $missing as $field => $count
        ) {

            $this->line(
                str_pad(
                    $field,
                    24
                ) .
                ': ' .
                $count
            );
        }


        $this->newLine();
    }


    /*
    |--------------------------------------------------------------------------
    | Display Content Statistics
    |--------------------------------------------------------------------------
    */

    private function displayContentStatistics(
        array $stats
    ): void {

        $this->info(
            '----------------------------------------------'
        );

        $this->info(
            'ORIGINAL CONTENT LENGTH'
        );

        $this->info(
            '----------------------------------------------'
        );

        $this->line(
            "Empty:                   {$stats['empty']}"
        );

        $this->line(
            "Very short (<300):       {$stats['very_short']}"
        );

        $this->line(
            "Short (300-699):         {$stats['short']}"
        );

        $this->line(
            "Acceptable (700-1499):   {$stats['acceptable']}"
        );

        $this->line(
            "Strong (1500+):          {$stats['strong']}"
        );

        $this->newLine();
    }


    /*
    |--------------------------------------------------------------------------
    | Display Duplicate Statistics
    |--------------------------------------------------------------------------
    */

    private function displayDuplicateStatistics(
        int $groups,
        int $articles
    ): void {

        $this->info(
            '----------------------------------------------'
        );

        $this->info(
            'DUPLICATE ANALYSIS'
        );

        $this->info(
            '----------------------------------------------'
        );

        $this->line(
            "Duplicate/similar groups: {$groups}"
        );

        $this->line(
            "Articles affected:        {$articles}"
        );

        $this->newLine();
    }


    /*
    |--------------------------------------------------------------------------
    | Display Results
    |--------------------------------------------------------------------------
    */

    private function displayResults(
        array $results,
        string $status,
        string $label,
        bool $show
    ): void {

        if (
            !$show
        ) {

            return;
        }


        $items =
            collect(
                $results
            )
            ->where(
                'status',
                $status
            )
            ->values();


        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            $label .
            ' (' .
            $items->count() .
            ')'
        );

        $this->info(
            '=============================================='
        );


        if (
            $items->isEmpty()
        ) {

            $this->line(
                'None.'
            );

            return;
        }


        foreach (
            $items as $item
        ) {

            $this->displaySingleResult(
                $item
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Recommendation Results
    |--------------------------------------------------------------------------
    */

    private function displayRecommendationResults(
        array $results,
        string $recommendation,
        string $label
    ): void {

        $items =
            collect(
                $results
            )
            ->where(
                'recommendation',
                $recommendation
            )
            ->values();


        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            $label .
            ' (' .
            $items->count() .
            ')'
        );

        $this->info(
            '=============================================='
        );


        if (
            $items->isEmpty()
        ) {

            $this->line(
                'None.'
            );

            return;
        }


        foreach (
            $items as $item
        ) {

            $this->displaySingleResult(
                $item
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Refetch Results
    |--------------------------------------------------------------------------
    */

    private function displayRefetchResults(
        array $results
    ): void {

        $items =
            collect(
                $results
            )
            ->filter(
                fn ($item) =>
                    in_array(
                        $item['recommendation'],
                        [
                            'REFETCH',
                            'REFETCH_OR_AI',
                            'DELETE_OR_REFETCH',
                        ],
                        true
                    )
            )
            ->values();


        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            '🔄 CONTENT REFETCH REQUIRED (' .
            $items->count() .
            ')'
        );

        $this->info(
            '=============================================='
        );


        if (
            $items->isEmpty()
        ) {

            $this->line(
                'None.'
            );

            return;
        }


        foreach (
            $items as $item
        ) {

            $this->displaySingleResult(
                $item
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Weak Results
    |--------------------------------------------------------------------------
    */

    private function displayWeakResults(
        array $results
    ): void {

        $items =
            collect(
                $results
            )
            ->filter(
                fn ($item) =>
                    $item['quality_score'] < 60
            )
            ->sortBy(
                'quality_score'
            )
            ->values();


        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            '🔴 WEAK NEWS (<60) (' .
            $items->count() .
            ')'
        );

        $this->info(
            '=============================================='
        );


        if (
            $items->isEmpty()
        ) {

            $this->line(
                'None.'
            );

            return;
        }


        foreach (
            $items as $item
        ) {

            $this->displaySingleResult(
                $item
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Display Single Result
    |--------------------------------------------------------------------------
    */

    private function displaySingleResult(
        array $item
    ): void {

        $this->line(
            "ID {$item['id']} | {$item['title']}"
        );

        $this->line(
            "Quality Score: {$item['quality_score']}/100"
        );

        $this->line(
            "Status: {$item['status']}"
        );

        $this->line(
            "Content: {$item['content_length']} chars"
        );

        $this->line(
            "Content EN: {$item['content_en_length']} chars"
        );

        $this->line(
            "Content AR: {$item['content_ar_length']} chars"
        );

        $this->line(
            "AI: " .
            (
                $item['ai_processed']
                ? 'YES'
                : 'NO'
            )
        );

        $this->line(
            "Recommendation: {$item['recommendation']}"
        );


        if (
            !empty(
                $item['diagnosis']
            )
        ) {

            $this->line(
                "Diagnosis: {$item['diagnosis']}"
            );
        }


        if (
            !empty(
                $item['source']
            )
        ) {

            $this->line(
                "Source: {$item['source']}"
            );
        }


        if (
            !empty(
                $item['url']
            )
        ) {

            $this->line(
                "URL: {$item['url']}"
            );
        }


        if (
            !empty(
                $item['category']
            )
        ) {

            $this->line(
                "Category: {$item['category']}"
            );
        }


        if (
            $item['duplicate']
        ) {

            $duplicateIds =
                implode(
                    ', ',
                    $item['duplicate_of']
                );


            $this->line(
                "Duplicate of: {$duplicateIds}"
            );
        }


        if (
            !empty(
                $item['issues']
            )
        ) {

            $this->line(
                'Issues:'
            );


            foreach (
                $item['issues']
                as $issue
            ) {

                $this->line(
                    "  - {$issue}"
                );
            }
        }


        $this->newLine();
    }


    /*
    |--------------------------------------------------------------------------
    | Count Duplicate Groups
    |--------------------------------------------------------------------------
    */

    private function countDuplicateGroups(
        array $duplicateMap
    ): int {

        $groups = [];


        foreach (
            $duplicateMap
            as $group
        ) {

            $group =
                array_values(
                    array_unique(
                        $group
                    )
                );


            sort(
                $group
            );


            $key =
                implode(
                    '-',
                    $group
                );


            $groups[$key] =
                true;
        }


        return count(
            $groups
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Display Duplicate Groups
    |--------------------------------------------------------------------------
    */

    private function displayDuplicateGroups(
        $news,
        array $duplicateMap
    ): void {

        $groups = [];


        foreach (
            $duplicateMap
            as $group
        ) {

            $group =
                array_values(
                    array_unique(
                        $group
                    )
                );


            sort(
                $group
            );


            $key =
                implode(
                    '-',
                    $group
                );


            $groups[$key] =
                $group;
        }


        $groups =
            array_values(
                $groups
            );


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


        if (
            empty(
                $groups
            )
        ) {

            $this->line(
                'No duplicate or highly similar groups detected.'
            );

            return;
        }


        foreach (
            $groups
            as $index => $group
        ) {

            $this->line(
                'Group #' .
                ($index + 1)
            );


            foreach (
                $group
                as $id
            ) {

                $item =
                    $news->firstWhere(
                        'id',
                        $id
                    );


                if (
                    !$item
                ) {

                    continue;
                }


                $title =
                    $item->title_en
                    ?:
                    $item->title_ar
                    ?:
                    'Untitled';


                $this->line(
                    "  ID {$item->id} | {$title}"
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
        int $repair,
        int $review,
        int $delete,
        float $averageScore
    ): void {

        $this->newLine();

        $this->info(
            '=============================================='
        );

        $this->info(
            'AUDIT COMPLETED'
        );

        $this->info(
            '=============================================='
        );

        $this->line(
            "Total:                 {$total}"
        );

        $this->line(
            "Average score:         {$averageScore}/100"
        );

        $this->line(
            "🟢 Ready:              {$ready}"
        );

        $this->line(
            "🟡 Repair:             {$repair}"
        );

        $this->line(
            "🟠 Review:             {$review}"
        );

        $this->line(
            "🔴 Delete candidate:   {$delete}"
        );

        $this->newLine();

        $this->comment(
            'No database records were modified, repaired or deleted.'
        );

        $this->comment(
            'This command is strictly READ ONLY.'
        );

        $this->newLine();
    }
}
