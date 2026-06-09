<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ChatBotAgent;
use App\Models\Product;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatBotAgentController extends Controller
{
    /**
     * Show the chatbot interface
     */
    public function index()
    {
        return view('chatbot.index');
    }

    public function chat(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string|min:1|max:500',
                'conversation_id' => 'nullable|string'
            ]);
            
            // Handle attachments
            $attachments = [];
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $attachments = [$request->file('photo')];
            }
            
            // Get or create conversation
            $conversation = $this->getOrCreateConversation($request);
            
            // Get real-time inventory
            $inventory = $this->getInventory(); 
            
            // Get previous messages from database
            $previousMessages = $this->getPreviousMessages($conversation);
            
            // Build conversation context from database messages
            $conversationContext = $this->buildContextFromMessages($previousMessages);
            
            // Build enhanced message with inventory and context
            $enhancedMessage = $this->buildEnhancedMessage($inventory, $conversationContext, $request->input('message'));
            
            // Save user message to database
            $this->saveUserMessage($conversation, $request->input('message'), $attachments);
            
            // Get AI response
            $agent = new ChatBotAgent();
            $response = $agent->prompt($enhancedMessage, attachments: $attachments);
            
            // Save AI response to database
            $this->saveAssistantMessage($conversation, $response);
            
            return response()->json([
                'success' => true,
                'response' => $response,
                'conversation_id' => $conversation->id,
                'message_count' => $conversation->messages()->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Chat error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'response' => 'Sorry, I encountered an error. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Get or create conversation from database
     */
    private function getOrCreateConversation(Request $request): Conversation
    {
        $conversationId = $request->input('conversation_id');
        
        if ($conversationId) {
            // Try to find existing conversation
            $conversation = Conversation::find($conversationId);
            if ($conversation) {
                return $conversation;
            }
        }
        
        // Create new conversation
        return Conversation::create([
            'id' => (string) Str::uuid(),
            'user_id' => auth()->id(),
            'title' => 'New Conversation',
        ]);
    }
    
    /**
     * Get previous messages from database
     */
    private function getPreviousMessages(Conversation $conversation): array
    {
        $messages = ConversationMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'asc')
            ->get();
        
        $formattedMessages = [];
        foreach ($messages as $message) {
            $formattedMessages[] = [
                'role' => $message->role,
                'content' => $message->content,
                'created_at' => $message->created_at->toDateTimeString()
            ];
        }
        
        return $formattedMessages;
    }
    
    /**
     * Build context from database messages
     */
    private function buildContextFromMessages(array $messages): string
    {
        if (empty($messages)) {
            return "No previous messages. This is the first message in this conversation.";
        }
        
        $context = "=== PREVIOUS MESSAGES FROM DATABASE ===\n";
        $context .= "Here is the complete conversation history (oldest to newest):\n\n";
        
        foreach ($messages as $index => $msg) {
            $turnNumber = $index + 1;
            $role = $msg['role'] === 'user' ? 'Customer' : 'Assistant';
            $context .= "Message {$turnNumber} ({$role}):\n";
            $context .= "{$msg['content']}\n\n";
        }
        
        $context .= "=== END OF MESSAGE HISTORY ===\n";
        $context .= "Continue the conversation naturally based on the history above.\n";
        $context .= "DO NOT say 'fresh conversation' - you can see all previous messages.\n";
        
        return $context;
    }
    
    /**
     * Save user message to database
     */
    private function saveUserMessage(Conversation $conversation, string $message, array $attachments)
    {
        ConversationMessage::create([
            'id' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
            'agent' => ChatBotAgent::class,
            'role' => 'user',
            'content' => $message,
            'attachments' => json_encode($attachments),
            'tool_calls' => json_encode([]),
            'tool_results' => json_encode([]),
            'usage' => json_encode([]),
            'meta' => json_encode([])
        ]);
        
        // Update conversation title with first message
        $messageCount = ConversationMessage::where('conversation_id', $conversation->id)->count();
        if ($messageCount === 1) {
            $title = substr($message, 0, 50) . (strlen($message) > 50 ? '...' : '');
            $conversation->update(['title' => $title]);
        }
        
        // Update timestamp
        $conversation->update(['updated_at' => now()]);
    }
    
    /**
     * Save assistant message to database
     */
    private function saveAssistantMessage(Conversation $conversation, string $response)
    {
        ConversationMessage::create([
            'id' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
            'agent' => ChatBotAgent::class,
            'role' => 'assistant',
            'content' => $response,
            'attachments' => json_encode([]),
            'tool_calls' => json_encode([]),
            'tool_results' => json_encode([]),
            'usage' => json_encode([]),
            'meta' => json_encode([])
        ]);
        
        // Update timestamp
        $conversation->update(['updated_at' => now()]);
    }
    
    /**
     * Build enhanced message with inventory and context
     */
    private function buildEnhancedMessage(string $inventory, string $context, string $userMessage): string
    {
        return "{$inventory}\n\n{$context}\n\nCurrent customer message: \"{$userMessage}\"\n\nRespond naturally based on the inventory and conversation history above.";
    }
    
    /**
     * Get current inventory from database
     */
    private function getInventory(): string
    {
        $products = Product::all();
        $inventory = "=== CURRENT INVENTORY (REAL-TIME DATA) ===\n";
        
        foreach ($products as $product) {
            $status = $product->quantity_available > 0 ? "IN STOCK ✅" : "OUT OF STOCK ❌";
            $inventory .= "- {$product->name}: {$status}, {$product->quantity_available} units, ₱{$product->price}\n";
        }
        
        $inventory .= "\n=== END INVENTORY ===\n";
        
        return $inventory;
    }
    
    /**
     * Get conversation history (for debugging)
     */
    public function getConversation(Request $request)
    {
        $conversationId = $request->input('conversation_id');
        
        if (!$conversationId) {
            return response()->json(['error' => 'conversation_id required'], 400);
        }
        
        $conversation = Conversation::with('messages')->find($conversationId);
        
        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }
        
        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'created_at' => $conversation->created_at,
                'updated_at' => $conversation->updated_at,
            ],
            'messages' => $conversation->messages->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content,
                    'created_at' => $message->created_at,
                ];
            }),
            'total_messages' => $conversation->messages->count()
        ]);
    }
    
    /**
     * List all conversations for the user
     */
    public function listConversations(Request $request)
    {
        $conversations = Conversation::where('user_id', auth()->id())
            ->orWhereNull('user_id')
            ->orderBy('updated_at', 'desc')
            ->get();
        
        return response()->json([
            'conversations' => $conversations->map(function ($conv) {
                return [
                    'id' => $conv->id,
                    'title' => $conv->title,
                    'message_count' => $conv->messages()->count(),
                    'last_updated' => $conv->updated_at,
                ];
            })
        ]);
    }
    
    /**
     * Delete a conversation
     */
    public function deleteConversation(Request $request)
    {
        $conversationId = $request->input('conversation_id');
        
        if (!$conversationId) {
            return response()->json(['error' => 'conversation_id required'], 400);
        }
        
        $conversation = Conversation::find($conversationId);
        
        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }
        
        // Delete all messages first (cascade should handle this)
        $conversation->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted'
        ]);
    }
}