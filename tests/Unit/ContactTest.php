<?php

namespace Tests\Unit;

use App\Http\Requests\ContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function キーワード・性別・カテゴリ・日付フィルタが有効であること(): void
    {
        // Arrange
        $this->seed();
        $user = User::first();
        $category = Category::factory()->create();
        $targetContact = Contact::factory()->create([
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => '1',
            'category_id' => $category->id,
            'created_at' => '2026-08-01 10:00:00',
        ]);

        Contact::factory()->create([
            'last_name' => '鈴木',
            'first_name' => '花子',
            'gender' => '2',
            'created_at' => '2026-07-01 10:00:00',
        ]);

        // Act
        $response = $this->actingAs($user)->get(route('admin.index', [
            'keyword' => '山田',
            'gender' => '1',
            'category_id' => $category->id,
            'date' => '2026-08-01',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('contacts', function ($contacts) use ($targetContact) {
            return $contacts->count() === 1 && $contacts->first()->id === $targetContact->id;
        });
    }

    /** @test */
    public function 不正な性別値を拒否すること(): void
    {
        $request = new ContactRequest;

        $validator = Validator::make(
            ['gender' => 999],
            $request->rules()
        );
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    /** @test */
    public function 全ての必須項目とタグ入力を受け付けて保存できること(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $contactData = [
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせのテスト本文です。',
        ];

        $contact = Contact::create($contactData);
        $contact->tags()->sync($tags->pluck('id'));

        $this->assertDatabaseHas('contacts', [
            'email' => 'test@example.com',
            'last_name' => '山田',
        ]);
        $this->assertCount(2, $contact->tags);
    }

    /** @test */
    public function 不正な電話番号形式は拒否すること(): void
    {
        $request = new ContactRequest;

        $validator = Validator::make(
            ['tel' => '090-1234-5678'], // ハイフン入りの不正な形式
            $request->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tel', $validator->errors()->toArray());
    }
}
