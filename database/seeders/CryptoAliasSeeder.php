<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cryptocurrency;
use App\Models\CryptoAlias;

class CryptoAliasSeeder extends Seeder
{
    public function run()
    {
        // مصفوفة ثابتة لأهم الأسماء البديلة
        $aliasesData = [
            'BTC'  => ['Bitcoin ETF', 'Spot Bitcoin ETF', 'Digital Gold', 'Satoshi', 'BlackRock Bitcoin'],
            'ETH'  => ['Ether', 'Ethereum ETF', 'Vitalik', 'ERC-20', 'Layer 2', 'Ethereum Foundation'],
            'SOL'  => ['Solana Mobile', 'Solana ETF', 'SPL Token', 'Solana Network'],
            'XRP'  => ['Ripple', 'SEC', 'XRP Ledger', 'Brad Garlinghouse'],
            'ADA'  => ['Cardano', 'Charles Hoskinson', 'Vasil'],
            'BNB'  => ['Binance Coin', 'CZ', 'Binance Smart Chain', 'BSC'],
            'DOGE' => ['Dogecoin', 'Elon Musk', 'Twitter Crypto'],
            'LINK' => ['Chainlink', 'Oracles', 'CCIP'],
            'MATIC'=> ['Polygon','Polygon zkEVM','Polygon Labs'], // ملاحظة: Polygon رمزها MATIC أو POL مؤخرا
            'TON'  => ['Toncoin', 'Telegram', 'The Open Network'],
        ];

        foreach ($aliasesData as $symbol => $aliases) {
            $crypto = Cryptocurrency::where('symbol', $symbol)->first();
            if ($crypto) {
                foreach ($aliases as $alias) {
                    CryptoAlias::firstOrCreate([
                        'cryptocurrency_id' => $crypto->id,
                        'alias'             => $alias
                    ]);
                }
            }
        }

        $this->command->info('✅ Crypto Aliases seeded successfully!');
    }
}
