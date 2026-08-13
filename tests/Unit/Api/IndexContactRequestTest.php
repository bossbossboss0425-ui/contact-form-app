<?php

namespace Tests\Unit\Api;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 有効な検索パラメータが許可される(): void
    {
        $category = Category::factory()->create();

        $data = [
            'keyword' => 'テスト',
            'gender' => '1',
            'category_id' => $category->id,
            'date' => '2026-08-13',
        ];

        $request = new IndexContactRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function 空の検索パラメータでも許可される(): void
    {
        $request = new IndexContactRequest;
        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function 不正な値（性別の範囲外・存在しないカテゴリ_i_d等）が拒否される(): void
    {
        $data = [
            'gender' => '99',          // in:1,2,3 違反
            'category_id' => 9999,          // exists:categories,id 違反
            'date' => 'invalid-date', // date 形式違反
        ];

        $request = new IndexContactRequest;
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
    }
}
