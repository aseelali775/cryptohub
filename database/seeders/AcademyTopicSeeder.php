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
                'slug' => 'bitcoin',
                'description' => 'تعرف على Bitcoin، تاريخه، طريقة عمله، التعدين، المحافظ، والتنصيف والمفاهيم الأساسية المرتبطة به.',
                'image' => null,
                'sort_order' => 1,
                'is_active' => true,
            ],

            [
                'name' => 'Blockchain',
                'slug' => 'blockchain',
                'description' => 'افهم تقنية Blockchain، كيفية تخزين البيانات والتحقق منها، وآلية عمل الشبكات اللامركزية.',
                'image' => null,
                'sort_order' => 2,
                'is_active' => true,
            ],

            [
                'name' => 'DeFi',
                'slug' => 'defi',
                'description' => 'تعرف على التمويل اللامركزي، البروتوكولات، التداول اللامركزي، الإقراض، والسيولة.',
                'image' => null,
                'sort_order' => 3,
                'is_active' => true,
            ],

            [
                'name' => 'Staking',
                'slug' => 'staking',
                'description' => 'تعلم مفهوم Staking، آلية إثبات الحصة، وكيف تعمل مكافآت المشاركة في تأمين الشبكات.',
                'image' => null,
                'sort_order' => 4,
                'is_active' => true,
            ],

            [
                'name' => 'Web3',
                'slug' => 'web3',
                'description' => 'اكتشف مفهوم Web3 والعلاقة بين التطبيقات اللامركزية وBlockchain والأصول الرقمية.',
                'image' => null,
                'sort_order' => 5,
                'is_active' => true,
            ],

            [
                'name' => 'NFTs',
                'slug' => 'nfts',
                'description' => 'تعرف على NFTs، كيفية عملها، استخداماتها، وأسواقها والعلاقة بينها وبين تقنية Blockchain.',
                'image' => null,
                'sort_order' => 6,
                'is_active' => true,
            ],

            [
                'name' => 'Wallets',
                'slug' => 'wallets',
                'description' => 'تعلم أساسيات محافظ العملات الرقمية، المفاتيح الخاصة، العناوين، والمحافظ الساخنة والباردة.',
                'image' => null,
                'sort_order' => 7,
                'is_active' => true,
            ],

            [
                'name' => 'Trading Basics',
                'slug' => 'trading-basics',
                'description' => 'مقدمة تعليمية إلى أساسيات تداول العملات الرقمية، أنواع الأوامر، قراءة الرسوم البيانية وإدارة المخاطر.',
                'image' => null,
                'sort_order' => 8,
                'is_active' => true,
            ],

            [
                'name' => 'Security',
                'slug' => 'security',
                'description' => 'تعلم أساسيات حماية العملات الرقمية والحسابات والمحافظ من الاحتيال والهجمات والأخطاء الشائعة.',
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