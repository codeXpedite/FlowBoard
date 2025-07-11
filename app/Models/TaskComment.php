<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComment extends Model
{
    protected $fillable = [
        'content',
        'task_id',
        'user_id',
        'mentions',
    ];

    protected $casts = [
        'mentions' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedContentAttribute(): string
    {
        $content = $this->content;
        
        // Convert @mentions to clickable links
        if ($this->mentions) {
            foreach ($this->mentions as $mention) {
                $content = str_replace(
                    "@{$mention['username']}", 
                    "<span class='text-blue-600 font-medium'>@{$mention['username']}</span>", 
                    $content
                );
            }
        }
        
        return $content;
    }
}
