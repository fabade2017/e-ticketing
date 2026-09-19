<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['name','code','description'];
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function tickets(): HasMany { return $this->hasMany(Ticket::class); }
}
