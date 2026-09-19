<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbArticle extends Model
{
    protected $fillable = ['title','category','body','created_by','is_published'];
    public function author(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
}
