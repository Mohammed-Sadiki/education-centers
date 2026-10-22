<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $adminBerlin;

    private User $adminMunich;

    private Center $berlin;

    private Center $munich;

    protected function setUp(): void
    {
        parent::setUp();

        $this->berlin = Center::factory()->create(['name' => 'Berlin']);
        $this->munich = Center::factory()->create(['name' => 'Munich']);

        $this->superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'center_id' => null,
            'name' => 'Super Admin',
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->adminBerlin = User::factory()->create([
            'role' => User::ROLE_CENTER_ADMIN,
            'center_id' => $this->berlin->id,
            'name' => 'Admin Berlin',
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->adminMunich = User::factory()->create([
            'role' => User::ROLE_CENTER_ADMIN,
            'center_id' => $this->munich->id,
            'name' => 'Admin Munich',
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    // ───────────────────────────────────────────────────────────────────────────
    //  1. Index page accessible
    // ───────────────────────────────────────────────────────────────────────────

    public function test_super_admin_can_access_messaging_index(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertViewIs('messages.index');
    }

    public function test_center_admin_can_access_messaging_index(): void
    {
        $this->actingAs($this->adminBerlin)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertViewIs('messages.index');
    }

    public function test_teacher_cannot_access_messaging(): void
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'center_id' => $this->berlin->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($teacher)
            ->get(route('messages.index'))
            ->assertForbidden();
    }

    // ───────────────────────────────────────────────────────────────────────────
    //  2. Super Admin starts direct conversation with Admin Berlin
    // ───────────────────────────────────────────────────────────────────────────

    public function test_super_admin_can_start_direct_conversation_with_center_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('messages.start-direct', $this->adminBerlin))
            ->assertOk()
            ->assertJsonStructure(['conversation_id']);

        $conversationId = $response->json('conversation_id');
        $this->assertDatabaseHas('conversations', ['id' => $conversationId, 'type' => 'direct']);
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversationId,
            'user_id' => $this->superAdmin->id,
        ]);
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversationId,
            'user_id' => $this->adminBerlin->id,
        ]);
    }

    public function test_starting_same_direct_conversation_twice_returns_same_id(): void
    {
        $r1 = $this->actingAs($this->superAdmin)
            ->postJson(route('messages.start-direct', $this->adminBerlin))
            ->assertOk();

        $r2 = $this->actingAs($this->superAdmin)
            ->postJson(route('messages.start-direct', $this->adminBerlin))
            ->assertOk();

        $this->assertSame($r1->json('conversation_id'), $r2->json('conversation_id'));
    }

    // ───────────────────────────────────────────────────────────────────────────
    //  3. Super Admin sends a message and Admin Berlin receives it
    // ───────────────────────────────────────────────────────────────────────────

    public function test_super_admin_sends_message_to_admin_berlin(): void
    {
        $conversation = Conversation::findOrCreateDirect($this->superAdmin, $this->adminBerlin);

        $this->actingAs($this->superAdmin)
            ->postJson(route('messages.send', $conversation), ['content' => 'Bonjour Admin Berlin !'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.content', 'Bonjour Admin Berlin !');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->superAdmin->id,
            'content' => 'Bonjour Admin Berlin !',
        ]);
    }

    public function test_admin_berlin_can_read_messages_in_conversation(): void
    {
        $conversation = Conversation::findOrCreateDirect($this->superAdmin, $this->adminBerlin);

        // Super Admin sends a message
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->superAdmin->id,
            'content' => 'Message du Super Admin',
        ]);

        $this->actingAs($this->adminBerlin)
            ->getJson(route('messages.show', $conversation))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.content', 'Message du Super Admin');
    }

    // ───────────────────────────────────────────────────────────────────────────
    //  4. Admin Berlin replies to Super Admin
    // ───────────────────────────────────────────────────────────────────────────

    public function test_admin_berlin_can_reply_to_super_admin(): void
    {
        $conversation = Conversation::findOrCreateDirect($this->superAdmin, $this->adminBerlin);

        $this->actingAs($this->adminBerlin)
            ->postJson(route('messages.send', $conversation), ['content' => 'Réponse de Berlin'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.content', 'Réponse de Berlin')
            ->assertJsonPath('message.is_mine', true);
    }

    // ───────────────────────────────────────────────────────────────────────────
    //  5. Super Admin receives reply (sees messages from Berlin)
    // ───────────────────────────────────────────────────────────────────────────

    public function test_super_admin_can_see_reply_from_admin_berlin(): void
    {
        $conversation = Conversation::findOrCreateDirect($this->superAdmin, $this->adminBerlin);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->adminBerlin->id,
            'content' => 'Réponse de Berlin',
        ]);

        $this->actingAs($this->superAdmin)
            ->getJson(route('messages.show', $conversation))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.content', 'Réponse de Berlin')
            ->assertJsonPath('messages.0.is_mine', false);
    }

    // ───────────────────────────────────────────────────────────────────────────
    //  6. Admin Munich CANNOT access Berlin conversation (multi-center isolation)
    // ───────────────────────────────────────────────────────────────────────────

    public function test_admin_munich_cannot_access_berlin_conversation(): void
    {
        $conversation = Conversation::findOrCreateDirect($this->superAdmin, $this->adminBerlin);

        // Admin Munich should get 403 since they are not a participant
        $this->actingAs($this->adminMunich)
            ->getJson(route('messages.show', $conversation))
            ->assertForbidden();
    }

    public function test_admin_munich_cannot_send_message_to_berlin_conversation(): void
    {
        $conversation = Conversation::findOrCreateDirect($this->superAdmin, $this->adminBerlin);

        $this->actingAs($this->adminMunich)
            ->postJson(route('messages.send', $conversation), ['content' => 'Intrusion'])
            ->assertForbidden();
    }

    public function test_center_admin_cannot_start_conversation_with_another_center_admin(): void
    {
        // Berlin Admin should not be able to start conversation with Munich Admin
        $this->actingAs($this->adminBerlin)
            ->postJson(route('messages.start-direct', $this->adminMunich))
            ->assertUnprocessable();
    }

    // ───────────────────────────────────────────────────────────────────────────
    //  7. Unread count increments and decrements appropriately
    // ───────────────────────────────────────────────────────────────────────────

    public function test_unread_count_increments_when_message_received(): void
    {
        $conversation = Conversation::findOrCreateDirect($this->superAdmin, $this->adminBerlin);

        // Initially no unread for Admin Berlin
        $this->actingAs($this->adminBerlin)
            ->getJson(route('messages.unread-count'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        // Super Admin sends a message
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->superAdmin->id,
            'content' => 'Message non lu',
        ]);

        // Now Admin Berlin should have 1 unread
        $this->actingAs($this->adminBerlin)
            ->getJson(route('messages.unread-count'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    public function test_unread_count_decrements_when_messages_read(): void
    {
        $conversation = Conversation::findOrCreateDirect($this->superAdmin, $this->adminBerlin);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->superAdmin->id,
            'content' => 'Message non lu',
        ]);

        // Admin Berlin reads the conversation → marks as read
        $this->actingAs($this->adminBerlin)
            ->getJson(route('messages.show', $conversation))
            ->assertOk();

        // Unread count should now be 0
        $this->actingAs($this->adminBerlin)
            ->getJson(route('messages.unread-count'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    // ───────────────────────────────────────────────────────────────────────────
    //  8. Conversation list returns correct structure
    // ───────────────────────────────────────────────────────────────────────────

    public function test_conversations_list_returns_correct_structure(): void
    {
        Conversation::findOrCreateDirect($this->superAdmin, $this->adminBerlin);

        $this->actingAs($this->superAdmin)
            ->getJson(route('messages.conversations'))
            ->assertOk()
            ->assertJsonStructure([
                'conversations' => [['id', 'other_user', 'last_message', 'unread_count']],
                'total_unread',
            ]);
    }

    // ───────────────────────────────────────────────────────────────────────────
    //  9. Message without content requires a file
    // ───────────────────────────────────────────────────────────────────────────

    public function test_sending_message_with_no_content_and_no_file_is_rejected(): void
    {
        $conversation = Conversation::findOrCreateDirect($this->superAdmin, $this->adminBerlin);

        $this->actingAs($this->superAdmin)
            ->postJson(route('messages.send', $conversation), ['content' => ''])
            ->assertUnprocessable();
    }

    // ───────────────────────────────────────────────────────────────────────────
    //  10. Unauthenticated users cannot access messaging
    // ───────────────────────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_messaging(): void
    {
        $this->get(route('messages.index'))
            ->assertRedirect(route('login'));
    }
}
