<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ChatBotAgent;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ChatBotAgentController extends Controller
{
    public function chat(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string|min:1|max:500',
                'session_id' => 'nullable|string'
            ]);
            
            $sessionId = $request->input('session_id', $this->generateSessionId());
            
            $attachments = [];
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $attachments = [$request->file('photo')];
            }
            
            $inventory = $this->getInventory();
            
            $history = $this->getHistoryFromCache($sessionId);
            
            $conversationContext = $this->buildContext($history);
            
            $agent = new ChatBotAgent();
            $enhancedMessage = $this->buildEnhancedMessage($inventory, $conversationContext, $request->input('message'));
            
            $response = $agent->prompt($enhancedMessage, attachments: $attachments);
            
            $this->saveToHistory($sessionId, $history, $request->input('message'), $response);
            
            return response()->json([
                'success' => true,
                'response' => $response,
                'session_id' => $sessionId
            ]);
            
        } catch (\Exception $e) {
            Log::error('Chat error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'response' => 'Sorry, I encountered an error. Please try again.'
            ], 500);
        }
    }
    
    private function generateSessionId(): string
    {
        return uniqid('chat_', true);
    }
    
    private function getHistoryFromCache(string $sessionId): array
    {
        $key = "chat_history_{$sessionId}";
        return Cache::get($key, []);
    }
    
    private function buildContext(array $history): string
    {
        if (empty($history)) {
            return "No previous conversation. This is the first message.";
        }
        
        $context = "=== CONVERSATION HISTORY ===\n";
        foreach ($history as $index => $exchange) {
            $context .= "Customer: {$exchange['user']}\n";
            $context .= "Assistant: {$exchange['assistant']}\n\n";
        }
        $context .= "=== END OF HISTORY ===\n";
        $context .= "DO NOT say 'fresh conversation' - you CAN see the history above.\n";
        
        return $context;
    }
    
    private function buildEnhancedMessage(string $inventory, string $context, string $userMessage): string
    {
        return "{$inventory}\n\n{$context}\n\nCurrent message: \"{$userMessage}\"\n\nRespond naturally.";
    }
    
    private function getInventory(): string
    {
        $products = Product::all();
        $inventory = "=== CURRENT INVENTORY ===\n";
        
        foreach ($products as $product) {
            $status = $product->quantity_available > 0 ? "IN STOCK ✅" : "OUT OF STOCK ❌";
            $inventory .= "- {$product->name}: {$status}, {$product->quantity_available} units, ₱{$product->price}\n";
        }
        
        return $inventory; 
    }
    
    private function saveToHistory(string $sessionId, array &$history, string $userMessage, string $aiResponse): void
    {
        $key = "chat_history_{$sessionId}";
        
        $history[] = [
            'user' => $userMessage,
            'assistant' => $aiResponse,
            'timestamp' => now()->toDateTimeString()
        ];
        
        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }
        
        Cache::put($key, $history, 86400);
    }
}