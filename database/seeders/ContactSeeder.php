<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存の全タグＩＤを取得
        $tags = Tag::all();

        // 20件のお問い合わせ作成し、1件ずつにループ処理
        Contact::factory(20)->create()->each(function (Contact $contact) use ($tags) {

            // ランダムに1～3件のタグを選択して紐づけ
            $randomTagIds = $tags->random(rand(1, 3))->pluck('id');
            $contact->tags()->attach($randomTagIds);
        });

    }
}
