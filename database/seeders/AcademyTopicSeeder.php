<?php

namespace Database\Seeders;

use App\Models\AcademyTopic;
use Illuminate\Database\Seeder;

class AcademyTopicSeeder extends Seeder
{
    public function run(): void
    {
        $topics = [
            [
                'name' => 'Bitcoin',
                'name_ar' => 'بيتكوين',
                'name_en' => 'Bitcoin',
                'slug' => 'bitcoin',

                'description' => 'Learn about Bitcoin, its history, how it works, mining, wallets, halving, and core concepts.',
                'description_ar' => 'تعرف على بيتكوين، تاريخه، طريقة عمله، التعدين، المحافظ، والتنصيف، والمفاهيم الأساسية المرتبطة به.',
                'description_en' => 'Learn about Bitcoin, its history, how it works, mining, wallets, halving, and core concepts.',

                'image' => null,
                'sort_order' => 1,
                'is_active' => true,
            ],

            [
                'name' => 'Blockchain',
                'name_ar' => 'البلوكشين',
                'name_en' => 'Blockchain',
                'slug' => 'blockchain',

                'description' => 'Understand blockchain technology, how data is stored and verified, and how decentralized networks work.',
                'description_ar' => 'افهم تقنية البلوكشين، كيفية تخزين البيانات والتحقق منها، وآلية عمل الشبكات اللامركزية.',
                'description_en' => 'Understand blockchain technology, how data is stored and verified, and how decentralized networks work.',

                'image' => null,
                'sort_order' => 2,
                'is_active' => true,
            ],

            [
                'name' => 'DeFi',
                'name_ar' => 'التمويل اللامركزي',
                'name_en' => 'DeFi',
                'slug' => 'defi',

                'description' => 'Learn about decentralized finance, protocols, decentralized exchanges, lending, and liquidity.',
                'description_ar' => 'تعرف على التمويل اللامركزي، البروتوكولات، التداول اللامركزي، الإقراض، والسيولة.',
                'description_en' => 'Learn about decentralized finance, protocols, decentralized exchanges, lending, and liquidity.',

                'image' => null,
                'sort_order' => 3,
                'is_active' => true,
            ],

            [
                'name' => 'Staking',
                'name_ar' => 'التخزين والمشاركة',
                'name_en' => 'Staking',
                'slug' => 'staking',

                'description' => 'Learn about staking, proof of stake, and how participation rewards help secure blockchain networks.',
                'description_ar' => 'تعلم مفهوم التخزين والمشاركة (Staking)، وآلية إثبات الحصة، وكيف تعمل مكافآت المشاركة في تأمين الشبكات.',
                'description_en' => 'Learn about staking, proof of stake, and how participation rewards help secure blockchain networks.',

                'image' => null,
                'sort_order' => 4,
                'is_active' => true,
            ],

            [
                'name' => 'Web3',
                'name_ar' => 'ويب 3',
                'name_en' => 'Web3',
                'slug' => 'web3',

                'description' => 'Explore Web3 and the relationship between decentralized applications, blockchain, and digital assets.',
                'description_ar' => 'اكتشف مفهوم Web3 والعلاقة بين التطبيقات اللامركزية والبلوكشين والأصول الرقمية.',
                'description_en' => 'Explore Web3 and the relationship between decentralized applications, blockchain, and digital assets.',

                'image' => null,
                'sort_order' => 5,
                'is_active' => true,
            ],

            [
                'name' => 'NFTs',
                'name_ar' => 'الرموز غير القابلة للاستبدال',
                'name_en' => 'NFTs',
                'slug' => 'nfts',

                'description' => 'Learn about NFTs, how they work, their uses, marketplaces, and their relationship with blockchain technology.',
                'description_ar' => 'تعرف على الرموز غير القابلة للاستبدال (NFTs)، وكيف تعمل، واستخداماتها، وأسواقها، وعلاقتها بتقنية البلوكشين.',
                'description_en' => 'Learn about NFTs, how they work, their uses, marketplaces, and their relationship with blockchain technology.',

                'image' => null,
                'sort_order' => 6,
                'is_active' => true,
            ],

            [
                'name' => 'Wallets',
                'name_ar' => 'المحافظ',
                'name_en' => 'Wallets',
                'slug' => 'wallets',

                'description' => 'Learn the basics of cryptocurrency wallets, private keys, addresses, and hot and cold wallets.',
                'description_ar' => 'تعلم أساسيات محافظ العملات الرقمية، والمفاتيح الخاصة، والعناوين، والمحافظ الساخنة والباردة.',
                'description_en' => 'Learn the basics of cryptocurrency wallets, private keys, addresses, and hot and cold wallets.',

                'image' => null,
                'sort_order' => 7,
                'is_active' => true,
            ],

            [
                'name' => 'Trading Basics',
                'name_ar' => 'أساسيات التداول',
                'name_en' => 'Trading Basics',
                'slug' => 'trading-basics',

                'description' => 'An educational introduction to cryptocurrency trading, order types, chart reading, and risk management.',
                'description_ar' => 'مقدمة تعليمية إلى أساسيات تداول العملات الرقمية، وأنواع الأوامر، وقراءة الرسوم البيانية، وإدارة المخاطر.',
                'description_en' => 'An educational introduction to cryptocurrency trading, order types, chart reading, and risk management.',

                'image' => null,
                'sort_order' => 8,
                'is_active' => true,
            ],

            [
                'name' => 'Security',
                'name_ar' => 'الأمان',
                'name_en' => 'Security',
                'slug' => 'security',

                'description' => 'Learn the basics of protecting cryptocurrency, accounts, and wallets from scams, attacks, and common mistakes.',
                'description_ar' => 'تعلم أساسيات حماية العملات الرقمية والحسابات والمحافظ من الاحتيال والهجمات والأخطاء الشائعة.',
                'description_en' => 'Learn the basics of protecting cryptocurrency, accounts, and wallets from scams, attacks, and common mistakes.',

                'image' => null,
                'sort_order' => 9,
                'is_active' => true,
            ],
        ];

        foreach ($topics as $topic) {
            AcademyTopic::updateOrCreate(
                ['slug' => $topic['slug']],
                $topic
            );
        }
    }
}