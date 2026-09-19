<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatGroup extends Model
{
    protected $fillable = ['name','description','is_announcement','is_private','created_by'];
    protected $casts = ['is_announcement'=>'boolean','is_private'=>'boolean'];

    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
    public function members(): BelongsToMany { return $this->belongsToMany(User::class,'chat_group_members')->withPivot('role','last_read_at')->withTimestamps(); }
    public function messages(): HasMany { return $this->hasMany(ChatMessage::class); }
    public function memberRows(): HasMany { return $this->hasMany(ChatGroupMember::class); }

    public function canPost(User $user): bool
    {
        if (!$this->members()->where('users.id',$user->id)->exists() && !$user->hasAnyRole(['SuperAdmin','Admin'])) return false;
        if ($this->is_announcement) {
            return $user->hasAnyRole(['SuperAdmin','Admin']) || $this->memberRows()->where('user_id',$user->id)->where('role','admin')->exists() || $this->created_by === $user->id;
        }
        return true;
    }
}
