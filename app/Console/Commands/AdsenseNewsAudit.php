<?php

namespace App\Console\Commands;

use App\Models\News;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

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

    // Internal thresholds
    private int $minContentLength = 700;
    private int $minArabicLength = 500;
    private int $minAnalysisLength = 250;
    private int $minContextLength = 180;
    private int $minWhyMattersLength = 150;
    private int $minWhatToWatchLength = 150;

    private float $highSimilarity = 85.0;
    private float $topicSimilarity = 58.0;

    private array $aiFields = [
        'summary_ar', 'why_it_matters_ar', 'analysis_ar', 
        'context_ar', 'what_to_watch_ar', 'limitations_ar'
    ];

    public function handle(): int
    {
        $this->printHeader();

        $limit = (int) $this->option('limit');
        $query = News::query()->orderBy('id');
        if ($limit > 0) $query->limit($limit);
        
        $news = $query->get();

        if ($news->isEmpty()) {
            $this->warn('No news articles found.');
            return self::SUCCESS;
        }

        $this->info("Auditing {$news->count()} news articles based on CryptoHub Editorial Standards...");
        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | PRE-PROCESSING: Maps
        |--------------------------------------------------------------------------
        */
        $duplicateMap = $this->detectDuplicates($news);
        $topicClusters = $this->detectTopicClusters($news);
        $topicMap = $this->buildTopicMap($topicClusters);

        /*
        |--------------------------------------------------------------------------
        | AUDIT EXECUTION
        |--------------------------------------------------------------------------
        */
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
        ];

        foreach ($news as $item) {
            $auditResult = $this->auditArticle($item, $duplicateMap, $topicMap);
            
            // Count Top Reasons for non-READY articles
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

        /*
        |--------------------------------------------------------------------------
        | STATISTICS & DISPLAY
        |--------------------------------------------------------------------------
        */
        $ready = $collection->where('status', 'ADSENSE_READY')->count();
        $review = $collection->where('status', 'ADSENSE_REVIEW')->count();
        $risk = $collection->where('status', 'ADSENSE_RISK')->count();
        $avgScore = round((float) $collection->avg('score'), 1);

        $this->info("======================================================");
        $this->info("AUDIT SUMMARY");
        $this->info("======================================================");
        $this->line("Total articles : {$collection->count()}");
        $this->line("Average Score  : {$avgScore}/100");
        $this->newLine();
        $this->line("🟢 READY   : {$ready} (" . round(($ready/$collection->count())*100) . "%)");
        $this->line("🟠 REVIEW  : {$review} (" . round(($review/$collection->count())*100) . "%)");
        $this->line("🔴 RISK    : {$risk} (" . round(($risk/$collection->count())*100) . "%)");
        
        $this->newLine();
        $this->info("======================================================");
        $this->info("TOP REASONS (For REVIEW & RISK articles)");
        $this->info("======================================================");
        
        arsort($topReasons);
        foreach ($topReasons as $reason => $count) {
            if ($count > 0) {
                $this->line(str_pad($reason, 28) . ": {$count}");
            }
        }
        $this->newLine();

        if ($this->option('show-all') || $this->option('show-risk')) {
            $this->displayDetailedList($collection->where('status', 'ADSENSE_RISK'), '🔴 RISK ARTICLES');
        }
        
        if ($this->option('show-all') || $this->option('show-review')) {
            $this->displayDetailedList($collection->where('status', 'ADSENSE_REVIEW'), '🟠 REVIEW ARTICLES');
        }
        
        if ($this->option('show-all') || $this->option('show-ready')) {
            $this->displayDetailedList($collection->where('status', 'ADSENSE_READY'), '🟢 READY ARTICLES');
        }

        return self::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | CORE AUDIT ENGINE
    |--------------------------------------------------------------------------
    */
    private function auditArticle(News $item, array $duplicateMap, array $topicMap): array
    {
        $contentEn = trim((string) $item->content_en);
        $contentAr = trim((string) $item->content_ar);

        // 1. Evaluations
        $evals = [
            'SOURCE_QUALITY'       => $this->evalSourceQuality($item),       // Max 5
            'SOURCE_TRANSPARENCY'  => $this->evalSourceTransparency($item),  // Max 5
            'METADATA'             => $this->evalMetadata($item),            // Max 5
            'CONTENT_DEPTH'        => $this->evalContentDepth($contentAr, $contentEn), // Max 10
            'STRUCTURE'            => $this->evalStructure($contentAr),      // Max 5
            'FACTUAL_COMPLETENESS' => $this->evalFactualCompleteness($contentAr, $contentEn), // Max 10
            'ORIGINALITY'          => $this->evalOriginality($item),         // Max 15
            'USER_VALUE'           => $this->evalUserValue($item),           // Max 10
            'ORIGINAL_VALUE'       => $this->evalOriginalValue($item),       // Max 15
            'ANALYSIS_QUALITY'     => $this->evalAnalysisQuality($item),     // Max 10
            'DUPLICATION'          => isset($duplicateMap[$item->id]) ? 0 : 10, // Max 10
        ];

        $aiPenalty = $this->evalAiTemplateRisk($item); // Subtracts points

        // 2. Score Calculation
        $baseScore = array_sum(array_column($evals, 'score'));
        $finalScore = max(0, min(100, $baseScore - $aiPenalty));

        // 3. Strict Deterministic Status Logic
        $isDuplicate       = ($evals['DUPLICATION'] === 0);
        $originalityPass   = ($evals['ORIGINALITY']['score'] >= 10);
        $originalValuePass = ($evals['ORIGINAL_VALUE']['score'] >= 10);
        $analysisPass      = ($evals['ANALYSIS_QUALITY']['score'] >= 7);

        if ($finalScore >= 80 && $originalValuePass && $analysisPass && !$isDuplicate && $originalityPass) {
            $status = 'ADSENSE_READY';
        } elseif ($finalScore >= 60 && !$isDuplicate && $originalityPass) {
            $status = 'ADSENSE_REVIEW';
        } else {
            $status = 'ADSENSE_RISK';
        }

        // 4. Identify Reasons
        $reasons = [];
        if (!$originalValuePass) $reasons[] = 'Missing ORIGINAL VALUE';
        if (!$analysisPass) $reasons[] = 'Weak ANALYSIS';
        if ($evals['FACTUAL_COMPLETENESS']['score'] < 7) $reasons[] = 'Low FACTUAL COMPLETENESS';
        if ($evals['STRUCTURE']['score'] < 3) $reasons[] = 'Weak STRUCTURE';
        if ($isDuplicate) $reasons[] = 'Duplicate';
        if (!$originalityPass) $reasons[] = 'Originality failure';
        if ($evals['CONTENT_DEPTH']['score'] < 6) $reasons[] = 'Short Content / Weak Depth';
        if ($aiPenalty > 10) $reasons[] = 'High AI Template Risk';

        return [
            'id' => $item->id,
            'title' => $item->title_ar ?: $item->title_en,
            'score' => $finalScore,
            'status' => $status,
            'reasons' => $reasons,
            'evals' => $evals,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | EVALUATION METHODS
    |--------------------------------------------------------------------------
    */
    private function evalSourceQuality($item) {
        $score = !empty(trim((string)$item->source)) ? 5 : 0;
        return ['score' => $score];
    }

    private function evalSourceTransparency($item) {
        $url = trim((string)$item->url);
        $score = (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) ? 5 : 0;
        return ['score' => $score];
    }

    private function evalMetadata($item) {
        $score = 0;
        if (!empty(trim((string)$item->title_ar))) $score += 2;
        if (!empty(trim((string)$item->title_en))) $score += 1;
        if (!empty(trim((string)$item->category))) $score += 1;
        if (!empty(trim((string)$item->image_url))) $score += 1;
        return ['score' => $score];
    }

    private function evalContentDepth($ar, $en) {
        $len = max(mb_strlen($ar), mb_strlen($en));
        if ($len >= $this->minContentLength) return ['score' => 10];
        if ($len >= 400) return ['score' => 6];
        return ['score' => 2];
    }

    private function evalStructure($ar) {
        $paragraphs = count(array_filter(preg_split('/\R\s*\R/u', trim($ar))));
        if ($paragraphs >= 4) return ['score' => 5];
        if ($paragraphs >= 2) return ['score' => 3];
        return ['score' => 0];
    }

    private function evalFactualCompleteness($ar, $en) {
        $lenAr = mb_strlen($ar);
        if ($lenAr >= $this->minArabicLength) return ['score' => 10];
        if ($lenAr >= 300) return ['score' => 6];
        return ['score' => 0];
    }

    private function evalOriginality($item) {
        // Originality is high if there is independent Arabic text that relies on AI enrichment
        $lenAr = mb_strlen(trim((string)$item->content_ar));
        $score = 0;
        if ($lenAr >= 400) $score += 10;
        if ($item->ai_processed) $score += 5; // AI transformed it rather than literal scrape
        return ['score' => $score];
    }

    private function evalUserValue($item) {
        $score = 0;
        if (mb_strlen(trim((string)$item->summary_ar)) >= 100) $score += 5;
        if (mb_strlen(trim((string)$item->context_ar)) >= 100) $score += 5;
        return ['score' => $score];
    }

    private function evalOriginalValue($item) {
        $score = 0;
        if (mb_strlen(trim((string)$item->why_it_matters_ar)) >= 100) $score += 8;
        if (mb_strlen(trim((string)$item->what_to_watch_ar)) >= 100) $score += 7;
        return ['score' => $score];
    }

    private function evalAnalysisQuality($item) {
        $len = mb_strlen(trim((string)$item->analysis_ar));
        if ($len >= $this->minAnalysisLength) return ['score' => 10];
        if ($len >= 100) return ['score' => 6];
        return ['score' => 0];
    }

    private function evalAiTemplateRisk($item) {
        $templates = [
            'في هذا التقرير', 'في هذا المقال', 'من المهم ملاحظة', 'يجدر بالذكر', 
            'تجدر الإشارة', 'بشكل عام', 'في نهاية المطاف', 'ينبغي للمستثمرين', 'بشكل ملحوظ'
        ];
        
        $hits = 0;
        $combinedText = '';
        foreach ($this->aiFields as $field) {
            $combinedText .= ' ' . trim((string)$item->{$field});
        }
        
        foreach ($templates as $template) {
            if (mb_strpos($combinedText, $template) !== false) $hits++;
        }
        
        if ($hits >= 4) return 15; // Heavy penalty
        if ($hits >= 2) return 5;  // Slight penalty
        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | PERFECTED DUPLICATE DETECTION (WITH ID MAP BUGFIX)
    |--------------------------------------------------------------------------
    */
    private function detectDuplicates($news): array {
        $titles = [];
        foreach ($news as $item) {
            $title = $this->normalizeText($item->title_en ?: $item->title_ar);
            if ($title !== '') $titles[$item->id] = $title;
        }

        $rawGroups = [];
        $ids = array_keys($titles);
        $count = count($ids);

        for ($i = 0; $i < $count; $i++) {
            $idA = $ids[$i];
            $titleA = $titles[$idA];
            if (mb_strlen($titleA) < 20) continue;

            for ($j = $i + 1; $j < $count; $j++) {
                $idB = $ids[$j];
                $titleB = $titles[$idB];
                if (mb_strlen($titleB) < 20) continue;

                similar_text($titleA, $titleB, $percent);
                if ($percent >= $this->highSimilarity) {
                    $rawGroups[] = [$idA, $idB];
                }
            }
        }

        // Merge groups safely
        $groups = $this->mergeGroups($rawGroups);
        
        // 🟢 FIX: Create a mapped array where Article ID is the Key!
        $duplicateMap = [];
        foreach ($groups as $group) {
            foreach ($group as $articleId) {
                $duplicateMap[$articleId] = $group;
            }
        }

        return $duplicateMap;
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS & DISPLAY
    |--------------------------------------------------------------------------
    */
    private function normalizeText(?string $text): string {
        if (!$text) return '';
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    private function mergeGroups(array $rawGroups): array {
        $merged = [];
        foreach ($rawGroups as $group) {
            $group = array_values(array_unique($group));
            if (count($group) < 2) continue;
            
            $mergedIntoExisting = false;
            foreach ($merged as &$existing) {
                if (count(array_intersect($existing, $group)) > 0) {
                    $existing = array_values(array_unique(array_merge($existing, $group)));
                    $mergedIntoExisting = true;
                    break;
                }
            }
            if (!$mergedIntoExisting) $merged[] = $group;
        }
        return $merged;
    }

    // Dummy placeholders for topic clustering to prevent breaking (kept simple for this phase)
    private function detectTopicClusters($news) { return []; }
    private function buildTopicMap($clusters) { return []; }

    private function printHeader(): void {
        $this->newLine();
        $this->info('======================================================');
        $this->info('        CRYPTOHUB EDITORIAL QUALITY SYSTEM            ');
        $this->info('======================================================');
        $this->comment('READ ONLY - No database records will be modified.');
        $this->comment('This tool diagnoses missing Original Value before AI enrichment.');
        $this->newLine();
    }

    private function displayDetailedList($items, $title) {
        if ($items->isEmpty()) return;
        $this->info("------------------------------------------------------");
        $this->info($title . " (" . $items->count() . ")");
        $this->info("------------------------------------------------------");
        foreach ($items as $item) {
            $reasonsStr = !empty($item['reasons']) ? implode(' | ', $item['reasons']) : 'None';
            $this->line("ID: {$item['id']} | Score: {$item['score']} | {$item['title']}");
            if ($item['status'] !== 'ADSENSE_READY') {
                $this->warn("   Issues: {$reasonsStr}");
            }
            $this->newLine();
        }
    }
}