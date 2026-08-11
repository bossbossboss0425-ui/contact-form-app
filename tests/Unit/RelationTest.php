<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function contactが_categoryに属していること(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        // Act & Assert
        $this->assertInstanceOf(Category::class, $contact->category);
        $this->assertEquals($category->id, $contact->category->id);
    }

    /** @test */
    public function contactと_tagが多対多のリレーションを持つこと(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $contact = Contact::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        // 中間テーブルに紐付け
        $contact->tags()->attach($tags->pluck('id'));

        // Act & Assert
        // Contactから紐付いたTagが3件取得できるか検証
        $this->assertCount(3, $contact->tags);
        $this->assertInstanceOf(Tag::class, $contact->tags->first());
    }

    /** @test */
    public function categoryは複数の_contactを持つこと(): void
    {
        // Arrange
        $category = Category::factory()->create();
        Contact::factory()->count(2)->create(['category_id' => $category->id]);

        // Act & Assert
        // Categoryから所属するContactが2件取得できるか検証
        $this->assertCount(2, $category->contacts);
        $this->assertInstanceOf(Contact::class, $category->contacts->first());
    }

    /** @test */
    public function tagは複数の_contactを持つこと(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $contacts = Contact::factory()->count(2)->create();

        // タグ側に複数のContactを紐付け
        $tag->contacts()->attach($contacts->pluck('id'));

        // Act & Assert
        // Tagから紐付いたContactが2件取得できるか検証
        $this->assertCount(2, $tag->contacts);
        $this->assertInstanceOf(Contact::class, $tag->contacts->first());
    }
}
