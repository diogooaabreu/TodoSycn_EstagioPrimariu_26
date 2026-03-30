<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TodoShare extends Model
{
    protected $fillable = ['todo_id', 'shared_with_user_id'];

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }
}
