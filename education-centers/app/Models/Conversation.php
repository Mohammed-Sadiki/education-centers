<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'center_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function isParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Get the other user in a direct 1-to-1 conversation
     */
    public function getOtherParticipant(User $currentUser): ?User
    {
        $participant = $this->participants()
            ->where('user_id', '!=', $currentUser->id)
            ->with('user.center')
            ->first();

        return $participant ? $participant->user : null;
    }

    /**
     * Get unread count for a specific user
     */
    public function unreadCountFor(User $user): int
    {
        $participant = $this->participants()->where('user_id', $user->id)->first();
        if (! $participant) {
            return 0;
        }

        $query = $this->messages()->where('sender_id', '!=', $user->id);
        if ($participant->last_read_at) {
            $query->where('created_at', '>', $participant->last_read_at);
        }

        return $query->count();
    }

    /**
     * Find or create a direct conversation between two users
     */
    public static function findOrCreateDirect(User $userA, User $userB): Conversation
    {
        $conversation = static::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userA->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userB->id))
            ->first();

        if ($conversation) {
            return $conversation;
        }

        $centerId = $userA->center_id ?: $userB->center_id;

        $conversation = static::create([
            'type' => 'direct',
            'center_id' => $centerId,
            'last_message_at' => now(),
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $userA->id,
            'last_read_at' => now(),
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $userB->id,
            'last_read_at' => null,
        ]);

        return $conversation;
    }
}
