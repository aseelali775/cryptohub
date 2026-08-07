<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProcessNewsWithAI extends Command
{
    protected $signature = 'news:process-ai';

    protected $description = 'Analyze crypto news to generate Arabic rewrites and market analysis using Gemini AI.';


    public function handle()
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {

            $this->error('GEMINI_API_KEY is missing.');

            Log::error('Gemini API Key missing.');

            return;
        }


        $this->info('Starting CryptoHub AI Arabic Journalist...');


        $newsList = News::where('ai_processed', false)
            ->latest()
            ->limit(3)
            ->get();



        if ($newsList->isEmpty()) {

            $this->info('No new articles.');

            return;
        }



        foreach ($newsList as $news) {


            $this->info("Processing: {$news->title_en}");



            $content = mb_substr($news->content_en, 0, 8000);



            $result = $this->analyzeWithGemini(
                $news->title_en,
                $content,
                $apiKey
            );



            if ($result && is_array($result)) {


                $slug = Str::slug($news->title_en)
                    . '-' . $news->id;



                $keywords = is_array($result['keywords'] ?? null)
                    ? array_slice($result['keywords'],0,5)
                    : [];



                $news->update([


                    'slug' => $slug,


                    'title_ar' =>
                        $result['title_ar'] ?? null,


                    'content_ar' =>
                        $result['content_ar'] ?? null,


                    'summary_ar' =>
                        $result['meta_description_ar']
                        ?? $result['summary_ar']
                        ?? null,


                    'why_it_matters_ar' =>
                        $result['why_it_matters_ar'] ?? null,


                    'keywords' =>
                        $keywords,


                    'sentiment' =>
                        $result['sentiment'] ?? 'Neutral',


                    'category' =>
                        $result['category'] ?? 'Market',


                    'impact_score' =>
                        (int)($result['impact_score'] ?? 5),


                    'ai_processed' => true,


                ]);



                $this->info(
                    "✅ Saved AI analysis ID {$news->id}"
                );

            } else {


                $this->error(
                    "AI failed for ID {$news->id}"
                );

            }



            sleep(5);

        }



        Cache::forget('ai_market_dashboard_stats');
        Cache::forget('ai_market_impact_news');


        $this->info(
            '🧹 AI Market cache cleared.'
        );


        $this->info(
            '🚀 AI Journalism Cycle Completed.'
        );

    }





    private function analyzeWithGemini($title,$content,$apiKey)
    {


        $url =
        "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";



        $prompt = <<<PROMPT

You are a senior cryptocurrency financial journalist and SEO content specialist for an Arabic crypto news platform.

Analyze the cryptocurrency news article and rewrite it into professional Arabic journalistic content.


RULES:


1. Writing Style:

- Do NOT translate literally.
- Rewrite naturally in professional Arabic.
- Use crypto financial terminology:

سيولة، زخم، تصحيح سعري، حيتان، تدفقات، قيمة سوقية، معنويات السوق.



2. Accuracy:

Preserve:

- Names
- Numbers
- Dates
- Prices
- Percentages
- Quotes
- Cryptocurrency terms


Never invent facts.



3. Article:

Generate:

- SEO Arabic title
- Complete Arabic article
- Short Arabic summary
- Why this matters for investors


Separate paragraphs using:

\n\n



4. SEO Keywords:

Generate exactly 3-5 English keywords.

Use:

Title Case words.

UPPERCASE symbols.


If specific coin exists:

Include:

Bitcoin + BTC

Ethereum + ETH

Solana + SOL



5. Meta Description:

Arabic SEO description.

Maximum 160 characters.



6. Market Analysis:

Analyze crypto market impact only.


sentiment:

Only:

Bullish

Bearish

Neutral



impact_score:

Integer 1-10.



7. Category:

Choose one:

Bitcoin
Ethereum
Regulation
DeFi
NFT
Mining
Market
Security
Blockchain



8. JSON ONLY:

Return ONLY valid JSON.

No markdown.

No explanations.



JSON STRUCTURE:


{
"title_ar":"",
"content_ar":"",
"summary_ar":"",
"meta_description_ar":"",
"why_it_matters_ar":"",
"sentiment":"Neutral",
"category":"Market",
"impact_score":5,
"keywords":["Bitcoin","BTC","Crypto"]
}


ARTICLE TITLE:

$title


ARTICLE CONTENT:

$content


PROMPT;




        $payload = [


            "contents" => [

                [

                    "parts" => [

                        [

                            "text" => $prompt

                        ]

                    ]

                ]

            ],


            "generationConfig" => [

                "response_mime_type" => "application/json",

                "temperature" => 0.3

            ]

        ];





        try {


            $response = Http::timeout(60)
                ->post($url,$payload);



            if(!$response->successful()){


                Log::error(
                    'Gemini API Error',
                    [
                        'status'=>$response->status(),
                        'body'=>$response->body()
                    ]
                );


                return null;

            }




            $text =
            $response->json()['candidates'][0]['content']['parts'][0]['text']
            ?? '';




            $text = trim(
                preg_replace(
                    '/```json|```/',
                    '',
                    $text
                )
            );




            $start = strpos($text,'{');

            $end = strrpos($text,'}');



            if($start !== false && $end !== false){

                $text = substr(
                    $text,
                    $start,
                    $end-$start+1
                );

            }




            $data = json_decode($text,true);



            if(json_last_error() === JSON_ERROR_NONE){

                return $data;

            }




            Log::error(
                'Gemini JSON Error: '
                .json_last_error_msg()
            );


            return null;




        } catch(\Exception $e){


            Log::error(
                'Gemini Connection Error: '
                .$e->getMessage()
            );


            return null;

        }


    }

}