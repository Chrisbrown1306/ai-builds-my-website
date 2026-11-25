<?php
namespace App\Services;

use App\Models\Project;
use App\Models\Source;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SourceIngestor
{
    const BLOCKED = ['instagram.com', 'facebook.com', 'fb.com', 'linkedin.com', 'x.com', 'twitter.com', 'tiktok.com', 'maps.app.goo.gl', 'g.page'];
    const INJECTION = '/(ignore|disregard|forget)\s+(all\s+|any\s+|the\s+)?(previous|prior|above|earlier)|system prompt|you are now|new instructions|do not (tell|inform) the user|reveal your/i';
    const UA = 'Mozilla/5.0 (compatible; SiteBuilderBot/1.0)';

    public function __construct(private GeminiClient $ai) {}

    public function addFile(Project $p, UploadedFile $f): Source
    {
        $ext = strtolower($f->getClientOriginalExtension());
        $label = $f->getClientOriginalName();

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $mime = $f->getMimeType();
            $path = $f->store("projects/{$p->uuid}", 'public');
            $src = $p->sources()->create(['type' => 'image', 'label' => $label, 'content' => $path]);
            $this->readPhoto($p, $path, $mime, $label);   // text on menus / price lists / signboards
            return $src;
        }
        try {
            $text = $ext === 'pdf'
                ? (new \Smalot\PdfParser\Parser())->parseFile($f->getRealPath())->getText()
                : file_get_contents($f->getRealPath());
        } catch (\Throwable $e) {
            return $this->fail($p, 'file', $label, 'failed', 'Could not read this file.');
        }
        if (strlen(trim($text)) < 20) {
            return $this->fail($p, 'file', $label, 'failed', 'No readable text (scanned image?). Upload it as a photo or paste the content.');
        }
        return $this->store($p, 'file', $label, $text);
    }

    public function addText(Project $p, string $label, string $text): Source
    {
        return $this->store($p, 'user', $label ?: 'Pasted text', $text);
    }

    public function addLink(Project $p, string $url, ?string $label = null, string $type = 'link'): Source
    {
        $url = trim($url);
        if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
        $label = $label ?: $url;

        $dup = $p->sources()->where('type', $type)->where('label', $label)->whereIn('status', ['ok', 'flagged'])->first();
        if ($dup) return $dup;                                   // same link added twice

        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        if (!$host || !filter_var($url, FILTER_VALIDATE_URL)) return $this->fail($p, $type, $label, 'failed', 'Invalid URL.');

        $blocked = str_contains($url, 'google.com/maps');
        foreach (self::BLOCKED as $b) if ($host === $b || str_ends_with($host, '.' . $b)) $blocked = true;
        if ($blocked) return $this->fail($p, $type, $label, 'blocked',
            'This site blocks automated reading. Please paste the relevant text (about, phone, hours, services) below.');

        $ip = gethostbyname($host);                              // SSRF guard
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $this->fail($p, $type, $label, 'failed', 'Address not allowed.');
        }
        try {
            $r = Http::timeout(10)->withUserAgent(self::UA)->get($url);
        } catch (\Throwable $e) {
            return $this->fail($p, $type, $label, 'failed', 'Could not reach this URL.');
        }
        $html = preg_replace('#<(script|style|noscript)\b.*?</\1>#is', ' ', $r->body());
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html))));
        if (!$r->ok() || strlen($text) < 200) {
            return $this->fail($p, $type, $label, 'blocked', 'Page blocked us or has no readable content. Please paste the text instead.');
        }
        return $this->store($p, $type, $label, mb_substr($text, 0, 20000));
    }

    /** Web search: Gemini + Google Search grounding when enabled, otherwise a keyless DuckDuckGo lookup. */
    public function webSearch(Project $p): Source
    {
        if (config('services.gemini.search')) {
            $where = $p->city ? " in {$p->city}" : '';
            try {
                $text = $this->ai->generate(
                    "Search the web for the small business \"{$p->business_name}\"{$where}. List EVERY distinct business that matches, separately "
                    . "(name, full address, phone, website, category, opening hours, services and prices if public). Do not guess; omit anything you did not find.",
                    'You are a careful research assistant. Never invent details.', search: true);
                return $this->store($p, 'web', 'Web search (Gemini + Google Search)', $text);
            } catch (AiUnavailable $e) {
                // fall through to the keyless search
            }
        }
        $urls = $this->duckSearch($p);
        if (!$urls) {
            return $this->fail($p, 'web', 'Web search', 'failed', 'No web results (search unavailable). Using your links and pasted text instead.');
        }
        $last = null;
        foreach ($urls as $u) $last = $this->addLink($p, $u, 'Web result: ' . parse_url($u, PHP_URL_HOST), 'web');
        return $last;
    }

    private function duckSearch(Project $p): array
    {
        try {
            $r = Http::asForm()->timeout(10)->withUserAgent(self::UA)
                ->post('https://html.duckduckgo.com/html/', ['q' => trim($p->business_name . ' ' . $p->city)]);
        } catch (\Throwable $e) { return []; }
        if (!$r->ok()) return [];

        preg_match_all('/<a[^>]+class="result__a"[^>]+href="([^"]+)"/i', $r->body(), $m);
        $urls = []; $seen = [];
        foreach ($m[1] as $href) {
            $href = html_entity_decode($href);
            if (str_contains($href, 'uddg=')) {
                parse_str(parse_url(str_starts_with($href, '//') ? 'https:' . $href : $href, PHP_URL_QUERY) ?? '', $qs);
                $href = $qs['uddg'] ?? '';
            }
            $host = strtolower(parse_url($href, PHP_URL_HOST) ?? '');
            if (!$host || str_contains($host, 'duckduckgo.com') || isset($seen[$host])) continue;
            $seen[$host] = true; $urls[] = $href;
            if (count($urls) >= 3) break;
        }
        return $urls;
    }

    /** Reads visible text from a photo (menu, price list, signboard). Silent if the AI is unavailable. */
    private function readPhoto(Project $p, string $path, ?string $mime, string $label): void
    {
        if ($p->sources()->where('type', 'image')->count() > 4) return;   // keep uploads quick
        try {
            $text = trim($this->ai->vision(
                'Transcribe any readable text in this image (menu, price list, signboard, phone number, address). '
                . 'Copy it exactly. If there is no readable text, reply exactly NONE. Do not describe the image.',
                Storage::disk('public')->path($path), $mime ?: 'image/jpeg'));
        } catch (\Throwable $e) { return; }
        if (strtoupper($text) === 'NONE' || strlen($text) < 15) return;
        $this->store($p, 'file', 'Text read from photo: ' . $label, $text);
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
