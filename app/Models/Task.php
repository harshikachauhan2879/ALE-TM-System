<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['title', 'description', 'status', 'priority', 'due_date', 'creator_id', 'admin_comment'])]
class Task extends Model
{
    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }
}
