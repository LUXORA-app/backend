<?php

namespace App\Models;

use App\Support\PublicMediaUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_url',
        'translated_text',
        'original_text',
        'confidence_score',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => PublicMediaUrl::normalize($value),
        );
    }
}
