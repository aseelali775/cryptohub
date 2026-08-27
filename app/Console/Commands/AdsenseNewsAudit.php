<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AdsenseNewsAudit extends Command
{
    protected $signature = 'adsense:audit-news
        {--limit=0 : Number of news articles to audit, 0 = all}
        {--min-score=80 : Minimum score considered publishable}
        {--show-all : Show all detailed results}
        {--show-ready : Show ADSENSE READY articles}
        {--show-review : Show articles requiring review}
        {--show-risk : Show high-risk articles}
        {--show-weak : Show articles with low content value}
        {--show-duplicates : Show duplicate/similar groups}
        {--show-clusters : Show topic/event clusters}
        {--show-ai-risk : Show AI/template repetition risks}
        {--json : Output machine-readable JSON report}';

    protected $description = 'Read-only CryptoHub Editorial & Quality Audit System';

    private int $minContentLength = 700;
    private int $minArabicLength = 500;
    private int $minAnalysisLength = 250;
    private int $minContextLength = 180;
    private int $minWhyMattersLength = 150;
    private int $minWhatToWatchLength = 150;

    /*
    |--------------------------------------------------------------------------
    | Duplicate / similarity thresholds
    |--------------------------------------------------------------------------
    |
    | Duplicate requires BOTH:
    |   1) title similarity >= 85%
    |   2) content similarity >= 70%
    |
    | Source is intentionally NOT used as an exclusion rule.
    | Different sources can report the same copied/syndicated story.
    |
    */
    private float $titleSimilarityThreshold = 85.0;
    private float $contentSimilarityThreshold = 70.0;

    private array $aiFields = [
        'summary_ar',
        'why_it_matters_ar',
        'analysis_ar',
        'context_ar',
        'what_to_watch_ar',
        'limitations_ar',
    ];

    private array $aiTemplatePhrases = [
        'في هذا التقرير',
        'في هذا المقال',
        'من المهم ملاحظة',
        'يجدر بالذكر',
        'تجدر الإشارة',
        'بشكل عام',
        'في نهاية المطاف',
        'ينبغي للمستثمرين',
        'بشكل ملحوظ',
        'في سياق متصل',
        'على صعيد آخر',
        'من الجدير بالذكر',
        'ختاماً',
        'في الختام',
    ];

    public function handle(): int
    {
        $this->printHeader();

        $limit = max(0, (int) $this->option('limit'));
        $minScore = max(0, min(100, (int) $this->option('min-score')));

        $query = News::query()->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $news = $query->get();

        if ($news->isEmpty()) {
            if ($this->option('json')) {
                $this->outputJson([
                    'success' => true,
                    'total' => 0,
                    'message' => 'No news articles found.',
                ]);

                return self::SUCCESS;
            }

            $this->warn('No news articles found.');

            return self::SUCCESS;
        }

        $duplicateMap = $this->detectDuplicates($news);
        $duplicateGroups = $this->extractDuplicateGroups($duplicateMap);

        $topicClusters = $this->detectTopicClusters($news);
        $topicMap = $this->buildTopicMap($topicClusters);

        $results = [];

        $topReasons = [
            'Missing ORIGINAL VALUE' => 0,
            'Weak ANALYSIS' => 0,
            'Low FACTUAL COMPLETENESS' => 0,
            'Weak STRUCTURE' => 0,
            'Duplicate' => 0,
            'Originality failure' => 0,
            'Short Content / Weak Depth' => 0,
            'High AI Template Risk' => 0,
            'Weak USER VALUE' => 0,
            'Weak METADATA' => 0,
        ];

        foreach ($news as $item) {
            $auditResult = $this->auditArticle(
                $item,
                $duplicateMap,
                $topicMap,
                $minScore
            );

            if ($auditResult['status'] !== 'ADSENSE_READY') {
                foreach ($auditResult['reasons'] as $reason) {
                    if (isset($topReasons[$reason])) {
                        $topReasons[$reason]++;
                    }
                }
            }

            $results[] = $auditResult;
        }

        $collection = collect($results);

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
            ->filter(fn ($item) =>
                $item['evals']['CONTENT_DEPTH']['score'] < 6
                || $item['evals']['USER_VALUE']['score'] < 5
            )
            ->count();

        $aiRiskCount = $collection
            ->filter(fn ($item) => $item['ai_penalty'] > 0)
            ->count();

        $duplicateCount = count($duplicateMap);

        $avgScore = $total > 0
            ? round((float) $collection->avg('score'), 1)
            : 0;

        if ($this->option('json')) {
            $this->outputJson([
                'success' => true,
                'generated_at' => now()->toIso8601String(),

                'configuration' => [
                    'limit' => $limit,
                    'min_score' => $minScore,
                    'read_only' => true,
                    'title_similarity_threshold' => $this->titleSimilarityThreshold,
                    'content_similarity_threshold' => $this->contentSimilarityThreshold,
                ],

                'statistics' => [
                    'total' => $total,
                    'average_score' => $avgScore,
                    'ready' => $ready,
                    'review' => $review,
                    'risk' => $risk,
                    'weak' => $weak,
                    'duplicate_articles' => $duplicateCount,
                    'duplicate_groups' => count($duplicateGroups),
                    'ai_risk_articles' => $aiRiskCount,
                    'topic_clusters' => count($topicClusters),
                ],

                'top_reasons' => $topReasons,
                'duplicate_groups' => $duplicateGroups,
                'topic_clusters' => $topicClusters,
                'articles' => $results,
            ]);

            return self::SUCCESS;
        }

        $this->displaySummary(
            $collection,
            $avgScore,
            $ready,
            $review,
            $risk,
            $weak,
            $aiRiskCount,
            $duplicateCount
        );

        $this->displayTopReasons($topReasons);

        if ($this->option('show-all')) {
            $this->displayDetailedList(
                $collection,
                '📋 ALL ARTICLES'
            );
        } else {
            if ($this->option('show-risk')) {
                $this->displayDetailedList(
                    $collection->where('status', 'ADSENSE_RISK'),
                    '🔴 RISK ARTICLES'
                );
            }

            if ($this->option('show-review')) {
                $this->displayDetailedList(
                    $collection->where('status', 'ADSENSE_REVIEW'),
                    '🟠 REVIEW ARTICLES'
                );
            }

            if ($this->option('show-ready')) {
                $this->displayDetailedList(
                    $collection->where('status', 'ADSENSE_READY'),
                    '🟢 READY ARTICLES'
                );
            }

            if ($this->option('show-weak')) {
                $weakArticles = $collection->filter(
                    fn ($item) =>
                        $item['evals']['CONTENT_DEPTH']['score'] < 6
                        || $item['evals']['USER_VALUE']['score'] < 5
                );

                $this->displayDetailedList(
                    $weakArticles,
                    '🔴 WEAK CONTENT ARTICLES'
                );
            }
        }

        if ($this->option('show-duplicates')) {
            $this->displayDuplicateGroups($duplicateGroups);
        }

        if ($this->option('show-ai-risk')) {
            $this->displayAiRisks($collection);
        }

        if ($this->option('show-clusters')) {
            $this->displayTopicClusters($topicClusters);
        }

        $this->newLine();

        $this->info('======================================================');
        $this->info('ADSENSE NEWS AUDIT COMPLETED');
        $this->info('======================================================');

        $this->comment(
            'This audit is an internal editorial quality tool. '
            . 'It does not guarantee Google AdSense approval.'
        );

        return self::SUCCESS;
    }

    private function auditArticle(
        News $item,
        array $duplicateMap,
        array $topicMap,
        int $minScore
    ): array {
        $contentEn = trim((string) $item->content_en);
        $contentAr = trim((string) $item->content_ar);

        $evals = [
            'SOURCE_QUALITY' => $this->evalSourceQuality($item),
            'SOURCE_TRANSPARENCY' => $this->evalSourceTransparency($item),
            'METADATA' => $this->evalMetadata($item),
            'CONTENT_DEPTH' => $this->evalContentDepth($contentAr, $contentEn),
            'STRUCTURE' => $this->evalStructure($contentAr),
            'FACTUAL_COMPLETENESS' => $this->evalFactualCompleteness(
                $contentAr,
                $contentEn
            ),
            'ORIGINALITY' => $this->evalOriginality($item),
            'USER_VALUE' => $this->evalUserValue($item),
            'ORIGINAL_VALUE' => $this->evalOriginalValue($item),
            'ANALYSIS_QUALITY' => $this->evalAnalysisQuality($item),
            'DUPLICATION' => isset($duplicateMap[$item->id])
                ? [
                    'score' => 0,
                    'max' => 10,
                    'pass' => false,
                    'reason' => 'Duplicate/similar article',
                    'details' => [
                        'group' => $duplicateMap[$item->id],
                    ],
                ]
                : [
                    'score' => 10,
                    'max' => 10,
                    'pass' => true,
                    'reason' => null,
                ],
        ];

        $aiRisk = $this->evalAiTemplateRisk($item);
        $aiPenalty = $aiRisk['penalty'];

        $baseScore = array_sum(
            array_map(
                fn ($evaluation) => $evaluation['score'],
                $evals
            )
        );

        $finalScore = max(
            0,
            min(100, $baseScore - $aiPenalty)
        );

        $isDuplicate = $evals['DUPLICATION']['score'] === 0;
        $originalityPass = $evals['ORIGINALITY']['score'] >= 10;
        $originalValuePass = $evals['ORIGINAL_VALUE']['score'] >= 10;
        $analysisPass = $evals['ANALYSIS_QUALITY']['score'] >= 7;

        if (
            $finalScore >= $minScore
            && $originalValuePass
            && $analysisPass
            && !$isDuplicate
            && $originalityPass
        ) {
            $status = 'ADSENSE_READY';
        } elseif (
            $finalScore >= 60
            && !$isDuplicate
            && $originalityPass
        ) {
            $status = 'ADSENSE_REVIEW';
        } else {
            $status = 'ADSENSE_RISK';
        }

        $reasons = [];

        if (!$originalValuePass) {
            $reasons[] = 'Missing ORIGINAL VALUE';
        }

        if (!$analysisPass) {
            $reasons[] = 'Weak ANALYSIS';
        }

        if ($evals['FACTUAL_COMPLETENESS']['score'] < 7) {
            $reasons[] = 'Low FACTUAL COMPLETENESS';
        }

        if ($evals['STRUCTURE']['score'] < 3) {
            $reasons[] = 'Weak STRUCTURE';
        }

        if ($isDuplicate) {
            $reasons[] = 'Duplicate';
        }

        if (!$originalityPass) {
            $reasons[] = 'Originality failure';
        }

        if ($evals['CONTENT_DEPTH']['score'] < 6) {
            $reasons[] = 'Short Content / Weak Depth';
        }

        if ($evals['USER_VALUE']['score'] < 5) {
            $reasons[] = 'Weak USER VALUE';
        }

        if ($evals['METADATA']['score'] < 4) {
            $reasons[] = 'Weak METADATA';
        }

        if ($aiPenalty > 10) {
            $reasons[] = 'High AI Template Risk';
        }

        $enrichment = [
            'needs_original_value' => !$originalValuePass,
            'needs_analysis' => !$analysisPass,
            'needs_factual_review' =>
                $evals['FACTUAL_COMPLETENESS']['score'] < 7,
            'needs_structure' =>
                $evals['STRUCTURE']['score'] < 3,
            'needs_user_value' =>
                $evals['USER_VALUE']['score'] < 5,
            'needs_metadata' =>
                $evals['METADATA']['score'] < 4,
            'needs_content_expansion' =>
                $evals['CONTENT_DEPTH']['score'] < 6,
            'needs_ai_cleanup' =>
                $aiPenalty > 0,
            'blocked_by_duplicate' =>
                $isDuplicate,
        ];

        return [
            'id' => $item->id,
            'title' =>
                $item->title_ar
                ?: $item->title_en
                ?: 'Untitled',
            'score' => $finalScore,
            'base_score' => $baseScore,
            'ai_penalty' => $aiPenalty,
            'status' => $status,
            'reasons' => array_values(array_unique($reasons)),
            'enrichment' => $enrichment,
            'ai_risk' => $aiRisk,
            'duplicate_group' => $duplicateMap[$item->id] ?? null,
            'topic_cluster' => $topicMap[$item->id] ?? null,
            'evals' => $evals,
        ];
    }

    private function evalSourceQuality(News $item): array
    {
        $source = trim((string) $item->source);

        return [
            'score' => $source !== '' ? 5 : 0,
            'max' => 5,
            'pass' => $source !== '',
        ];
    }

    private function evalSourceTransparency(News $item): array
    {
        $url = trim((string) $item->url);

        $valid =
            $url !== ''
            && filter_var($url, FILTER_VALIDATE_URL);

        return [
            'score' => $valid ? 5 : 0,
            'max' => 5,
            'pass' => $valid,
        ];
    }

    private function evalMetadata(News $item): array
    {
        $score = 0;

        if (trim((string) $item->title_ar) !== '') {
            $score += 2;
        }

        if (trim((string) $item->title_en) !== '') {
            $score += 1;
        }

        if (trim((string) $item->category) !== '') {
            $score += 1;
        }

        if (trim((string) $item->image_url) !== '') {
            $score += 1;
        }

        return [
            'score' => $score,
            'max' => 5,
            'pass' => $score >= 4,
        ];
    }

    private function evalContentDepth(
        string $ar,
        string $en
    ): array {
        $arLength = mb_strlen($ar);
        $enLength = mb_strlen($en);

        $effectiveLength = max(
            $arLength,
            $enLength
        );

        if ($effectiveLength >= $this->minContentLength) {
            $score = 10;
        } elseif ($effectiveLength >= 500) {
            $score = 8;
        } elseif ($effectiveLength >= 400) {
            $score = 6;
        } elseif ($effectiveLength >= 250) {
            $score = 4;
        } else {
            $score = 2;
        }

        return [
            'score' => $score,
            'max' => 10,
            'pass' => $score >= 6,
            'details' => [
                'arabic_length' => $arLength,
                'english_length' => $enLength,
                'effective_length' => $effectiveLength,
            ],
        ];
    }

    private function evalStructure(string $ar): array
    {
        $paragraphs = $this->countParagraphs($ar);

        if ($paragraphs >= 5) {
            $score = 5;
        } elseif ($paragraphs >= 4) {
            $score = 4;
        } elseif ($paragraphs >= 2) {
            $score = 3;
        } elseif ($paragraphs >= 1) {
            $score = 1;
        } else {
            $score = 0;
        }

        return [
            'score' => $score,
            'max' => 5,
            'pass' => $score >= 3,
            'details' => [
                'paragraphs' => $paragraphs,
            ],
        ];
    }

    private function evalFactualCompleteness(
        string $ar,
        string $en
    ): array {
        $arLength = mb_strlen($ar);
        $enLength = mb_strlen($en);

        if ($enLength === 0) {
            $score = $arLength >= $this->minArabicLength
                ? 8
                : ($arLength >= 300 ? 5 : 0);

            return [
                'score' => $score,
                'max' => 10,
                'pass' => $score >= 7,
                'details' => [
                    'arabic_length' => $arLength,
                    'english_length' => 0,
                    'coverage_ratio' => null,
                    'method' => 'arabic-length-only',
                ],
            ];
        }

        $ratio = $arLength / $enLength;

        if ($ratio >= 0.45) {
            $score = 10;
        } elseif ($ratio >= 0.35) {
            $score = 9;
        } elseif ($ratio >= 0.25) {
            $score = 8;
        } elseif ($ratio >= 0.18) {
            $score = 6;
        } elseif ($ratio >= 0.10) {
            $score = 4;
        } else {
            $score = 0;
        }

        if ($arLength < 250) {
            $score = min($score, 4);
        } elseif ($arLength < 350) {
            $score = min($score, 6);
        }

        return [
            'score' => $score,
            'max' => 10,
            'pass' => $score >= 7,
            'details' => [
                'arabic_length' => $arLength,
                'english_length' => $enLength,
                'coverage_ratio' => round($ratio, 3),
                'coverage_percent' => round($ratio * 100, 1),
                'method' => 'relative-length-coverage',
            ],
        ];
    }

    private function evalOriginality(News $item): array
    {
        $ar = trim((string) $item->content_ar);
        $en = trim((string) $item->content_en);

        $arLength = mb_strlen($ar);
        $enLength = mb_strlen($en);

        if ($arLength === 0) {
            return [
                'score' => 0,
                'max' => 15,
                'pass' => false,
                'details' => [
                    'arabic_length' => 0,
                    'english_length' => $enLength,
                    'ai_processed' => (bool) $item->ai_processed,
                    'method' => 'independent-arabic-content',
                ],
            ];
        }

        if ($arLength >= 1200) {
            $score = 10;
        } elseif ($arLength >= 800) {
            $score = 9;
        } elseif ($arLength >= 600) {
            $score = 8;
        } elseif ($arLength >= 400) {
            $score = 7;
        } elseif ($arLength >= 250) {
            $score = 5;
        } else {
            $score = 3;
        }

        $originalValueLength =
            mb_strlen(trim((string) $item->why_it_matters_ar))
            +
            mb_strlen(trim((string) $item->what_to_watch_ar))
            +
            mb_strlen(trim((string) $item->context_ar))
            +
            mb_strlen(trim((string) $item->analysis_ar));

        if ($originalValueLength >= 800) {
            $score += 4;
        } elseif ($originalValueLength >= 400) {
            $score += 3;
        } elseif ($originalValueLength >= 200) {
            $score += 2;
        } elseif ($originalValueLength >= 100) {
            $score += 1;
        }

        $score = min(15, $score);

        return [
            'score' => $score,
            'max' => 15,
            'pass' => $score >= 10,
            'details' => [
                'arabic_length' => $arLength,
                'english_length' => $enLength,
                'original_value_length' => $originalValueLength,
                'ai_processed' => (bool) $item->ai_processed,
                'method' => 'independent-arabic-plus-original-value',
            ],
        ];
    }

    private function evalUserValue(News $item): array
    {
        $summaryLength =
            mb_strlen(trim((string) $item->summary_ar));

        $contextLength =
            mb_strlen(trim((string) $item->context_ar));

        $score = 0;

        if ($summaryLength >= 250) {
            $score += 5;
        } elseif ($summaryLength >= 150) {
            $score += 4;
        } elseif ($summaryLength >= 100) {
            $score += 3;
        } elseif ($summaryLength >= 50) {
            $score += 1;
        }

        if ($contextLength >= 250) {
            $score += 5;
        } elseif ($contextLength >= $this->minContextLength) {
            $score += 4;
        } elseif ($contextLength >= 100) {
            $score += 2;
        } elseif ($contextLength >= 50) {
            $score += 1;
        }

        return [
            'score' => $score,
            'max' => 10,
            'pass' => $score >= 5,
            'details' => [
                'summary_length' => $summaryLength,
                'context_length' => $contextLength,
            ],
        ];
    }

    private function evalOriginalValue(News $item): array
    {
        $whyLength =
            mb_strlen(trim((string) $item->why_it_matters_ar));

        $watchLength =
            mb_strlen(trim((string) $item->what_to_watch_ar));

        $score = 0;

        if ($whyLength >= 250) {
            $score += 8;
        } elseif ($whyLength >= $this->minWhyMattersLength) {
            $score += 6;
        } elseif ($whyLength >= 100) {
            $score += 4;
        } elseif ($whyLength >= 50) {
            $score += 2;
        }

        if ($watchLength >= 250) {
            $score += 7;
        } elseif ($watchLength >= $this->minWhatToWatchLength) {
            $score += 6;
        } elseif ($watchLength >= 100) {
            $score += 4;
        } elseif ($watchLength >= 50) {
            $score += 2;
        }

        return [
            'score' => $score,
            'max' => 15,
            'pass' => $score >= 10,
            'details' => [
                'why_it_matters_length' => $whyLength,
                'what_to_watch_length' => $watchLength,
            ],
        ];
    }

    private function evalAnalysisQuality(News $item): array
    {
        $analysisLength =
            mb_strlen(trim((string) $item->analysis_ar));

        if ($analysisLength >= 600) {
            $score = 10;
        } elseif ($analysisLength >= 400) {
            $score = 9;
        } elseif ($analysisLength >= $this->minAnalysisLength) {
            $score = 8;
        } elseif ($analysisLength >= 180) {
            $score = 6;
        } elseif ($analysisLength >= 100) {
            $score = 4;
        } else {
            $score = 0;
        }

        return [
            'score' => $score,
            'max' => 10,
            'pass' => $score >= 7,
            'details' => [
                'analysis_length' => $analysisLength,
            ],
        ];
    }

    private function evalAiTemplateRisk(News $item): array
    {
        $combinedText = '';

        foreach ($this->aiFields as $field) {
            $value = trim((string) $item->{$field});

            if ($value !== '') {
                $combinedText .= ' ' . $value;
            }
        }

        $combinedText = trim($combinedText);

        if ($combinedText === '') {
            return [
                'penalty' => 0,
                'hits' => [],
                'hit_count' => 0,
                'risk' => 'NONE',
            ];
        }

        $hits = [];

        foreach ($this->aiTemplatePhrases as $phrase) {
            if (mb_stripos($combinedText, $phrase) !== false) {
                $hits[] = $phrase;
            }
        }

        $hitCount = count($hits);

        if ($hitCount >= 5) {
            $penalty = 15;
            $risk = 'HIGH';
        } elseif ($hitCount >= 3) {
            $penalty = 10;
            $risk = 'MEDIUM';
        } elseif ($hitCount >= 1) {
            $penalty = 3;
            $risk = 'LOW';
        } else {
            $penalty = 0;
            $risk = 'NONE';
        }

        return [
            'penalty' => $penalty,
            'hits' => array_values(array_unique($hits)),
            'hit_count' => $hitCount,
            'risk' => $risk,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DUPLICATE DETECTION
    |--------------------------------------------------------------------------
    |
    | A title match alone is NOT enough.
    |
    | Two articles are duplicates only when:
    |
    |   title similarity >= 85%
    |   AND
    |   content similarity >= 70%
    |
    | Arabic and English content are compared independently. The highest
    | available content similarity is used.
    |
    | Different sources do NOT automatically make articles unique.
    |
    */

    private function detectDuplicates(Collection $news): array
    {
        $rawGroups = [];
        $items = $news->values();
        $count = $items->count();

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $itemA = $items[$i];
                $itemB = $items[$j];

                $titleDetails =
                    $this->calculateTitleSimilarity($itemA, $itemB);

                if (
                    $titleDetails['similarity']
                    < $this->titleSimilarityThreshold
                ) {
                    continue;
                }

                $contentDetails =
                    $this->calculateContentSimilarity($itemA, $itemB);

                if (
                    $contentDetails['similarity']
                    >= $this->contentSimilarityThreshold
                ) {
                    $rawGroups[] = [
                        'ids' => [
                            $itemA->id,
                            $itemB->id,
                        ],
                        'similarity' => [
                            'title' => round(
                                $titleDetails['similarity'],
                                1
                            ),
                            'content' => round(
                                $contentDetails['similarity'],
                                1
                            ),
                            'content_ar' =>
                                $contentDetails['arabic'],
                            'content_en' =>
                                $contentDetails['english'],
                        ],
                    ];
                }
            }
        }

        return $this->buildDuplicateMap($rawGroups);
    }

    /*
    |--------------------------------------------------------------------------
    | TITLE SIMILARITY
    |--------------------------------------------------------------------------
    */

    private function calculateTitleSimilarity(
        News $itemA,
        News $itemB
    ): array {
        $maxSim = 0.0;
        $arabic = null;
        $english = null;

        $titleAEn = $this->normalizeText(
            $itemA->title_en
        );

        $titleBEn = $this->normalizeText(
            $itemB->title_en
        );

        if ($titleAEn !== '' && $titleBEn !== '') {
            similar_text(
                $titleAEn,
                $titleBEn,
                $percentEn
            );

            $english = round($percentEn, 1);
            $maxSim = max($maxSim, $percentEn);
        }

        $titleAAr = $this->normalizeText(
            $itemA->title_ar
        );

        $titleBAr = $this->normalizeText(
            $itemB->title_ar
        );

        if ($titleAAr !== '' && $titleBAr !== '') {
            similar_text(
                $titleAAr,
                $titleBAr,
                $percentAr
            );

            $arabic = round($percentAr, 1);
            $maxSim = max($maxSim, $percentAr);
        }

        return [
            'similarity' => $maxSim,
            'arabic' => $arabic,
            'english' => $english,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CONTENT SIMILARITY
    |--------------------------------------------------------------------------
    |
    | HTML is stripped and text is normalized before comparison.
    |
    | To prevent extremely long articles from making the command expensive,
    | only a bounded normalized portion is compared.
    |
    | The comparison is done separately for Arabic and English, and the
    | highest available language similarity is used for the decision.
    |
    */

    private function calculateContentSimilarity(
        News $itemA,
        News $itemB
    ): array {
        $maxSim = 0.0;
        $arabic = null;
        $english = null;

        $textAAr = $this->prepareSimilarityText(
            $itemA->content_ar
        );

        $textBAr = $this->prepareSimilarityText(
            $itemB->content_ar
        );

        if ($textAAr !== '' && $textBAr !== '') {
            similar_text(
                $textAAr,
                $textBAr,
                $percentAr
            );

            $arabic = round($percentAr, 1);
            $maxSim = max($maxSim, $percentAr);
        }

        $textAEn = $this->prepareSimilarityText(
            $itemA->content_en
        );

        $textBEn = $this->prepareSimilarityText(
            $itemB->content_en
        );

        if ($textAEn !== '' && $textBEn !== '') {
            similar_text(
                $textAEn,
                $textBEn,
                $percentEn
            );

            $english = round($percentEn, 1);
            $maxSim = max($maxSim, $percentEn);
        }

        return [
            'similarity' => $maxSim,
            'arabic' => $arabic,
            'english' => $english,
        ];
    }

    private function prepareSimilarityText(
        ?string $content
    ): string {
        $text = trim((string) $content);

        if ($text === '') {
            return '';
        }

        $text = strip_tags($text);
        $text = html_entity_decode(
            $text,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $text = $this->normalizeText($text);

        /*
        | Keep comparison bounded for performance.
        |
        | 4000 normalized characters gives substantially more coverage than
        | the previous 2000-character limit while keeping pairwise comparison
        | manageable for the current audit size.
        */
        return mb_substr($text, 0, 4000);
    }

    /*
    |--------------------------------------------------------------------------
    | BUILD DUPLICATE MAP
    |--------------------------------------------------------------------------
    |
    | Keeps compatibility with auditArticle():
    |
    | 210 => [210, 216]
    | 216 => [210, 216]
    |
    | Similarity information is also retained for JSON/display diagnostics.
    |--------------------------------------------------------------------------
    */

    private function buildDuplicateMap(array $rawGroups): array
    {
        if (empty($rawGroups)) {
            return [];
        }

        $groups = [];
        $similarities = [];

        foreach ($rawGroups as $match) {
            $ids = array_values(
                array_unique($match['ids'])
            );

            if (count($ids) < 2) {
                continue;
            }

            $mergedIndex = null;

            foreach ($groups as $index => $existing) {
                if (
                    count(
                        array_intersect(
                            $existing,
                            $ids
                        )
                    ) > 0
                ) {
                    $groups[$index] = array_values(
                        array_unique(
                            array_merge(
                                $existing,
                                $ids
                            )
                        )
                    );

                    $similarities[$index][] =
                        $match['similarity'];

                    $mergedIndex = $index;

                    break;
                }
            }

            if ($mergedIndex === null) {
                $groups[] = $ids;
                $similarities[] = [$match['similarity']];
            }
        }

        $duplicateMap = [];

        foreach ($groups as $index => $group) {
            sort($group);

            $groupSimilarity =
                $this->summarizeDuplicateSimilarity(
                    $similarities[$index] ?? []
                );

            foreach ($group as $articleId) {
                $duplicateMap[$articleId] = [
                    'ids' => $group,
                    'matches' => $groupSimilarity,
                ];
            }
        }

        return $duplicateMap;
    }

    private function summarizeDuplicateSimilarity(
        array $matches
    ): array {
        $summary = [];

        foreach ($matches as $match) {
            $summary[] = [
                'title' =>
                    $match['title'] ?? null,
                'content' =>
                    $match['content'] ?? null,
                'content_ar' =>
                    $match['content_ar'] ?? null,
                'content_en' =>
                    $match['content_en'] ?? null,
            ];
        }

        return $summary;
    }

    private function extractDuplicateGroups(
        array $duplicateMap
    ): array {
        $groups = [];

        foreach ($duplicateMap as $data) {
            /*
             * New map format.
             */
            if (
                is_array($data)
                && isset($data['ids'])
                && is_array($data['ids'])
            ) {
                $group = $data['ids'];
            } else {
                /*
                 * Backward-compatible fallback.
                 */
                $group = (array) $data;
            }

            sort($group);

            $key = implode(',', $group);

            $groups[$key] = $group;
        }

        return array_values($groups);
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE TEXT
    |--------------------------------------------------------------------------
    */

    private function normalizeText(
        ?string $text
    ): string {
        if (!$text) {
            return '';
        }

        $text = mb_strtolower(
            trim($text)
        );

        $text = str_replace(
            [
                'أ',
                'إ',
                'آ',
                'ٱ',
                'ى',
                'ة',
            ],
            [
                'ا',
                'ا',
                'ا',
                'ا',
                'ي',
                'ه',
            ],
            $text
        );

        $text = preg_replace(
            '/[\x{064B}-\x{065F}\x{0670}]/u',
            '',
            $text
        );

        $text = preg_replace(
            '/[^\p{L}\p{N}\s]/u',
            ' ',
            $text
        );

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $text
            )
        );
    }

    private function countParagraphs(
        string $content
    ): int {
        if (trim($content) === '') {
            return 0;
        }

        $paragraphs = preg_split(
            '/\R\s*\R/u',
            trim($content)
        );

        $paragraphs = array_filter(
            $paragraphs,
            fn ($paragraph) =>
                trim($paragraph) !== ''
        );

        if (count($paragraphs) >= 2) {
            return count($paragraphs);
        }

        preg_match_all(
            '/<p\b[^>]*>(.*?)<\/p>/isu',
            $content,
            $matches
        );

        if (!empty($matches[1])) {
            return count(
                array_filter(
                    $matches[1],
                    fn ($paragraph) =>
                        trim(strip_tags($paragraph)) !== ''
                )
            );
        }

        $lines = preg_split(
            '/\R/u',
            trim($content)
        );

        return count(
            array_filter(
                $lines,
                fn ($line) =>
                    trim($line) !== ''
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TOPIC CLUSTERING
    |--------------------------------------------------------------------------
    */

    private function detectTopicClusters(
        Collection $news
    ): array {
        $articles = [];

        foreach ($news as $item) {
            $title =
                $item->title_en
                ?: $item->title_ar;

            $normalized =
                $this->normalizeText($title);

            if ($normalized === '') {
                continue;
            }

            $tokens = array_filter(
                preg_split(
                    '/\s+/u',
                    $normalized
                )
            );

            $stopWords = [
                'the',
                'and',
                'for',
                'with',
                'from',
                'this',
                'that',
                'after',
                'before',
                'into',
                'over',
                'about',
                'بعد',
                'من',
                'في',
                'على',
                'الى',
                'إلى',
                'عن',
                'مع',
                'هل',
                'كيف',
                'ما',
                'هذا',
                'هذه',
            ];

            $tokens = array_values(
                array_filter(
                    $tokens,
                    fn ($token) =>
                        mb_strlen($token) >= 4
                        && !in_array(
                            $token,
                            $stopWords,
                            true
                        )
                )
            );

            if (count($tokens) < 2) {
                continue;
            }

            $articles[$item->id] = array_values(
                array_unique($tokens)
            );
        }

        $rawClusters = [];

        $ids = array_keys($articles);
        $count = count($ids);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $common = count(
                    array_intersect(
                        $articles[$ids[$i]],
                        $articles[$ids[$j]]
                    )
                );

                if ($common >= 3) {
                    $rawClusters[] = [
                        $ids[$i],
                        $ids[$j],
                    ];
                }
            }
        }

        return $this->mergeGroups($rawClusters);
    }

    private function mergeGroups(
        array $rawGroups
    ): array {
        $merged = [];

        foreach ($rawGroups as $group) {
            $group = array_values(
                array_unique($group)
            );

            if (count($group) < 2) {
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

        return $merged;
    }

    private function buildTopicMap(
        array $clusters
    ): array {
        $map = [];

        foreach ($clusters as $clusterIndex => $cluster) {
            $clusterNumber = $clusterIndex + 1;

            foreach ($cluster as $articleId) {
                $map[$articleId] = $clusterNumber;
            }
        }

        return $map;
    }

    /*
    |--------------------------------------------------------------------------
    | DISPLAY
    |--------------------------------------------------------------------------
    */

    private function displaySummary(
        Collection $collection,
        float $avgScore,
        int $ready,
        int $review,
        int $risk,
        int $weak,
        int $aiRiskCount,
        int $duplicateCount
    ): void {
        $total = $collection->count();

        $readyPercent =
            $total > 0
                ? round(($ready / $total) * 100)
                : 0;

        $reviewPercent =
            $total > 0
                ? round(($review / $total) * 100)
                : 0;

        $riskPercent =
            $total > 0
                ? round(($risk / $total) * 100)
                : 0;

        $this->info(
            '======================================================'
        );

        $this->info(
            'CRYPTOHUB ADSENSE NEWS AUDIT'
        );

        $this->info(
            '======================================================'
        );

        $this->line(
            "Total articles : {$total}"
        );

        $this->line(
            "Average Score  : {$avgScore}/100"
        );

        $this->newLine();

        $this->line(
            "🟢 READY   : {$ready} ({$readyPercent}%)"
        );

        $this->line(
            "🟠 REVIEW  : {$review} ({$reviewPercent}%)"
        );

        $this->line(
            "🔴 RISK    : {$risk} ({$riskPercent}%)"
        );

        $this->line(
            "🔴 WEAK    : {$weak}"
        );

        $this->line(
            "🔁 DUPLICATE ARTICLES : {$duplicateCount}"
        );

        $this->line(
            "🤖 AI TEMPLATE RISKS  : {$aiRiskCount}"
        );

        $this->newLine();
    }

    private function displayTopReasons(
        array $topReasons
    ): void {
        $this->info(
            '======================================================'
        );

        $this->info(
            'TOP REASONS'
        );

        $this->info(
            '======================================================'
        );

        arsort($topReasons);

        foreach ($topReasons as $reason => $count) {
            if ($count <= 0) {
                continue;
            }

            $this->line(
                str_pad(
                    $reason,
                    32
                ) . ": {$count}"
            );
        }

        $this->newLine();
    }

    private function displayDetailedList(
        Collection $items,
        string $title
    ): void {
        if ($items->isEmpty()) {
            return;
        }

        $this->info(
            '------------------------------------------------------'
        );

        $this->info(
            "{$title} ({$items->count()})"
        );

        $this->info(
            '------------------------------------------------------'
        );

        foreach ($items as $item) {
            $this->line(
                "ID: {$item['id']} | "
                . "Score: {$item['score']} | "
                . "{$item['title']}"
            );

            $this->line(
                "   Status: {$item['status']}"
            );

            if ($item['base_score'] !== $item['score']) {
                $this->line(
                    "   Base Score: {$item['base_score']} "
                    . "| AI Penalty: -{$item['ai_penalty']}"
                );
            }

            if (!empty($item['reasons'])) {
                $this->warn(
                    "   Issues: "
                    . implode(
                        ' | ',
                        $item['reasons']
                    )
                );
            }

            foreach ($item['evals'] as $name => $evaluation) {
                $score = $evaluation['score'];
                $max = $evaluation['max'] ?? null;

                $scoreText =
                    $max !== null
                        ? "{$score}/{$max}"
                        : (string) $score;

                $this->line(
                    "   {$name}: {$scoreText}"
                );
            }

            $this->newLine();
        }
    }

    private function displayDuplicateGroups(
        array $duplicateGroups
    ): void {
        $this->info(
            '======================================================'
        );

        $this->info(
            '🔁 DUPLICATE / SIMILAR GROUPS'
        );

        $this->info(
            '======================================================'
        );

        if (empty($duplicateGroups)) {
            $this->line(
                'No duplicate/similar groups detected.'
            );

            $this->newLine();

            return;
        }

        foreach ($duplicateGroups as $index => $group) {
            $this->info(
                'Group #' . ($index + 1)
            );

            $items = News::query()
                ->whereIn('id', $group)
                ->orderBy('id')
                ->get();

            foreach ($items as $item) {
                $title =
                    $item->title_ar
                    ?: $item->title_en
                    ?: 'Untitled';

                $this->line(
                    "  ID {$item->id} | {$title}"
                );
            }

            /*
             * Show pairwise similarity diagnostics when available.
             */
            foreach ($group as $articleId) {
                /*
                 * Find the map entry indirectly through the group is not
                 * available here, so the main article listing remains concise.
                 * Detailed similarity is available through --json.
                 */
                break;
            }

            $this->newLine();
        }
    }

    private function displayAiRisks(
        Collection $collection
    ): void {
        $items = $collection
            ->filter(
                fn ($item) =>
                    $item['ai_penalty'] > 0
            )
            ->sortByDesc('ai_penalty');

        $this->info(
            '======================================================'
        );

        $this->info(
            '🤖 AI / TEMPLATE REPETITION RISKS'
        );

        $this->info(
            '======================================================'
        );

        if ($items->isEmpty()) {
            $this->line(
                'No AI/template repetition risks detected.'
            );

            $this->newLine();

            return;
        }

        foreach ($items as $item) {
            $this->line(
                "ID: {$item['id']} | "
                . "Penalty: -{$item['ai_penalty']} | "
                . "Risk: {$item['ai_risk']['risk']}"
            );

            $this->line(
                "   {$item['title']}"
            );

            if (!empty($item['ai_risk']['hits'])) {
                $this->warn(
                    '   Phrases: '
                    . implode(
                        ' | ',
                        $item['ai_risk']['hits']
                    )
                );
            }

            $this->newLine();
        }
    }

    private function displayTopicClusters(
        array $clusters
    ): void {
        $this->info(
            '======================================================'
        );

        $this->info(
            '🧩 TOPIC / EVENT CLUSTERS'
        );

        $this->info(
            '======================================================'
        );

        if (empty($clusters)) {
            $this->line(
                'No topic/event clusters detected.'
            );

            $this->newLine();

            return;
        }

        foreach ($clusters as $index => $cluster) {
            $this->info(
                'Cluster #' . ($index + 1)
            );

            $items = News::query()
                ->whereIn('id', $cluster)
                ->orderBy('id')
                ->get();

            foreach ($items as $item) {
                $title =
                    $item->title_ar
                    ?: $item->title_en
                    ?: 'Untitled';

                $this->line(
                    "  ID {$item->id} | {$title}"
                );
            }

            $this->newLine();
        }
    }

    private function outputJson(
        array $payload
    ): void {
        $this->output->writeln(
            json_encode(
                $payload,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            )
        );
    }

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
            'Scores are internal editorial quality indicators.'
        );

        $this->comment(
            'They are NOT official Google AdSense thresholds.'
        );

        $this->comment(
            'AI/template warnings are editorial signals only.'
        );

        $this->newLine();
    }
}