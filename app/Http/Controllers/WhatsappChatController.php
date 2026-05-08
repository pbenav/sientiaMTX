<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsappMessage;
use App\Models\Team;

class WhatsappChatController extends Controller
{
    /**
     * Enviar un mensaje desde la web al grupo de WhatsApp del equipo.
     */
     public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string|max:10000',
            'photo' => 'nullable|image|max:5120', // Max 5MB
            'voice' => 'nullable|mimes:ogg,webm,mp3,wav|max:5120', // Max 5MB
            'team_id' => 'required|exists:teams,id',
            'reply_to_id' => 'nullable|exists:whatsapp_messages,id',
        ]);

        $user = auth()->user();

        $team = Team::findOrFail($request->team_id);
        $text = $request->input('message') ?? '';
        $photo = $request->file('photo');
        $voice = $request->file('voice');
        
        $chatId = $team->whatsapp_chat_id;

        if (!$chatId) {
            return response()->json([
                'reply' => '⚠️ Este equipo no tiene un chat de WhatsApp vinculado.'
            ]);
        }

        try {
            $photoPath = null;
            $voicePath = null;
            $fileType = 'text';
            $fileSize = 0;

            if ($photo) {
                $fileSize = $photo->getSize();
                if (!$team->hasAvailableQuota($fileSize)) {
                    return response()->json(['reply' => '⚠️ Cuota de almacenamiento de equipo agotada.'], 400);
                }
                $photoPath = $photo->store('whatsapp/photos', 'public');
                $fileType = 'photo';
            } elseif ($voice) {
                $fileSize = $voice->getSize();
                if (!$team->hasAvailableQuota($fileSize)) {
                    return response()->json(['reply' => '⚠️ Cuota de almacenamiento de equipo agotada.'], 400);
                }
                $voicePath = $voice->store('whatsapp/voice', 'public');
                $fileType = 'voice';
            } elseif (!$text) {
                return response()->json(['error' => 'Mensaje vacío'], 422);
            }

            // Guardamos el mensaje en nuestra DB local
            $localMsg = WhatsappMessage::create([
                'team_id' => $team->id,
                'from_me' => true,
                'author' => $user->name,
                'text' => $text,
                'file_type' => $fileType,
                'photo_path' => $photoPath,
                'voice_path' => $voicePath,
                'file_size' => $fileSize,
                'reply_to_id' => $request->reply_to_id ? WhatsappMessage::find($request->reply_to_id)?->message_id : null,
                'reply_to_text' => $request->reply_to_id ? WhatsappMessage::find($request->reply_to_id)?->text : null,
            ]);

            // Formateamos el pie del mensaje
            $caption = "💬 *[{$user->name}]:*\n{$text}";
            
            $params = [
                'session' => 'team_' . $team->id,
                'phone' => $chatId,
                'message' => $caption,
            ];

            // Si hay multimedia, la convertimos a base64 para el bridge
            if ($photo) {
                $params['mediaBase64'] = base64_encode(file_get_contents($photo->getRealPath()));
                $params['mediaMimetype'] = $photo->getMimeType();
                $params['mediaFilename'] = $photo->getClientOriginalName();
            } elseif ($voice) {
                $params['mediaBase64'] = base64_encode(file_get_contents($voice->getRealPath()));
                $params['mediaMimetype'] = $voice->getMimeType();
                $params['mediaFilename'] = $voice->getClientOriginalName();
            }

            $params['webhook_url'] = route('whatsapp.webhook');

            // Llamada al bridge de NodeJS
            $response = Http::post("http://localhost:3001/api/send", $params);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['message_id'])) {
                    $localMsg->update(['message_id' => $data['message_id']]);
                }

                return response()->json([
                    'success' => true,
                    'message' => [
                        'id' => $localMsg->id,
                        'text' => $localMsg->text,
                        'author' => $localMsg->author,
                        'from_me' => true,
                        'time' => $localMsg->created_at->format('H:i'),
                        'photo' => $localMsg->photo_path ? asset('storage/' . $localMsg->photo_path) : null,
                        'voice' => $localMsg->voice_path ? asset('storage/' . $localMsg->voice_path) : null,
                        'sticker' => null,
                        'file_type' => $localMsg->file_type,
                    ]
                ]);
            }

            Log::error("Error de WhatsApp Bridge: " . $response->body());
            return response()->json([
                'reply' => '😕 No he podido enviar el mensaje a WhatsApp.'
            ]);

        } catch (\Exception $e) {
            Log::error("Error en WhatsappChatController@sendMessage: " . $e->getMessage());
            return response()->json([
                'reply' => '💥 Error técnico al enviar el mensaje.'
            ]);
        }
    }

    /**
     * Obtener el historial de mensajería de un equipo (paginado).
     */
    public function getMessages(Request $request)
    {
        $user = auth()->user();

        $teamId = $request->query('team_id');
        $beforeId = $request->query('before_id');
        
        if (!$teamId) {
            return response()->json(['messages' => []]);
        }

        $query = WhatsappMessage::where('team_id', $teamId)
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->take(25)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'text' => $msg->text,
                    'author' => $msg->author,
                    'from_me' => $msg->from_me,
                    'time' => $msg->created_at->format('H:i'),
                    'photo' => $msg->photo_path ? asset('storage/' . $msg->photo_path) : null,
                    'voice' => $msg->voice_path ? asset('storage/' . $msg->voice_path) : null,
                    'sticker' => $msg->sticker_path ? asset('storage/' . $msg->sticker_path) : null,
                    'file_type' => $msg->file_type,
                    'reply_to_text' => $msg->reply_to_text,
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    /**
     * Editar un mensaje (Si la API de WA lo soportase; lo dejamos por compatibilidad local)
     */
    public function update(Request $request, WhatsappMessage $message)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $text = $request->input('message');
        
        try {
            $message->update(['text' => $text]);
            // Omitimos la llamada externa si WA no permite edición de mensajes fácilmente
            return response()->json([
                'success' => true,
                'message' => $text
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al editar el mensaje'], 500);
        }
    }

    /**
     * Eliminar un mensaje localmente.
     */
    public function destroy(WhatsappMessage $message)
    {
        // En WhatsApp es complicado eliminar un mensaje para todos vía API sin el ID exacto, 
        // lo eliminamos localmente.
        $message->update(['is_deleted' => true]);
        
        return response()->json(['success' => true]);
    }
}
