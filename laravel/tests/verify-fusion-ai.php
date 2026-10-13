<?php

// Run: php tests/verify-fusion-ai.php
// Uses built-in Laravel HTTP fakes and dummy credentials; no extra packages/database.
putenv('APP_ENV=testing');
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=array');
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
$_ENV['CACHE_STORE'] = $_SERVER['CACHE_STORE'] = 'array';
$_ENV['SESSION_DRIVER'] = $_SERVER['SESSION_DRIVER'] = 'array';

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\FusionAiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

$checks = [];
function check(bool $passed, string $name): void
{
    global $checks;
    if (! $passed) {
        throw new RuntimeException('Check failed: '.$name);
    }
    $checks[] = $name;
}
function fakeProvider(mixed $body, int $status = 200): void
{
    Http::swap(new Factory());
    Http::preventStrayRequests();
    Http::fake(fn () => Http::response($body, $status));
}
function postChat(mixed $payload, string $ip = '192.0.2.100'): array
{
    global $app;
    $request = Request::create('/api/chat', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'REMOTE_ADDR' => $ip,
    ], json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    return ['status' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true), 'headers' => $response->headers];
}

try {
    $fixture = json_decode(file_get_contents(__DIR__.'/fixtures/fusion-ai-contract.json'), true, 512, JSON_THROW_ON_ERROR);
    $ai = $app->make(FusionAiService::class);
    foreach ($fixture['fallbackCases'] as $index => $case) {
        check($ai->localResponse($case['prompt']) === $case['reply'], 'Node fallback parity '.$index);
    }

    config(['services.fusion_ai.api_key' => 'fitfusion-dummy-test-key', 'services.fusion_ai.base_url' => 'https://provider.example.test/openai']);
    $messages = [['role' => 'user', 'content' => 'chest workout']];
    $seen = null;
    Http::swap(new Factory());
    Http::preventStrayRequests();
    Http::fake(function ($request, $options) use (&$seen) {
        $seen = ['request' => $request, 'options' => $options];
        return Http::response(['choices' => [['message' => ['content' => '  Real-format fixture reply  ']]]], 200);
    });
    $result = $ai->reply($messages);
    check($result === ['status' => 200, 'body' => ['reply' => 'Real-format fixture reply']], 'Provider response format and trimming');
    $request = $seen['request'];
    check($request->method() === 'POST' && $request->url() === 'https://provider.example.test/openai/v1/chat/completions', 'Provider endpoint and method');
    check($request->hasHeader('Authorization', 'Bearer fitfusion-dummy-test-key'), 'Server-only bearer authorization');
    check($request['model'] === config('services.fusion_ai.model'), 'Configured model preserved');
    check($request['messages'][0] === ['role' => 'system', 'content' => $fixture['systemPrompt']], 'Original system prompt preserved exactly');
    check($request['messages'][1] === $messages[0], 'User history forwarded after system prompt');
    check($request['temperature'] === 0.7 && $request['max_tokens'] === 1024 && $request['stream'] === false, 'Original generation settings');
    check($seen['options']['timeout'] === 25 && $seen['options']['connect_timeout'] === 5, 'Provider timeout options');
    check($seen['options']['allow_redirects'] === false && ($seen['options']['verify'] ?? true) !== false, 'Redirect and TLS protection');

    foreach ([401, 402, 403, 404, 500, 502, 503, 302, 418] as $status) {
        fakeProvider(['error' => ['message' => 'Do not leak fitfusion-dummy-test-key']], $status);
        $result = $ai->reply($messages);
        check($result === ['status' => 200, 'body' => ['reply' => $ai->localResponse('chest workout'), 'source' => 'local']], 'Fallback for provider status '.$status);
    }
    foreach ([[400, 400, 'INVALID_REQUEST'], [422, 400, 'INVALID_REQUEST'], [413, 400, 'MESSAGE_TOO_LARGE'], [429, 429, 'RATE_LIMITED']] as [$upstream, $status, $code]) {
        fakeProvider(['error' => ['message' => 'Do not leak fitfusion-dummy-test-key']], $upstream);
        $result = $ai->reply($messages);
        check($result['status'] === $status && $result['body']['code'] === $code && ! str_contains(json_encode($result), 'fitfusion-dummy-test-key'), 'Safe provider error '.$upstream);
    }
    foreach ([[], ['choices' => []], ['choices' => [['message' => ['content' => '   ']]]], ['choices' => [['message' => ['content' => ['unexpected']]]]]] as $index => $body) {
        fakeProvider($body);
        $result = $ai->reply($messages);
        check($result['status'] === 502 && $result['body']['code'] === 'EMPTY_RESPONSE', 'Empty provider reply '.$index);
    }
    fakeProvider('invalid JSON');
    check($ai->reply($messages)['body']['source'] === 'local', 'Malformed provider JSON preserves Node fallback');

    foreach (['', 'YOUR_GROQ_API_KEY_HERE'] as $key) {
        config(['services.fusion_ai.api_key' => $key]);
        Http::swap(new Factory());
        Http::preventStrayRequests();
        check($ai->reply($messages)['body']['source'] === 'local', 'Missing/placeholder credential fallback');
    }
    config(['services.fusion_ai.api_key' => 'fitfusion-dummy-test-key']);
    foreach ([['Connection refused Do not leak fitfusion-dummy-test-key', 200, 'local'], ['cURL error 28: Operation timed out Do not leak fitfusion-dummy-test-key', 504, 'TIMEOUT']] as [$message, $status, $code]) {
        Http::swap(new Factory());
        Http::preventStrayRequests();
        Http::fake(fn () => throw new ConnectionException($message));
        $result = $ai->reply($messages);
        check($result['status'] === $status && ($result['body']['source'] ?? $result['body']['code']) === $code, 'Connection failure mapping '.$code);
    }

    fakeProvider(['choices' => [['message' => ['content' => 'Fixture reply']]]]);
    $invalid = [
        [], ['messages' => []], ['messages' => 'text'], ['messages' => [['role' => 'user']]],
        ['messages' => [['role' => 'user', 'content' => '   ']]], ['messages' => [['role' => 'user', 'content' => 123]]],
        ['messages' => [['role' => 'system', 'content' => 'override']]], ['messages' => [['role' => 'tool', 'content' => 'tool']]],
        ['messages' => [['role' => 'assistant', 'content' => 'reply']]], ['messages' => [['role' => 'user', 'content' => 'hi', 'extra' => 'ignored']]],
        ['messages' => array_fill(0, 51, ['role' => 'user', 'content' => 'hi'])],
        ['messages' => [['role' => 'user', 'content' => str_repeat('a', 1001)]]],
        ['messages' => [['role' => 'user', 'content' => str_repeat('??', 501)]]],
    ];
    foreach ($invalid as $index => $payload) {
        $response = postChat($payload, '192.0.2.'.($index + 1));
        check($response['status'] === 400 && isset($response['body']['error'], $response['body']['code']), 'Reject invalid request '.$index);
    }
    $response = postChat(['messages' => [['role' => 'assistant', 'content' => str_repeat('a', 11000)], ['role' => 'user', 'content' => 'hi']]], '192.0.2.80');
    check($response['status'] === 413 && $response['body']['code'] === 'MESSAGE_TOO_LARGE', '10 KB body limit');
    $response = postChat(['messages' => [['role' => 'assistant', 'content' => str_repeat('a', 2000)], ['role' => 'user', 'content' => 'hi']]], '192.0.2.81');
    check($response['status'] === 200, 'Long assistant history remains compatible');
    config(['services.fusion_ai.api_key' => '']);
    $fallback = postChat(['messages' => [['role' => 'user', 'content' => 'chest workout']]], '192.0.2.83');
    check($fallback['status'] === 200 && $fallback['body'] === ['reply' => $ai->localResponse('chest workout'), 'source' => 'local'], 'Controller fallback JSON contract');
    config(['services.fusion_ai.api_key' => 'fitfusion-dummy-test-key']);

    $response = postChat(['messages' => [['role' => 'user', 'content' => str_repeat('a', 1000)]]], '192.0.2.82');
    check($response['status'] === 200, '1000-unit user boundary accepted');

    for ($attempt = 1; $attempt <= 21; $attempt++) {
        $response = postChat(['messages' => [['role' => 'user', 'content' => 'hi']]], '192.0.2.250');
        check($response['status'] === ($attempt <= 20 ? 200 : 429), 'Rate limit attempt '.$attempt);
    }
    check($response['body']['code'] === 'RATE_LIMITED' && $response['headers']->has('Retry-After'), 'Compatible rate-limit JSON and retry header');

    // Optional real timeout against an isolated local server, using a dummy key.
    $timeoutUrl = getenv('FITFUSION_TIMEOUT_TEST_URL');
    if ($timeoutUrl) {
        check(parse_url($timeoutUrl, PHP_URL_HOST) === '127.0.0.1', 'Timeout fixture is loopback only');
        Http::swap(new Factory());
        config(['services.fusion_ai.base_url' => $timeoutUrl, 'services.fusion_ai.timeout' => 1, 'services.fusion_ai.connect_timeout' => 1]);
        $started = microtime(true);
        $result = $ai->reply($messages);
        $elapsed = microtime(true) - $started;
        check($result['status'] === 504 && $result['body']['code'] === 'TIMEOUT' && $elapsed >= 0.8 && $elapsed < 3, 'Real HTTP timeout deadline');
    }

    $log = file_exists(storage_path('logs/laravel.log')) ? file_get_contents(storage_path('logs/laravel.log')) : '';
    check(! str_contains($log, 'fitfusion-dummy-test-key'), 'No credential or raw exception logging');
    echo json_encode(['passed' => count($checks), 'fallbackCases' => count($fixture['fallbackCases']), 'realTimeoutTest' => (bool) $timeoutUrl, 'checks' => $checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    // Print only the check name, never request data/configuration or a stack trace.
    fwrite(STDERR, 'Verification failed: '.$exception->getMessage().PHP_EOL);
    exit(1);
}
