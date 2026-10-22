<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * Display the main messaging interface
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Ensure only Super Admin and Center Admins can access
        if (! $user->isSuperAdmin() && ! $user->isCenterAdmin()) {
            abort(403, __('Accès non autorisé au module de messagerie.'));
        }

        // Available contacts for initiating conversations:
        // - If Super Admin: all active Center Admins grouped or sorted with their Center
        // - If Center Admin: all active Super Admins
        if ($user->isSuperAdmin()) {
            $contacts = User::where('role', User::ROLE_CENTER_ADMIN)
                ->where('status', User::STATUS_ACTIVE)
                ->with('center')
                ->orderBy('name')
                ->get();
        } else {
            $contacts = User::where('role', User::ROLE_SUPER_ADMIN)
                ->where('status', User::STATUS_ACTIVE)
                ->orderBy('name')
                ->get();
        }

        // Active conversation if specified in query param
        $activeConversationId = $request->query('conversation_id');

        return view('messages.index', compact('user', 'contacts', 'activeConversationId'));
    }

    /**
     * Get JSON list of conversations for current user
     */
    public function getConversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->when($user->isCenterAdmin(), function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->whereNull('center_id')->orWhere('center_id', $user->center_id);
                });
            })
            ->with([
                'participants.user.center',
                'latestMessage.attachments',
                'center',
            ])
            ->orderBy('updated_at', 'desc')
            ->get();

        $data = $conversations->map(function ($conv) use ($user) {
            $other = $conv->getOtherParticipant($user);
            $unreadCount = $conv->unreadCountFor($user);
            $latest = $conv->latestMessage;

            return [
                'id' => $conv->id,
                'other_user' => [
                    'id' => $other?->id,
                    'name' => $other?->name ?? 'Utilisateur',
                    'role' => $other?->isSuperAdmin() ? __('Super Admin') : __('Admin Centre'),
                    'center_name' => $other?->center?->name ?? ($conv->center?->name ?? __('Direction Générale')),
                    'email' => $other?->email,
                ],
                'last_message' => $latest ? [
                    'content' => $latest->content ?: ($latest->attachments->count() ? '📎 Fichier joint' : ''),
                    'time' => $latest->created_at->locale('fr')->diffForHumans(null, true),
                    'created_at' => $latest->created_at->toIso8601String(),
                    'is_mine' => $latest->sender_id === $user->id,
                ] : null,
                'unread_count' => $unreadCount,
                'updated_at' => $conv->updated_at->toIso8601String(),
            ];
        });

        return response()->json([
            'conversations' => $data,
            'total_unread' => $user->totalUnreadMessagesCount(),
        ]);
    }

    /**
     * Start or open direct conversation with a target user
     */
    public function startDirect(Request $request, User $target): JsonResponse
    {
        $currentUser = $request->user();

        // Validation of allowed pairs:
        // Super Admin <-> Center Admin
        if ($currentUser->isSuperAdmin() && ! $target->isCenterAdmin()) {
            return response()->json(['error' => __('Le Super Admin ne peut échanger qu\'avec des Admins de centre.')], 422);
        }

        if ($currentUser->isCenterAdmin() && ! $target->isSuperAdmin()) {
            return response()->json(['error' => __('Un Admin de centre ne peut échanger qu\'avec le Super Admin.')], 422);
        }

        $conversation = Conversation::findOrCreateDirect($currentUser, $target);

        return response()->json([
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Get messages for a specific conversation
     */
    public function getMessages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        // Policy check
        if (! $conversation->isParticipant($user)) {
            abort(403, __('Accès interdit à cette conversation.'));
        }

        if ($user->isCenterAdmin() && $conversation->center_id && $conversation->center_id !== $user->center_id) {
            abort(403, __('Accès interdit aux conversations d\'un autre centre.'));
        }

        // Mark as read for this user
        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        $messages = $conversation->messages()
            ->with(['sender', 'attachments'])
            ->get();

        $other = $conversation->getOtherParticipant($user);

        $formatted = $messages->map(function ($msg) use ($user) {
            return [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'sender_name' => $msg->sender->name,
                'is_mine' => $msg->sender_id === $user->id,
                'content' => $msg->content,
                'time' => $msg->created_at->format('H:i'),
                'date' => $msg->created_at->locale('fr')->isoFormat('D MMMM YYYY'),
                'created_at' => $msg->created_at->toIso8601String(),
                'attachments' => $msg->attachments->map(function ($att) {
                    return [
                        'id' => $att->id,
                        'name' => $att->original_name,
                        'size' => $att->formatted_size,
                        'is_image' => $att->isImage(),
                        'url' => route('messages.attachment', $att->id),
                    ];
                }),
            ];
        });

        return response()->json([
            'conversation_id' => $conversation->id,
            'other_user' => [
                'id' => $other?->id,
                'name' => $other?->name ?? 'Utilisateur',
                'role' => $other?->isSuperAdmin() ? __('Super Admin') : __('Admin Centre'),
                'center_name' => $other?->center?->name ?? 'Direction',
                'email' => $other?->email,
            ],
            'messages' => $formatted,
            'total_unread' => $user->totalUnreadMessagesCount(),
        ]);
    }

    /**
     * Send a message in a conversation
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        // Policy check
        if (! $conversation->isParticipant($user)) {
            abort(403, __('Accès interdit à cette conversation.'));
        }

        if ($user->isCenterAdmin() && $conversation->center_id && $conversation->center_id !== $user->center_id) {
            abort(403, __('Accès interdit aux conversations d\'un autre centre.'));
        }

        $request->validate([
            'content' => 'nullable|string|max:5000',
            'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,webp', // 10MB max
        ]);

        if (empty($request->input('content')) && ! $request->hasFile('file')) {
            return response()->json(['error' => __('Le message ou un fichier est requis.')], 422);
        }

        DB::beginTransaction();
        try {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'content' => $request->input('content'),
            ]);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('messages/'.$conversation->id, 'local');

                MessageAttachment::create([
                    'message_id' => $message->id,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }

            // Update conversation last_message_at
            $conversation->update([
                'last_message_at' => now(),
            ]);

            // Mark read for sender
            ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->update(['last_read_at' => now()]);

            DB::commit();

            $message->load('attachments', 'sender');

            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->name,
                    'is_mine' => true,
                    'content' => $message->content,
                    'time' => $message->created_at->format('H:i'),
                    'date' => $message->created_at->locale('fr')->isoFormat('D MMMM YYYY'),
                    'created_at' => $message->created_at->toIso8601String(),
                    'attachments' => $message->attachments->map(function ($att) {
                        return [
                            'id' => $att->id,
                            'name' => $att->original_name,
                            'size' => $att->formatted_size,
                            'is_image' => $att->isImage(),
                            'url' => route('messages.attachment', $att->id),
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => __('Erreur lors de l\'envoi du message: :message', ['message' => $e->getMessage()])], 500);
        }
    }

    /**
     * Get global unread message count for topbar & sidebar badge
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->totalUnreadMessagesCount(),
        ]);
    }

    /**
     * Secure download of message attachment
     */
    public function downloadAttachment(Request $request, MessageAttachment $attachment)
    {
        $user = $request->user();
        $conversation = $attachment->message->conversation;

        // Policy checks: only authorized participants of this conversation can download
        if (! $conversation->isParticipant($user)) {
            abort(403, __('Vous n\'êtes pas autorisé à accéder à cette pièce jointe.'));
        }

        if ($user->isCenterAdmin() && $conversation->center_id && $conversation->center_id !== $user->center_id) {
            abort(403, __('Accès non autorisé.'));
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($attachment->file_path)) {
            abort(404, __('Fichier introuvable.'));
        }

        return $disk->download($attachment->file_path, $attachment->original_name);
    }
}
