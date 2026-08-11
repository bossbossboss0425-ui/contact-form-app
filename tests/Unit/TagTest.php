<?php

namespace Tests\Unit;

use App\Http\Requests\TagRequest;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function タグ名の必須入力チェックが有効であること(): void
    {
        // Arrange
        $data = ['name' => ''];

        // Act
        $request = new TagRequest;
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function タグ名の文字数制限が有効であること(): void
    {
        // Arrange
        $data = ['name' => str_repeat('あ', 51)];

        // Act
        $request = new TagRequest;
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function タグ名の一意性（重複禁止）が維持されていること(): void
    {
        // Arrenge
        Tag::factory()->create(['name' => '既存タグ']);

        $data = ['name' => '既存タグ'];

        // Act
        $request = new TagRequest;
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function タグ更新時に自身の名前維持は可能だが他で使用済みのタグ名への変更は拒否すること(): void
    {
        // Arrange
        $this->seed();
        $user = User::first();

        $tag1 = Tag::factory()->create(['name' => 'タグA']);
        $tag2 = Tag::factory()->create(['name' => 'タグB']);

        // Act & Assert ①：自身の名前（タグA）をそのまま維持して更新する場合（成功すべき）
        $responseSelf = $this->actingAs($user)->put(route('admin.tag.update', $tag1->id), [
            'name' => 'タグA',
        ]);
        $responseSelf->assertSessionHasNoErrors();

        // Act & Assert ②：すでに存在する他のタグ名（タグB）に変更しようとする場合（拒否されるべき）
        $responseOther = $this->actingAs($user)->put(route('admin.tag.update', $tag1->id), [
            'name' => 'タグB',
        ]);
        $responseOther->assertSessionHasErrors(['name']);
    }
}
