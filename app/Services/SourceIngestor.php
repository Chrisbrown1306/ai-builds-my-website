<?php
namespace App\Services;

use App\Models\Project;
use App\Models\Source;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class SourceIngestor
{
    const BLOCKED = ['instagram.com', 'facebook.com', 'fb.com', 'linkedin.com', 'x.com', 'twitter.com', 'tiktok.com', 'maps.app.goo.gl', 'g.page'];
    const INJECTION = '/(ignore|disregard|forget)\s+(all\s+|any\s+|the\s+)?(previous|prior|above|earlier)|system prompt|you are now|new instructions|do not (tell|inform) the user|reveal your/i';

    public function __construct(private GeminiClient $ai) {}

    public function addFile(Project $p, UploadedFile $f): Source
    {
        $ext = strtolower($f->getClientOriginalExtension());
        $label = $f->getClientOriginalName();
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $path = $f->store("projects/{$p->uuid}", 'public');
            return $p->sources()->create(['type' => 'image', 'label' => $label, 'content' => $path]);
        }
        try {
            $text = $ext === 'pdf'
                ? (new \Smalot\PdfParser\Parser())->parseFile($f->getRealPath())->getText()
                : file_get_contents($f->getRealPath());
        } catch (\Throwable $e) {
            return $this->fail($p, 'file', $label, 'failed', 'Could not read this file.');
        }
        if (strlen(trim($text)) < 20) {
            return $this->fail($p, 'file', $label, 'failed', 'No readable text (scanned image?). Paste the content as text instead.');
        }
        return $this->store($p, 'file', $label, $text);
    }

    public function addText(Project $p, string $label, string $text): Source
    {
        return $this->store($p, 'user', $label ?: 'Pasted text', $text);
    }

    public function addLink(Project $p, string $url, ?string $label = null): Source
    {
        $url = trim($url);
        if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
        $label = $label ?: $url;
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        if (!$host || !filter_var($url, FILTER_VALIDATE_URL)) return $this->fail($p, 'link', $label, 'failed', 'Invalid URL.');

        $blocked = str_contains($url, 'google.com/maps');
        foreach (self::BLOCKED as $b) if ($host === $b || str_ends_with($host, '.' . $b)) $blocked = true;
        if ($blocked) return $this->fail($p, 'link', $label, 'blocked',
            'This site blocks automated reading. Please paste the relevant text (about, phone, hours, services) below.');

        $ip = gethostbyname($host); // SSRF guard
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $this->fail($p, 'link', $label, 'failed', 'Address not allowed.');
        }
        try {
            $r = Http::timeout(10)->withUserAgent('Mozilla/5.0 (compatible; SiteBuilderBot/1.0)')->get($url);
        } catch (\Throwable $e) {
            return $this->fail($p, 'link', $label, 'failed', 'Could not reach this URL.');
        }
        $html = preg_replace('#<(script|style|noscript)\b.*?</\1>#is', ' ', $r->body());
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html))));
        if (!$r->ok() || strlen($text) < 200) {
            return $this->fail($p, 'link', $label, 'blocked', 'Page blocked us or has no readable content. Please paste the text instead.');
        }
        return $this->store($p, 'link', $label, mb_substr($text, 0, 20000));
    }

    public function webSearch(Project $p): Source
    {
                if (!config('services.gemini.search')) {
            return $this->fail($p, 'web', 'Web search', 'failed', 'Web search is turned off (needs a Gemini key with Search grounding). Using your links and pasted text instead.');
        }
        $where = $p->city ? " in {$p->city}" : '';
        try {
            $text = $this->ai->generate(
                "Search the web for the small business \"{$p->business_name}\"{$where}. List EVERY distinct business that matches, separately "
                . "(name, full address, phone, website, category, opening hours, services and prices if public). Do not guess; omit anything you did not find.",
                'You are a careful research assistant. Never invent details.', search: true);
            return $this->store($p, 'web', 'Web search (Gemini + Google Search)', $text);
        } catch (AiUnavailable $e) {
            return $this->fail($p, 'web', 'Web search', 'failed', 'Web search unavailable: ' . $e->getMessage());
        }
    }

    private function store(Project $p, string $type, string $label, string $text): Source
    {
        $kept = []; $removed = 0;
        foreach (preg_split('/\R/', $text) as $line) {
            if (preg_match(self::INJECTION, $line)) $removed++; else $kept[] = $line;
        }
        return $p->sources()->create([
            'type' => $type, 'label' => $label, 'content' => implode("\n", $kept),
            'status' => $removed ? 'flagged' : 'ok',
            'note' => $removed ? "$removed line(s) that looked like instructions to the AI were removed and ignored." : null,
        ]);
    }

    private function fail(Project $p, string $type, string $label, string $status, string $note): Source
    {
        return $p->sources()->create(['type' => $type, 'label' => $label, 'status' => $status, 'note' => $note]);
    }
}
