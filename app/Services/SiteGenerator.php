<?php
namespace App\Services;

use App\Models\Project;
use App\Models\Section;
use Illuminate\Support\Facades\Storage;

class SiteGenerator
{
    public function __construct(private GeminiClient $ai) {}

    /** Builds all sections. Returns false if AI was down and the no-AI template was used. */
    public function build(Project $p): bool
    {
        $f = $this->facts($p);
        $aiUsed = true;
        try { $copy = $this->copy($f); } catch (AiUnavailable $e) { \Log::warning('Copy failed: ' . $e->getMessage()); $copy = $this->fallback($f); $aiUsed = false; }

        $p->sections()->delete();
        $i = 0;
        foreach ($this->assemble($p, $f, $copy) as $type => $content) {
            $p->sections()->create(['type' => $type, 'position' => $i++, 'content' => $content]);
        }
        $p->update(['status' => 'generated']);
        return $aiUsed;
    }

    public function regenerate(Project $p, Section $s, string $instruction): void
    {
        $copy = $this->copy($this->facts($p), $instruction); // throws AiUnavailable; old content stays untouched
        $c = $s->content;
        if ($s->type === 'hero') { $c['headline'] = $copy['hero']['headline']; $c['subheadline'] = $copy['hero']['subheadline'] ?? ''; }
        if ($s->type === 'about') { $c['body'] = $copy['about']['body']; }
        if ($s->type === 'services') foreach ($c['items'] as $i => $it) $c['items'][$i]['description'] = $this->cleanDesc($copy['services'][$i]['description'] ?? '', $it['name']);
        $s->update(['content' => $c]);
    }

    private function facts(Project $p): array
    {
        $rows = $p->facts()->where('status', 'confirmed')->get();
        $one = fn ($k) => $rows->where('field', $k)->first()?->value;
        $services = $rows->where('field', 'service')->map(function ($r) {
            [$n, $pr] = array_pad(array_map('trim', explode('::', $r->value, 2)), 2, '');
            return ['name' => $n, 'price' => $pr];
        })->values()->all();
        $f = ['name' => $p->business_name, 'city' => $p->city, 'about' => $one('about'), 'phone' => $one('phone'), 'email' => $one('email'),
              'address' => $one('address'), 'hours' => $one('hours'), 'website' => $one('website'),
              'socials' => $rows->where('field', 'social')->pluck('value')->values()->all(), 'services' => $services];
        $f['blob'] = json_encode($f, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $f;
    }

    private function copy(array $f, string $instruction = ''): array
    {
        $names = array_map(fn ($s, $i) => ['index' => $i, 'name' => $s['name']], $f['services'], array_keys($f['services']));
        $facts = json_encode(['name' => $f['name'], 'city' => $f['city'], 'about' => $f['about'], 'hours' => $f['hours'], 'services' => $names], JSON_UNESCAPED_UNICODE);
        $extra = $instruction ? "\nOwner's style request (tone/wording only, never new facts): " . mb_substr($instruction, 0, 300) : '';
         $prompt = "VERIFIED FACTS:\n$facts\n$extra\n\nWrite website copy as JSON: "
            . '{"hero":{"headline":"","subheadline":""},"about":{"body":""},"services":[{"index":0,"description":""}]}' . "\n"
            . "Rules: use ONLY the verified facts. No invented claims: no years in business, awards, numbers, prices, guarantees, reviews, staff names, contact details or URLs. "
            . "Service descriptions: only describe an item with words that appear in its own name (for example its ingredients or flavours). "
            . "Do NOT apply facts about the whole business (such as its history or baking style) to a single item. "
            . "If the name alone gives nothing to say, return an empty string for that description. Never repeat the name word for word. "
            . "If there is no 'about' fact keep the about body short and generic. Friendly, plain tone.";

        for ($try = 0; $try < 2; $try++) {
            $c = $this->ai->json($prompt, 'You write website copy for small businesses. Never invent facts.');
            if ($this->allSafe($c, $f['blob'])) return $c;
        }
        throw new AiUnavailable('AI copy contained unverified details.');
    }

    private function allSafe(array $c, string $blob): bool
    {
        return \App\Support\CopyValidator::isSafe($c, $blob);
    }
    private function fallback(array $f): array
    {
        return ['hero' => ['headline' => $f['name'], 'subheadline' => $f['city'] ? 'Serving ' . $f['city'] : ''],
                'about' => ['body' => $f['about'] ?? ''], 'services' => []];
    }

    private function assemble(Project $p, array $f, array $copy): array
    {
        $out = ['hero' => ['headline' => $copy['hero']['headline'], 'subheadline' => $copy['hero']['subheadline'] ?? '', 'cta' => 'Contact us']];
        if (!empty($copy['about']['body'])) $out['about'] = ['body' => $copy['about']['body']];
        if ($f['services']) {
            $desc = collect($copy['services'] ?? [])->keyBy('index');
            $out['services'] = ['title' => 'Our services', 'items' => array_map(fn ($s, $i) => [
                'name' => $s['name'], 'description' => $this->cleanDesc($desc[$i]['description'] ?? '', $s['name']), 'price' => $s['price']], $f['services'], array_keys($f['services']))];
        }
        $imgs = $p->sources()->where('type', 'image')->pluck('content')->map(fn ($path) => Storage::url($path))->all();
        if ($imgs) $out['gallery'] = ['images' => $imgs];
        // Contact comes straight from confirmed facts, never from the AI.
        $out['contact'] = array_filter(['phone' => $f['phone'], 'email' => $f['email'], 'address' => $f['address'], 'hours' => $f['hours'], 'website' => $f['website'], 'socials' => $f['socials']]);
        return $out;
    }
    private function cleanDesc(string $desc, string $name): string
    {
        $words = fn ($s) => array_filter(preg_split('/\W+/u', mb_strtolower($s)));
        $stop = ['a', 'an', 'the', 'with', 'made', 'and', 'of', 'in', 'our', 'is', 'are', 'to', 'for', 'by', 'from', 'served', 'this', 'that'];
        $extra = array_diff($words($desc), $words($name), $stop);
        return count($extra) === 0 ? '' : trim($desc);
    }
}
