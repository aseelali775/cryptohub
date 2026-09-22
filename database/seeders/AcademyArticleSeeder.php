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
                'title_ar' => 'ما هو البيتكوين؟',
                'title_en' => 'What is Bitcoin?',

                'slug' => 'what-is-bitcoin',

                'excerpt' => 'An introduction to Bitcoin, its purpose, decentralized design, and role in the digital asset ecosystem.',
                'excerpt_ar' => 'مقدمة حول البيتكوين، وفكرته، وتصميمه اللامركزي، ودوره في منظومة الأصول الرقمية.',
                'excerpt_en' => 'An introduction to Bitcoin, its purpose, decentralized design, and role in the digital asset ecosystem.',

                'content' => '<p>This is a placeholder for the full educational article about Bitcoin.</p>',
                'content_ar' => '<p>هذا نص تجريبي للمقال التعليمي الكامل حول البيتكوين.</p>',
                'content_en' => '<p>This is a placeholder for the full educational article about Bitcoin.</p>',

                'image' => null,

                'seo_title' => 'What is Bitcoin? | AQL Crypto Academy',
                'seo_title_ar' => 'ما هو البيتكوين؟ | أكاديمية AQL Crypto',
                'seo_title_en' => 'What is Bitcoin? | AQL Crypto Academy',

                'meta_description' => 'Learn what Bitcoin is, why it was created, and how it works as a decentralized digital asset.',
                'meta_description_ar' => 'تعرف على ماهية البيتكوين، ولماذا تم إنشاؤه، وكيف يعمل كأصل رقمي لامركزي.',
                'meta_description_en' => 'Learn what Bitcoin is, why it was created, and how it works as a decentralized digital asset.',

                'status' => 'published',
                'sort_order' => 1,
                'published_at' => now(),
            ],

            [
                'title' => 'Bitcoin History',
                'title_ar' => 'تاريخ البيتكوين',
                'title_en' => 'Bitcoin History',

                'slug' => 'history-of-bitcoin',

                'excerpt' => 'Explore the origins of Bitcoin, the publication of the Bitcoin whitepaper, and its early development.',
                'excerpt_ar' => 'استكشف نشأة البيتكوين، وصدور الورقة البيضاء، والمراحل الأولى من تطور الشبكة.',
                'excerpt_en' => 'Explore the origins of Bitcoin, the publication of the Bitcoin whitepaper, and its early development.',

                'content' => '<p>This is a placeholder for the full educational article about Bitcoin history.</p>',
                'content_ar' => '<p>هذا نص تجريبي للمقال التعليمي الكامل حول تاريخ البيتكوين.</p>',
                'content_en' => '<p>This is a placeholder for the full educational article about Bitcoin history.</p>',

                'image' => null,

                'seo_title' => 'Bitcoin History | AQL Crypto Academy',
                'seo_title_ar' => 'تاريخ البيتكوين | أكاديمية AQL Crypto',
                'seo_title_en' => 'Bitcoin History | AQL Crypto Academy',

                'meta_description' => 'Explore the history of Bitcoin, from its whitepaper and early development to its growth as a global digital asset.',
                'meta_description_ar' => 'استكشف تاريخ البيتكوين، بدءًا من الورقة البيضاء والمراحل الأولى لتطوره وصولًا إلى نموه كأصل رقمي عالمي.',
                'meta_description_en' => 'Explore the history of Bitcoin, from its whitepaper and early development to its growth as a global digital asset.',

                'status' => 'published',
                'sort_order' => 2,
                'published_at' => now(),
            ],

            [
                'title' => 'How Bitcoin Works',
                'title_ar' => 'كيف يعمل البيتكوين؟',
                'title_en' => 'How Bitcoin Works',

                'slug' => 'how-bitcoin-works',

                'excerpt' => 'Understand how Bitcoin transactions, blocks, nodes, and the Bitcoin network work together.',
                'excerpt_ar' => 'افهم كيف تعمل معاملات البيتكوين والكتل والعُقد وشبكة البيتكوين معًا.',
                'excerpt_en' => 'Understand how Bitcoin transactions, blocks, nodes, and the Bitcoin network work together.',

                'content' => '<p>This is a placeholder for the full educational article explaining how Bitcoin works.</p>',
                'content_ar' => '<p>هذا نص تجريبي للمقال التعليمي الكامل الذي يشرح كيفية عمل البيتكوين.</p>',
                'content_en' => '<p>This is a placeholder for the full educational article explaining how Bitcoin works.</p>',

                'image' => null,

                'seo_title' => 'How Bitcoin Works | AQL Crypto Academy',
                'seo_title_ar' => 'كيف يعمل البيتكوين؟ | أكاديمية AQL Crypto',
                'seo_title_en' => 'How Bitcoin Works | AQL Crypto Academy',

                'meta_description' => 'Learn how Bitcoin transactions, blocks, nodes, and the decentralized Bitcoin network work together.',
                'meta_description_ar' => 'تعرف على كيفية عمل معاملات البيتكوين والكتل والعُقد والشبكة اللامركزية معًا.',
                'meta_description_en' => 'Learn how Bitcoin transactions, blocks, nodes, and the decentralized Bitcoin network work together.',

                'status' => 'published',
                'sort_order' => 3,
                'published_at' => now(),
            ],

            [
                'title' => 'Bitcoin Mining Explained',
                'title_ar' => 'شرح تعدين البيتكوين',
                'title_en' => 'Bitcoin Mining Explained',

                'slug' => 'bitcoin-mining',

                'excerpt' => 'Learn what Bitcoin mining is, how proof of work operates, and how new blocks are added to the blockchain.',
                'excerpt_ar' => 'تعرف على تعدين البيتكوين، وكيف تعمل آلية إثبات العمل، وكيف تتم إضافة الكتل الجديدة إلى البلوكشين.',
                'excerpt_en' => 'Learn what Bitcoin mining is, how proof of work operates, and how new blocks are added to the blockchain.',

                'content' => '<p>This is a placeholder for the full educational article about Bitcoin mining.</p>',
                'content_ar' => '<p>هذا نص تجريبي للمقال التعليمي الكامل حول تعدين البيتكوين.</p>',
                'content_en' => '<p>This is a placeholder for the full educational article about Bitcoin mining.</p>',

                'image' => null,

                'seo_title' => 'Bitcoin Mining Explained | AQL Crypto Academy',
                'seo_title_ar' => 'شرح تعدين البيتكوين | أكاديمية AQL Crypto',
                'seo_title_en' => 'Bitcoin Mining Explained | AQL Crypto Academy',

                'meta_description' => 'Learn how Bitcoin mining and proof of work operate and how miners help secure the Bitcoin network.',
                'meta_description_ar' => 'تعرف على كيفية عمل تعدين البيتكوين وإثبات العمل ودور المعدنين في تأمين شبكة البيتكوين.',
                'meta_description_en' => 'Learn how Bitcoin mining and proof of work operate and how miners help secure the Bitcoin network.',

                'status' => 'published',
                'sort_order' => 4,
                'published_at' => now(),
            ],

            [
                'title' => 'Bitcoin Wallets',
                'title_ar' => 'محافظ البيتكوين',
                'title_en' => 'Bitcoin Wallets',

                'slug' => 'bitcoin-wallets',

                'excerpt' => 'Learn how Bitcoin wallets work, the difference between custodial and non-custodial wallets, and how private keys are used.',
                'excerpt_ar' => 'تعرف على كيفية عمل محافظ البيتكوين، والفرق بين المحافظ الحاضنة وغير الحاضنة، ودور المفاتيح الخاصة.',
                'excerpt_en' => 'Learn how Bitcoin wallets work, the difference between custodial and non-custodial wallets, and how private keys are used.',

                'content' => '<p>This is a placeholder for the full educational article about Bitcoin wallets.</p>',
                'content_ar' => '<p>هذا نص تجريبي للمقال التعليمي الكامل حول محافظ البيتكوين.</p>',
                'content_en' => '<p>This is a placeholder for the full educational article about Bitcoin wallets.</p>',

                'image' => null,

                'seo_title' => 'Bitcoin Wallets | AQL Crypto Academy',
                'seo_title_ar' => 'محافظ البيتكوين | أكاديمية AQL Crypto',
                'seo_title_en' => 'Bitcoin Wallets | AQL Crypto Academy',

                'meta_description' => 'Learn how Bitcoin wallets work, including private keys, addresses, custodial wallets, and non-custodial wallets.',
                'meta_description_ar' => 'تعرف على كيفية عمل محافظ البيتكوين، بما في ذلك المفاتيح الخاصة والعناوين والمحافظ الحاضنة وغير الحاضنة.',
                'meta_description_en' => 'Learn how Bitcoin wallets work, including private keys, addresses, custodial wallets, and non-custodial wallets.',

                'status' => 'published',
                'sort_order' => 5,
                'published_at' => now(),
            ],

            [
                'title' => 'Bitcoin vs Ethereum',
                'title_ar' => 'البيتكوين مقابل الإيثريوم',
                'title_en' => 'Bitcoin vs Ethereum',

                'slug' => 'bitcoin-vs-ethereum',

                'excerpt' => 'Understand the main differences between Bitcoin and Ethereum, including their purposes, networks, and use cases.',
                'excerpt_ar' => 'افهم أهم الاختلافات بين البيتكوين والإيثريوم، بما في ذلك أهدافهما وشبكاتهما واستخداماتهما.',
                'excerpt_en' => 'Understand the main differences between Bitcoin and Ethereum, including their purposes, networks, and use cases.',

                'content' => '<p>This is a placeholder for the full educational comparison between Bitcoin and Ethereum.</p>',
                'content_ar' => '<p>هذا نص تجريبي للمقال التعليمي الكامل الذي يقارن بين البيتكوين والإيثريوم.</p>',
                'content_en' => '<p>This is a placeholder for the full educational comparison between Bitcoin and Ethereum.</p>',

                'image' => null,

                'seo_title' => 'Bitcoin vs Ethereum | AQL Crypto Academy',
                'seo_title_ar' => 'البيتكوين مقابل الإيثريوم | أكاديمية AQL Crypto',
                'seo_title_en' => 'Bitcoin vs Ethereum | AQL Crypto Academy',

                'meta_description' => 'Compare Bitcoin and Ethereum and understand the key differences between their networks, purposes, and use cases.',
                'meta_description_ar' => 'قارن بين البيتكوين والإيثريوم وتعرف على أهم الاختلافات بين شبكاتهما وأهدافهما واستخداماتهما.',
                'meta_description_en' => 'Compare Bitcoin and Ethereum and understand the key differences between their networks, purposes, and use cases.',

                'status' => 'published',
                'sort_order' => 6,
                'published_at' => now(),
            ],

            [
                'title' => 'Bitcoin Halving',
                'title_ar' => 'تنصيف البيتكوين',
                'title_en' => 'Bitcoin Halving',

                'slug' => 'bitcoin-halving',

                'excerpt' => 'Learn what Bitcoin halving is, why it occurs, and how it changes the rate at which new bitcoins are created.',
                'excerpt_ar' => 'تعرف على تنصيف البيتكوين، ولماذا يحدث، وكيف يؤثر في معدل إنشاء وحدات البيتكوين الجديدة.',
                'excerpt_en' => 'Learn what Bitcoin halving is, why it occurs, and how it changes the rate at which new bitcoins are created.',

                'content' => '<p>This is a placeholder for the full educational article about Bitcoin halving.</p>',
                'content_ar' => '<p>هذا نص تجريبي للمقال التعليمي الكامل حول تنصيف البيتكوين.</p>',
                'content_en' => '<p>This is a placeholder for the full educational article about Bitcoin halving.</p>',

                'image' => null,

                'seo_title' => 'Bitcoin Halving | AQL Crypto Academy',
                'seo_title_ar' => 'تنصيف البيتكوين | أكاديمية AQL Crypto',
                'seo_title_en' => 'Bitcoin Halving | AQL Crypto Academy',

                'meta_description' => 'Learn what Bitcoin halving is, why it occurs, and how it affects the issuance of new bitcoins.',
                'meta_description_ar' => 'تعرف على تنصيف البيتكوين، ولماذا يحدث، وكيف يؤثر في إصدار وحدات البيتكوين الجديدة.',
                'meta_description_en' => 'Learn what Bitcoin halving is, why it occurs, and how it affects the issuance of new bitcoins.',

                'status' => 'published',
                'sort_order' => 7,
                'published_at' => now(),
            ],

            [
                'title' => 'Bitcoin Advantages and Risks',
                'title_ar' => 'مزايا ومخاطر البيتكوين',
                'title_en' => 'Bitcoin Advantages and Risks',

                'slug' => 'bitcoin-advantages-and-risks',

                'excerpt' => 'Explore the potential benefits, limitations, risks, and important considerations associated with Bitcoin.',
                'excerpt_ar' => 'استكشف المزايا المحتملة للبيتكوين، وحدوده، ومخاطره، وأهم الجوانب التي يجب أخذها في الاعتبار.',
                'excerpt_en' => 'Explore the potential benefits, limitations, risks, and important considerations associated with Bitcoin.',

                'content' => '<p>This is a placeholder for the full educational article about Bitcoin advantages and risks.</p>',
                'content_ar' => '<p>هذا نص تجريبي للمقال التعليمي الكامل حول مزايا ومخاطر البيتكوين.</p>',
                'content_en' => '<p>This is a placeholder for the full educational article about Bitcoin advantages and risks.</p>',

                'image' => null,

                'seo_title' => 'Bitcoin Advantages and Risks | AQL Crypto Academy',
                'seo_title_ar' => 'مزايا ومخاطر البيتكوين | أكاديمية AQL Crypto',
                'seo_title_en' => 'Bitcoin Advantages and Risks | AQL Crypto Academy',

                'meta_description' => 'Explore the potential advantages, limitations, risks, and important considerations related to Bitcoin.',
                'meta_description_ar' => 'استكشف المزايا المحتملة للبيتكوين، وحدوده، ومخاطره، وأهم الجوانب المتعلقة باستخدامه.',
                'meta_description_en' => 'Explore the potential advantages, limitations, risks, and important considerations related to Bitcoin.',

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