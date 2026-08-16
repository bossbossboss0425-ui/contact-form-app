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
        // Arrange
        $category = Category::factory()->create(['content' => '問合せカテゴリ']);

        $validData = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => '1',
            'email' => 'test@example.com',
            'tel1' => '090',
            'tel2' => '1234',
            'tel3' => '5678',
            'address' => '東京都渋谷区1-1-1',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容のテストです。',
        ];

        // Act
        $response = $this->post(route('contact.confirm'), $validData);

        // バリデーションエラーが出ないこと
        $response->assertSessionHasNoErrors();

        // Assert
        $response->assertStatus(200)
            ->assertSee('山田')
            ->assertSee('test@example.com');

        // バリデーションエラーのテスト
        $invalidResponse = $this->post(route('contact.confirm'), []);
        $invalidResponse->assertSessionHasErrors(['first_name', 'last_name', 'email', 'tel', 'address', 'category_id', 'detail']);
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
            'gender' => '2',
            'email' => 'sato@example.com',
            'tel1' => '080',
            'tel2' => '9876',
            'tel3' => '5432',
            'address' => '大阪府大阪市1-1-1',
            'building' => '大阪ビル202',
            'category_id' => $category->id,
            'detail' => '送信テストです。',
            'tag_ids' => $tags->pluck('id')->toArray(), // ContactRequestの仕様に合わせtag_idsに指定
        ];

        // Act
        $response = $this->post(route('contact.store'), $submitData);

        // バリデーションエラーが出ないこと
        $response->assertSessionHasNoErrors();

        // Assert
        $response->assertRedirect(route('contact.thanks'));

        // DB（contactsテーブル）にデータが保存されたか確認
        $this->assertDatabaseHas('contacts', [
            'first_name' => '佐藤',
            'email' => 'sato@example.com',
        ]);

        // DB（contact_tag 中間テーブル）に紐付けが記録されたか確認
        $contact = Contact::where('email', 'sato@example.com')->first();
        if ($contact) {
            foreach ($tags as $tag) {
                $this->assertDatabaseHas('contact_tag', [
                    'contact_id' => $contact->id,
                    'tag_id' => $tag->id,
                ]);
            }
        }

        // バリデーションエラーのテスト
        $invalidResponse = $this->post(route('contact.store'), []);
        $invalidResponse->assertSessionHasErrors();
    }
}
