<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    // 1. お問い合わせ一覧

    /** @test */
    public function お問い合わせ一覧が_jso_n形式で取得できページネーションが含まれる(): void
    {

        $this->withoutExceptionHandling(); // ★ この行を追加！

        $category = Category::factory()->create();
        Contact::factory()->count(15)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'full_name',
                        'first_name',
                        'last_name',
                        'gender',
                        'gender_label',
                        'email',
                        'tel',
                        'address',
                        'building',
                        'category' => ['id', 'content'],
                        'tags',
                        'detail',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function キーワードやカテゴリ等の検索条件で一覧が絞り込める(): void
    {
        $categoryA = Category::factory()->create(['content' => '商品について']);
        $categoryB = Category::factory()->create(['content' => 'その他']);

        // 検索対象データ
        $target = Contact::factory()->create([
            'first_name' => '太郎',
            'last_name' => 'テスト',
            'gender' => 1,
            'category_id' => $categoryA->id,
            'created_at' => '2026-08-13 10:00:00',
        ]);

        // 検索除外データ
        Contact::factory()->create([
            'first_name' => '花子',
            'last_name' => '山田',
            'gender' => 2,
            'category_id' => $categoryB->id,
            'created_at' => '2026-01-01 10:00:00',
        ]);

        $queryParams = [
            'keyword' => 'テスト',
            'gender' => '1',
            'category_id' => $categoryA->id,
            'date' => '2026-08-13',
        ];

        $response = $this->getJson('/api/v1/contacts?'.http_build_query($queryParams));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);
    }

    /** @test */
    public function 一覧検索で不正なパラメータを送ると422エラーが返る(): void
    {
        $response = $this->getJson('/api/v1/contacts?gender=99&category_id=9999');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gender', 'category_id']);
    }

    // 2. お問い合わせ詳細

    /** @test */
    public function お問い合わせ詳細が_jso_n形式で取得できる(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $contact->id,
                    'email' => $contact->email,
                ],
            ]);
    }

    /** @test */
    public function 存在しない_i_dのお問い合わせ詳細を取得すると404エラーが返る(): void
    {
        $response = $this->getJson('/api/v1/contacts/99999');

        $response->assertStatus(404);
    }

    // 3. お問い合わせ作成

    /** @test */
    public function お問い合わせが正常に作成され201が返る(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $payload = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'テストのお問い合わせ内容です。',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $response = $this->postJson('/api/v1/contacts', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.email', 'yamada@example.com');

        $this->assertDatabaseHas('contacts', [
            'email' => 'yamada@example.com',
            'first_name' => '太郎',
        ]);
    }

    /** @test */
    public function 作成時にバリデーションエラーがあると422が返る(): void
    {
        $response = $this->postJson('/api/v1/contacts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'gender', 'email', 'tel', 'address', 'category_id', 'detail']);
    }

    // 4. お問い合わせ更新

    /** @test */
    public function お問い合わせが正常に更新され200が返る(): void
    {
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $payload = [
            'first_name' => '更新次郎',
            'last_name' => '佐藤',
            'gender' => 1,
            'email' => 'updated@example.com',
            'tel' => '08098765432',
            'address' => '大阪府大阪市1-1',
            'building' => '更新ビル202',
            'category_id' => $category->id,
            'detail' => '更新されたお問い合わせ内容です。',
        ];

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.first_name', '更新次郎')
            ->assertJsonPath('data.email', 'updated@example.com');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'email' => 'updated@example.com',
        ]);
    }

    /** @test */
    public function 存在しない_i_dのお問い合わせを更新しようとすると404エラーが返る(): void
    {
        $category = Category::factory()->create();

        $payload = [
            'first_name' => '更新次郎',
            'last_name' => '佐藤',
            'gender' => 1,
            'email' => 'updated@example.com',
            'tel' => '08098765432',
            'address' => '大阪府大阪市1-1',
            'category_id' => $category->id,
            'detail' => '更新された内容',
        ];

        $response = $this->putJson('/api/v1/contacts/99999', $payload);

        $response->assertStatus(404);
    }

    /** @test */
    public function 更新時にバリデーションエラーがあると422が返る(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'email']);
    }

    // 5. お問い合わせ削除

    /** @test */
    public function お問い合わせが正常に削除され204が返る(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    /** @test */
    public function 存在しない_i_dのお問い合わせを削除しようとすると404エラーが返る(): void
    {
        $response = $this->deleteJson('/api/v1/contacts/99999');

        $response->assertStatus(404);
    }
}
