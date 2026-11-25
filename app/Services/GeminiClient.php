<?php
namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class AiUnavailable extends \RuntimeException {}

class GeminiClient
{
        public function generate(string $prompt, string $system = '', bool $json = false, bool $search = false): string
    {
        $key = config('services.gemini.key');
        if (!$key) throw new AiUnavailable('GEMINI_API_KEY is not configured.');

        $body = ['contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                 'generationConfig' => ['temperature' => 0.2]];
        if ($system) $body['systemInstruction'] = ['parts' => [['text' => $system]]];
        if ($json)   $body['generationConfig']['responseMimeType'] = 'application/json';
        if ($search) $body['tools'] = [['google_search' => new \stdClass]];

        $models = array_values(array_unique(array_filter([config('services.gemini.model'), config('services.gemini.fallback_model')])));
        $last = 'unknown error';
        foreach ($models as $m) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent";
            try {
                $res = Http::withHeaders(['x-goog-api-key' => $key])->timeout(60)->post($url, $body);
            } catch (\Throwable $e) { $last = 'network error'; continue; }

            if ($res->successful()) {
                $text = collect($res->json('candidates.0.content.parts', []))->pluck('text')->implode('');
                if (trim($text) !== '') return $text;
                $last = "empty answer ($m)"; continue;
            }
            $last = 'HTTP ' . $res->status() . " ($m)";
            if (in_array($res->status(), [400, 401, 403])) break;   // key/request problem: another model won't help
            sleep(2);                                               // busy/quota: try the next model
        }
        throw new AiUnavailable("AI service error: $last");
    }
    public function json(string $prompt, string $system = ''): array
    {
        $raw = preg_replace('/^```(?:json)?|```$/m', '', trim($this->generate($prompt, $system, json: true)));
        $data = json_decode(trim($raw), true);
        if (!is_array($data)) throw new AiUnavailable('AI returned invalid JSON.');
        return $data;
    }
    public function vision(string $prompt, string $path, string $mime): string
    {
        $key = config('services.gemini.key');
        if (!$key) throw new AiUnavailable('GEMINI_API_KEY is not configured.');
        $body = ['contents' => [['parts' => [['text' => $prompt],
                    ['inline_data' => ['mime_type' => $mime, 'data' => base64_encode(file_get_contents($path))]]]]],
                 'generationConfig' => ['temperature' => 0]];
        $m = config('services.gemini.model');
        try {
            $res = Http::withHeaders(['x-goog-api-key' => $key])->timeout(30)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent", $body);
        } catch (\Throwable $e) { throw new AiUnavailable('network error'); }
        if (!$res->successful()) throw new AiUnavailable('HTTP ' . $res->status());
        return collect($res->json('candidates.0.content.parts', []))->pluck('text')->implode('');
    }
}
