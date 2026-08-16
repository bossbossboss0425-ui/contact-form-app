<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'first_name',
        'last_name',
        'email',
        'gender',
        'tel',
        'address',
        'building',
        'detail',
    ];

    // お問い合わせは1つのカテゴリに属する
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // お問い合わせは複数のタグを持つ
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'contact_tag');
    }

    // 性別のラベルを取得するアクセサ
    public function getGenderLabelAttribute(): string
    {
        return match ((int) $this->gender) {
            1 => '男性',
            2 => '女性',
            3 => 'その他',
        };
    }
}
