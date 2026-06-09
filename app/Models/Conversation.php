<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $table = 'agent_conversations';
    
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'id',
        'user_id',
        'title',
    ];
    
    protected $casts = [
        'id' => 'string',
        'user_id' => 'integer',
    ];
    
    /**
     * Get all messages in this conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class, 'conversation_id', 'id');
    }
    
    /**
     * Get the user who owns this conversation
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the last message in the conversation
     */
    public function lastMessage()
    {
        return $this->messages()->orderBy('created_at', 'desc')->first();
    }
    
    /**
     * Get message count
     */
    public function getMessageCountAttribute(): int
    {
        return $this->messages()->count();
    }
}