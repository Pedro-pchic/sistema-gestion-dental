<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'description', 'image_path', 'published_on', 'expires_on', 'is_published', 'sort_order'])]
class Announcement extends Model
{
    protected function casts(): array
    {
        return ['published_on' => 'date', 'expires_on' => 'date', 'is_published' => 'boolean', 'sort_order' => 'integer'];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereDate('published_on', '<=', today(config('appointments.timezone')))
            ->where(fn (Builder $query): Builder => $query->whereNull('expires_on')->orWhereDate('expires_on', '>=', today(config('appointments.timezone'))));
    }
}
