<?php

namespace App\Http\Controllers;

use App\Contracts\AiAssistantInterface;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function ask(Request $request, AiAssistantInterface $aiAssistant)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'team_id' => 'nullable|integer|exists:teams,id'
        ]);

        $aiAssistant->forUser($request->user(), $request->team_id);
        
        $response = $aiAssistant->generateText($request->prompt);

        return response()->json([
            'message' => $response
        ]);
    }
}
