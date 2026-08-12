<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExportContactTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 未ログインユーザーはエクスポートできずログイン画面へリダイレクトされる(): void
    {
        // Act
        // 未ログイン状態でアクセス（URL直打ちへの対応）
        $response = $this->get(route('admin.contact.export'));

        // Assert
        $response->assertRedirect('/login');
    }

    /** @test */
    public function ログイン済み管理者がフィルタ未指定で全件を新着順でダウンロードできる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // 作成日時の異なるお問い合わせデータを作成
        $oldContact = Contact::factory()->create([
            'category_id' => $category->id,
            'created_at' => now()->subDays(2),
        ]);
        $newContact = Contact::factory()->create([
            'category_id' => $category->id,
            'created_at' => now(),
        ]);

        // Act
        // 管理者としてログインし、フィルタ未指定でエクスポート
        $response = $this->actingAs($user)->get(route('admin.contact.export'));

        // Assert
        // レスポンスステータスとヘッダーの検証
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv;charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment;filename="contact.csv"');

        // ストリーミング出力コンテンツの取得と検証
        $content = $response->streamedContent();

        // 先頭にBOMが含まれてるか検証
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // 新着順検証
        $this->assertTrue(
            strpos($content, $newContact->email) < strpos($content, $oldContact->email)
        );
    }

    /** @test */
    public function フィルタ条件指定時に一致するデータのみ出力される(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // 検索に「ヒットするデータ」と「ヒットしないデータ」を作成
        $targetContact = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '武',
            'email' => 'target@example.com',
        ]);
        $otherContact = Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '川田',
            'email' => 'other@example.com',
        ]);

        // Act
        // 管理者としてログインし、キーワードフィルタを指定してエクスポート
        $response = $this->actingAs($user)->get(route('admin.contact.export', [
            'keyword' => '武',
        ]));

        // Assert
        // レスポンスステータスの検証
        $response->assertOk();

        // CSV結果にヒットしたデータが含まれ、ヒットしないデータが含まれないか検証
        $content = $response->streamedContent();
        $this->assertStringContainsString('target@example.com', $content);
        $this->assertStringNotContainsString('other@example.com', $content);
    }
}
