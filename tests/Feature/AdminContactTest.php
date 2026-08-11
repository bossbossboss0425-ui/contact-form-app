<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 未認証ユーザーは管理画面にアクセスできずログインへリダイレクトされること(): void
    {
        $response = $this->get(route('admin.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 認証されたユーザーのみが管理ダッシュボードを表示できること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function 検索フィルタと7件ずつのページネーションが機能すること(): void
    {
        $user = User::factory()->create();
        $categoryA = Category::factory()->create(['content' => 'カテゴリA']);
        $categoryB = Category::factory()->create(['content' => 'カテゴリB']);

        // 該当するデータを8件作成（ページネーション検証のため）
        Contact::factory()->count(8)->create([
            'first_name' => '検索ヒット',
            'gender' => '1',
            'category_id' => $categoryA->id,
            'created_at' => '2026-08-01 10:00:00',
        ]);

        // 検索対象外のデータを作成
        Contact::factory()->create([
            'first_name' => '対象外',
            'gender' => '2',
            'category_id' => $categoryB->id,
            'created_at' => '2026-01-01 10:00:00',
        ]);

        // 検索パラメータ付きでリクエスト
        $queryParams = [
            'keyword' => '検索ヒット',
            'gender' => '1',
            'category_id' => $categoryA->id,
            'date' => '2026-08-01',
        ];

        $response = $this->actingAs($user)->get(route('admin.index', $queryParams));

        $response->assertStatus(200)
            ->assertSee('検索ヒット')
            ->assertDontSee('対象外');

        // ページネーション（1ページあたり7件表示）の確認
        $contacts = $response->viewData('contacts');
        $this->assertEquals(7, $contacts->count());
        $this->assertEquals(8, $contacts->total());
    }

    /** @test */
    public function 指定したお問い合わせがカテゴリ情報付きで詳細ページに表示されること(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['content' => '詳細用カテゴリ']);
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '詳細確認太郎',
        ]);

        $response = $this->actingAs($user)->get(route('admin.contact.show', $contact));

        $response->assertStatus(200)
            ->assertViewIs('admin.show')
            ->assertSee('詳細確認太郎')
            ->assertSee('詳細用カテゴリ');
    }

    /** @test */
    public function お問い合わせレコードが削除され管理画面にリダイレクトされること(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->delete(route('admin.contact.destroy', $contact));

        $response->assertRedirect(route('admin.index'));

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }
}
