<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FusionAiService
{
    // Return an HTTP status and the original chatbot's JSON response body.
    private array $usage = [];

    public function reply(array $messages): array
    {
        $lastMessage=$messages[array_key_last($messages)]['content'];
        if(app('request')->attributes->get('fusion_ai_provider_allowed')===false){
            if(config('services.fusion_ai.require_provider'))return $this->error('AI_LOGIN_REQUIRED','Please sign in to use Fusion-AI.',403);
            return $this->localReply($lastMessage);
        }
        $key=(string)config('services.fusion_ai.api_key');
        if($key===''||$key==='YOUR_GROQ_API_KEY_HERE')return $this->localReply($lastMessage);
        // Keep saved history intact, but send a bounded recent context to Groq.
        $maxBytes=max(4000,(int)config('services.fusion_ai.max_input_bytes'));
        while(count($messages)>1&&array_sum(array_map(fn($message)=>strlen($message['content']),$messages))>$maxBytes)array_shift($messages);
        while(count($messages)>1&&$messages[0]['role']==='assistant')array_shift($messages);
        $monitor=app(AiUsageService::class);
        $reservation=$monitor->reserve($messages);
        if(isset($reservation['error']))return $reservation['error'];
        $result=null;$this->usage=[];
        try{$result=$this->providerReply($messages);return $result;}
        finally{$monitor->complete($reservation,$result,$this->usage);}
    }

    private function providerReply(array $messages): array
    {
        $lastMessage = $messages[array_key_last($messages)]['content'];
        $key = (string) config('services.fusion_ai.api_key');

        if ($key === '' || $key === 'YOUR_GROQ_API_KEY_HERE') {
            return $this->localReply($lastMessage);
        }

        try {
            $response = $this->requestProvider($messages);
        } catch (ConnectionException $exception) {
            $message = strtolower($exception->getMessage());
            $timedOut = str_contains($message, 'curl error 28') || str_contains($message, 'timed out');
            // Never log exception text, authorization headers, prompts or replies.
            Log::warning('Fusion-AI connection failed.', ['reason' => $timedOut ? 'TIMEOUT' : 'NETWORK_ERROR']);

            return $timedOut
                ? $this->error('TIMEOUT', 'The request timed out. Please try again.', 504)
                : $this->localReply($lastMessage);
        }

        if (! $response->successful()) {
            return $this->providerError($response->status(), $lastMessage);
        }

        if(strlen($response->body())>65536)return $this->error('EMPTY_RESPONSE','The AI service returned an unsupported response. Please try again.',502);
        $data = json_decode($response->body(), true);
        if (json_last_error() !== JSON_ERROR_NONE || $data === null) {
            // Node falls back when the provider's JSON cannot be decoded.
            return $this->localReply($lastMessage);
        }
        $this->usage=is_array($data['usage']??null)?$data['usage']:[];
        $content = data_get($data, 'choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            return $this->error('EMPTY_RESPONSE', 'The AI service returned an empty response.', 502);
        }

        return ['status' => 200, 'body' => ['reply' => trim($content)]];
    }

    private function requestProvider(array $messages): Response
    {
        $settings = config('services.fusion_ai');
        $systemPrompt = file_get_contents(resource_path('prompts/fusion-ai.txt'));

        return Http::acceptJson()
            ->withToken($settings['api_key'])
            ->connectTimeout($settings['connect_timeout'])
            ->timeout($settings['timeout'])
            // Do not forward the API key through redirects; TLS verification stays enabled.
            ->withOptions(['allow_redirects' => false])
            ->post(rtrim($settings['base_url'], '/').'/v1/chat/completions', [
                'model' => $settings['model'],
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ...$messages,
                ],
                'temperature' => 0.7,
                'max_tokens' => 1024,
                'stream' => false,
            ]);
    }

    private function providerError(int $status, string $lastMessage): array
    {
        // The provider's raw error body may contain sensitive information.
        Log::warning('Fusion-AI provider request failed.', ['status' => $status]);

        return match ($status) {
            400, 422 => $this->error('INVALID_REQUEST', 'The request to the AI service was invalid.', 400),
            413 => $this->error('MESSAGE_TOO_LARGE', 'The message is too long for the AI service.', 400),
            429 => $this->error('RATE_LIMITED', 'Too many requests. Please wait a moment and try again.', 429),
            // Preserve Node's fallback for authentication, quota, unavailable and other errors.
            default => $this->localReply($lastMessage),
        };
    }

    private function localReply(string $message): array
    {
        if(config('services.fusion_ai.require_provider'))return $this->error('AI_UNAVAILABLE','Fusion-AI is temporarily unavailable. Please try again later.',503);
        return ['status' => 200, 'body' => ['reply' => $this->localResponse($message), 'source' => 'local']];
    }

    public function localResponse(string $message): string
    {
        // These strings and their matching order are copied exactly from server.js.
        $replies = json_decode(file_get_contents(resource_path('data/fusion-ai-fallbacks.json')), true, 512, JSON_THROW_ON_ERROR);
        $question = mb_strtolower($message, 'UTF-8');

        if (preg_match('/^(hi|hello|hey|assalam|salam|howdy|sup|yo)\b/i', $question)) {
            return $replies['greeting'];
        }
        if (preg_match('/^(bye|goodbye|thanks|thank you|ok|okay)\b/i', $question)) {
            return $replies['goodbye'];
        }

        foreach ($replies['responses'] as $response) {
            foreach ($response['keys'] as $keyword) {
                if (str_contains($question, $keyword)) {
                    return $response['reply'];
                }
            }
        }

        return $replies['default'];
    }

    private function error(string $code, string $message, int $status): array
    {
        return ['status' => $status, 'body' => ['error' => $message, 'code' => $code]];
    }
}


