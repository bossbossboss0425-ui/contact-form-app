<?php

namespace Tests\Unit\Api;

use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 正常な入力値（必須項目と有効なタグ配列）が通る(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $data = [
            'category_id' => $category->id,
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => '1',
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => 'テストビル101',
            'detail' => 'お問い合わせ内容のテストです。',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $request = new StoreContactRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function 必須項目が欠けている場合、エラーになる(): void
    {
        $data = [];

        $request = new StoreContactRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());

        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('category_id', $errors);
        $this->assertArrayHasKey('first_name', $errors);
        $this->assertArrayHasKey('last_name', $errors);
        $this->assertArrayHasKey('gender', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('tel', $errors);
        $this->assertArrayHasKey('address', $errors);
        $this->assertArrayHasKey('detail', $errors);
    }

    /** @test */
    public function 不正な値は拒否される(): void
    {
        $data = [
            'category_id' => 9999,
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => '5',
            'email' => 'not-an-email',
            'tel' => '090-1234-5678',
            'address' => '東京都',
            'detail' => str_repeat('a', 121),
            'tag_ids' => [9999],
        ];
        $request = new StoreContactRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());

        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('category_id', $errors);
        $this->assertArrayHasKey('gender', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('tel', $errors);
        $this->assertArrayHasKey('detail', $errors);
        $this->assertArrayHasKey('tag_ids.0', $errors);
    }
}
