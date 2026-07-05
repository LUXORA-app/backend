<?php

namespace App\Models;

use App\Support\PublicMediaUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Landmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'location',
        'latitude',
        'longitude',
        'image_url',
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => PublicMediaUrl::normalize($value),
        );
    }
}
