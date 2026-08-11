<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTagTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 認証済みユーザーは編集画面表示・作成・更新・削除ができ_各操作後にadminへリダイレクトされること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['name' => '編集前タグ']);

        // 1. GET /admin/tags/{tag}/edit で編集画面表示
        $editResponse = $this->actingAs($user)->get(route('admin.tag.edit', $tag));
        $editResponse->assertStatus(200);

        // 2. POST /admin/tags でタグ作成＆ /admin へリダイレクト
        $storeResponse = $this->actingAs($user)->post(route('admin.tag.store'), [
            'name' => '新規タグ',
        ]);
        $storeResponse->assertRedirect(route('admin.index'));
        $this->assertDatabaseHas('tags', ['name' => '新規タグ']);

        // 3. PUT /admin/tags/{tag} で更新＆ /admin へリダイレクト
        $updateResponse = $this->actingAs($user)->put(route('admin.tag.update', $tag), [
            'name' => '更新後タグ',
        ]);
        $updateResponse->assertRedirect(route('admin.index'));
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '更新後タグ',
        ]);

        // 4. DELETE /admin/tags/{tag} で削除＆ /admin へリダイレクト
        $deleteResponse = $this->actingAs($user)->delete(route('admin.tag.destroy', $tag));
        $deleteResponse->assertRedirect(route('admin.index'));
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    /** @test */
    public function 未認証ユーザーはタグ操作が拒否されloginにリダイレクトされること(): void
    {
        $tag = Tag::factory()->create();

        // 編集画面表示
        $this->get(route('admin.tag.edit', $tag))
            ->assertRedirect(route('login'));

        // 作成
        $this->post(route('admin.tag.store'), ['name' => 'テスト'])
            ->assertRedirect(route('login'));

        // 更新
        $this->put(route('admin.tag.update', $tag), ['name' => 'テスト'])
            ->assertRedirect(route('login'));

        // 削除
        $this->delete(route('admin.tag.destroy', $tag))
            ->assertRedirect(route('login'));
    }
}
