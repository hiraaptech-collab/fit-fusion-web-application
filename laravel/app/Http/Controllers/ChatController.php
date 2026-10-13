<?php

namespace App\Http\Controllers;

use App\Services\FusionAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ChatController extends Controller
{
    public function store(Request $request, FusionAiService $ai): JsonResponse
    {
        // Match Express's 10 KB request limit without storing chat history.
        if (strlen($request->getContent()) > 10240) {
            return response()->json(['error' => 'Request body is too large. Please start a new conversation.', 'code' => 'MESSAGE_TOO_LARGE'], 413);
        }

        $validator = Validator::make($request->all(), [
            'messages' => ['required', 'array', 'list', 'min:1', 'max:50'],
            'messages.*' => ['required', 'array:role,content'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string'],
        ], [
            'messages.required' => 'Messages array is required and cannot be empty.',
            'messages.min' => 'Messages array is required and cannot be empty.',
            'messages.max' => 'Too many messages. Please start a new conversation.',
            'messages.*.content.required' => 'Message cannot be empty.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first(), 'code' => 'INVALID_REQUEST'], 400);
        }

        $messages = $validator->validated()['messages'];
        if ($messages[array_key_last($messages)]['role'] !== 'user') {
            return response()->json(['error' => 'The last message must be a user message.', 'code' => 'INVALID_REQUEST'], 400);
        }

        foreach ($messages as $message) {
            // JavaScript counts emoji as two UTF-16 units. Match its input limit.
            $length = strlen(mb_convert_encoding($message['content'], 'UTF-16LE', 'UTF-8')) / 2;
            if ($message['role'] === 'user' && $length > 1000) {
                return response()->json(['error' => 'Message too long. Please keep it under 1000 characters.', 'code' => 'MESSAGE_TOO_LARGE'], 400);
            }
        }

        try {
            $result = $ai->reply($messages);
            return response()->json($result['body'], $result['status']);
        } catch (\Illuminate\Database\QueryException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // Keep errors generic so credentials and request contents cannot reach logs or browsers.
            Log::warning('Fusion-AI request failed.', ['type' => class_basename($exception)]);
            return response()->json(['error' => 'Something went wrong. Please try again.', 'code' => 'INTERNAL_ERROR'], 500);
        }
    }
}

