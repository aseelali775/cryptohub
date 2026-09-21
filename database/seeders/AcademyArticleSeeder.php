<?php

namespace Database\Seeders;

use App\Models\AcademyArticle;
use App\Models\AcademyTopic;
use Illuminate\Database\Seeder;

class AcademyArticleSeeder extends Seeder
{
    public function run(): void
    {
        $bitcoin = AcademyTopic::where('slug', 'bitcoin')->firstOrFail();

        $articles = [
            [
                'title' => 'What is Bitcoin?',
                'slug' => 'what-is-bitcoin',
                'excerpt' => 'An introduction to Bitcoin, its purpose, decentralized design, and role in the digital asset ecosystem.',
                'content' => '<p>This is a placeholder for the full educational article about Bitcoin.</p>',
                'image' => null,
                'seo_title' => 'What is Bitcoin? | AQL Crypto Academy',
                'meta_description' => 'Learn what Bitcoin is, why it was created, and how it works as a decentralized digital asset.',
                'status' => 'published',
                'sort_order' => 1,
                'published_at' => now(),
            ],

            [
                'title' => 'Bitcoin History',
                'slug' => 'history-of-bitcoin',
                'excerpt' => 'Explore the origins of Bitcoin, the publication of the Bitcoin whitepaper, and its early development.',
                'content' => '<p>This is a placeholder for the full educational article about Bitcoin history.</p>',
                'image' => null,
                'seo_title' => 'Bitcoin History | AQL Crypto Academy',
                'meta_description' => 'Explore the history of Bitcoin, from its whitepaper and early development to its growth as a global digital asset.',
                'status' => 'published',
                'sort_order' => 2,
                'published_at' => now(),
            ],

            [
                'title' => 'How Bitcoin Works',
                'slug' => 'how-bitcoin-works',
                'excerpt' => 'Understand how Bitcoin transactions, blocks, nodes, and the Bitcoin network work together.',
                'content' => '<p>This is a placeholder for the full educational article explaining how Bitcoin works.</p>',
                'image' => null,
                'seo_title' => 'How Bitcoin Works | AQL Crypto Academy',
                'meta_description' => 'Learn how Bitcoin transactions, blocks, nodes, and the decentralized Bitcoin network work together.',
                'status' => 'published',
                'sort_order' => 3,
                'published_at' => now(),
            ],

            [
                'title' => 'Bitcoin Mining Explained',
                'slug' => 'bitcoin-mining',
                'excerpt' => 'Learn what Bitcoin mining is, how proof of work operates, and how new blocks are added to the blockchain.',
                'content' => '<p>This is a placeholder for the full educational article about Bitcoin mining.</p>',
                'image' => null,
                'seo_title' => 'Bitcoin Mining Explained | AQL Crypto Academy',
                'meta_description' => 'Learn how Bitcoin mining and proof of work operate and how miners help secure the Bitcoin network.',
                'status' => 'published',
                'sort_order' => 4,
                'published_at' => now(),
            ],

            [
                'title' => 'Bitcoin Wallets',
                'slug' => 'bitcoin-wallets',
                'excerpt' => 'Learn how Bitcoin wallets work, the difference between custodial and non-custodial wallets, and how private keys are used.',
                'content' => '<p>This is a placeholder for the full educational article about Bitcoin wallets.</p>',
                'image' => null,
                'seo_title' => 'Bitcoin Wallets | AQL Crypto Academy',
                'meta_description' => 'Learn how Bitcoin wallets work, including private keys, addresses, custodial wallets, and non-custodial wallets.',
                'status' => 'published',
                'sort_order' => 5,
                'published_at' => now(),
            ],

            [
                'title' => 'Bitcoin vs Ethereum',
                'slug' => 'bitcoin-vs-ethereum',
                'excerpt' => 'Understand the main differences between Bitcoin and Ethereum, including their purposes, networks, and use cases.',
                'content' => '<p>This is a placeholder for the full educational comparison between Bitcoin and Ethereum.</p>',
                'image' => null,
                'seo_title' => 'Bitcoin vs Ethereum | AQL Crypto Academy',
                'meta_description' => 'Compare Bitcoin and Ethereum and understand the key differences between their networks, purposes, and use cases.',
                'status' => 'published',
                'sort_order' => 6,
                'published_at' => now(),
            ],

            [
                'title' => 'Bitcoin Halving',
                'slug' => 'bitcoin-halving',
                'excerpt' => 'Learn what Bitcoin halving is, why it occurs, and how it changes the rate at which new bitcoins are created.',
                'content' => '<p>This is a placeholder for the full educational article about Bitcoin halving.</p>',
                'image' => null,
                'seo_title' => 'Bitcoin Halving | AQL Crypto Academy',
                'meta_description' => 'Learn what Bitcoin halving is, why it occurs, and how it affects the issuance of new bitcoins.',
                'status' => 'published',
                'sort_order' => 7,
                'published_at' => now(),
            ],

            [
                'title' => 'Bitcoin Advantages and Risks',
                'slug' => 'bitcoin-advantages-and-risks',
                'excerpt' => 'Explore the potential benefits, limitations, risks, and important considerations associated with Bitcoin.',
                'content' => '<p>This is a placeholder for the full educational article about Bitcoin advantages and risks.</p>',
                'image' => null,
                'seo_title' => 'Bitcoin Advantages and Risks | AQL Crypto Academy',
                'meta_description' => 'Explore the potential advantages, limitations, risks, and important considerations related to Bitcoin.',
                'status' => 'published',
                'sort_order' => 8,
                'published_at' => now(),
            ],
        ];

        foreach ($articles as $article) {
            AcademyArticle::updateOrCreate(
                [
                    'topic_id' => $bitcoin->id,
                    'slug' => $article['slug'],
                ],
                $article
            );
        }
    }
}