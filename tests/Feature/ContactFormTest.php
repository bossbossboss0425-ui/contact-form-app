<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function お問い合わせフォーム入力ページが正常に表示されカテゴリとタグが渡されること(): void
    {
        // Arrange
        $category = Category::factory()->create(['content' => 'テストカテゴリ']);
        $tag = Tag::factory()->create(['name' => 'テストタグ']);

        // Act
        $response = $this->get(route('contact.index'));

        // Assert
        $response->assertStatus(200)
            ->assertViewHas('categories')
            ->assertViewHas('tags')
            ->assertSee('テストカテゴリ')
            ->assertSee('テストタグ');
    }

    /** @test */
    public function サンクスページが正常に表示されること(): void
    {
        // Act
        $response = $this->get(route('contact.thanks'));

        // Assert
        $response->assertStatus(200);
    }

    /** @test */
    public function お問い合わせ確認ページ表示とバリデーション(): void
    {
        // Arrange: 成功データの作成
        $category = Category::factory()->create(['name' => '問合せカテゴリ']);
        $validData = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tell1' => '090',
            'tell2' => '1234',
            'tell3' => '5678',
            'address' => '東京都渋谷区',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容のテストです。',
        ];

        // Act & Assert ①: バリデーション通過時
        $response = $this->post(route('contact.confirm'), $validData);
        $response->assertStatus(200)
            ->assertViewIs('contact.confirm') // ビュー名（※環境に合わせて確認）
            ->assertSee('山田')
            ->assertSee('test@example.com')
            ->assertSee('問合せカテゴリ');

        // Act & Assert ②: バリデーションエラー時
        $invalidResponse = $this->post(route('contact.confirm'), []);
        $invalidResponse->assertSessionHasErrors(['first_name', 'last_name', 'email']);
    }

    /** @test */
    public function お問い合わせ送信と_d_b保存およびタグの紐付け(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $submitData = [
            'first_name' => '佐藤',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'sato@example.com',
            'tell' => '08098765432',
            'address' => '大阪府大阪市',
            'category_id' => $category->id,
            'detail' => '送信テストです。',
            'category' => $category->name,
            'tags' => $tags->pluck('id')->toArray(), // タグIDの配列
        ];

        // Act & Assert ①: 送信成功時
        $response = $this->post(route('contact.store'), $submitData);

        // /thanks へリダイレクト確認
        $response->assertRedirect(route('contact.thanks'));

        // DB（contactsテーブル）にデータが保存されたか確認
        $this->assertDatabaseHas('contacts', [
            'first_name' => '佐藤',
            'email' => 'sato@example.com',
        ]);

        // DB（contact_tag 中間テーブル）に紐付けが記録されたか確認
        $contact = Contact::where('email', 'sato@example.com')->first();
        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }

        // Act & Assert ②: バリデーションエラー時
        $invalidResponse = $this->post(route('contact.store'), []);
        $invalidResponse->assertSessionHasErrors();
    }
}
