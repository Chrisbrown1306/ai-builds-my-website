<?php
namespace App\Http\Controllers;

use App\Models\{Fact, Project, Question, Section};
use App\Services\{AiUnavailable, FactResolver, SiteGenerator, SourceIngestor};
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function home() { return view('home'); }

    public function store(Request $r, SourceIngestor $in)
    {
        $d = $r->validate([
            'business_name' => 'required|string|max:150', 'city' => 'nullable|string|max:100',
            'gbp_url' => 'nullable|string|max:500', 'links' => 'nullable|string|max:3000', 'notes' => 'nullable|string|max:8000',
            'files.*' => 'file|mimes:pdf,txt,csv,jpg,jpeg,png,webp|max:10240',
        ]);
        $p = Project::create(['uuid' => (string) Str::uuid(), 'business_name' => $d['business_name'], 'city' => $d['city'] ?? null]);
        if (!empty($d['gbp_url'])) $in->addLink($p, $d['gbp_url'], 'Google Business listing');
        foreach (preg_split('/\R/', $d['links'] ?? '') as $u) if (trim($u) !== '') $in->addLink($p, $u);
        foreach ($r->file('files', []) as $f) $in->addFile($p, $f);
        if (!empty($d['notes'])) $in->addText($p, 'Owner notes', $d['notes']);
        return redirect()->route('workspace', $p->uuid);
    }

    public function workspace(Project $project)
    {
        return view('workspace', ['p' => $project->load(['sources', 'sections']),
            'open' => $project->openQuestions()->get(),
            'facts' => $project->facts()->where('status', 'confirmed')->orderBy('field')->get()]);
    }

    public function addSource(Request $r, Project $project, SourceIngestor $in)
    {
        $r->validate(['url' => 'nullable|string|max:500', 'text' => 'nullable|string|max:20000', 'file' => 'nullable|file|mimes:pdf,txt,csv,jpg,jpeg,png,webp|max:10240']);
        if ($r->filled('url')) $in->addLink($project, $r->url);
        if ($r->filled('text')) $in->addText($project, $r->input('label', 'Pasted text'), $r->text);
        if ($r->hasFile('file')) $in->addFile($project, $r->file('file'));
        return back()->with('ok', 'Source added. Click "Analyze sources" to re-read everything.');
    }

    public function analyze(Project $project, SourceIngestor $in, FactResolver $res)
    {
        set_time_limit(180);
        $project->sources()->where('type', 'web')->delete();
        $in->webSearch($project);
        try {
            $res->extract($project);
            $project->update(['status' => 'review']);
        } catch (AiUnavailable $e) {
            return back()->with('error', "The AI service is unavailable right now ({$e->getMessage()}). Your data is saved — try again in a minute, or add details by pasting text.");
        }
        return back();
    }

    public function answer(Request $r, Project $project, Question $question, FactResolver $res)
    {
        $r->validate(['answer' => 'required|string|max:2000']);
        try {
            if ($res->answer($project, $question, $r->answer)) $res->extract($project);
        } catch (AiUnavailable $e) {
            return back()->with('error', 'Saved your answer, but the AI is unavailable to re-read sources. Try "Analyze sources" again shortly.');
        }
        return back();
    }

    public function rejectFact(Project $project, Fact $fact)
    {
        $fact->update(['status' => 'rejected']);
        return back();
    }

    public function generate(Project $project, SiteGenerator $gen)
    {
        if ($project->openQuestions()->exists()) return back()->with('error', 'Please answer the open questions first. I will not guess.');
        set_time_limit(180);
        $ai = $gen->build($project);
        return back()->with($ai ? 'ok' : 'error', $ai ? 'Website generated. Review and edit below.'
            : 'The AI is unavailable, so I built a plain version from your confirmed facts. You can edit it now and click Regenerate later.');
    }

    public function updateSection(Request $r, Project $project, Section $section)
    {
        $content = $section->content;
        foreach ((array) $r->input('content', []) as $k => $v) {
            if (!array_key_exists($k, $content)) continue;
            $content[$k] = is_array($v) ? array_replace_recursive($content[$k], $v) : (string) $v;
        }
        $section->update(['content' => $content]);
        return back()->with('ok', ucfirst($section->type) . ' saved.');
    }

    public function regenerate(Request $r, Project $project, Section $section, SiteGenerator $gen)
    {
        set_time_limit(180);
        try { $gen->regenerate($project, $section, (string) $r->input('instruction', '')); }
        catch (AiUnavailable $e) { return back()->with('error', 'AI unavailable — your existing text was kept. Try again shortly.'); }
        return back()->with('ok', ucfirst($section->type) . ' regenerated.');
    }

    public function publish(Project $project)
    {
        if ($project->sections()->doesntExist()) return back()->with('error', 'Generate the website first.');
        $slug = $project->slug ?: Str::slug($project->business_name . ' ' . $project->city) . '-' . Str::lower(Str::random(5));
        $project->update(['slug' => $slug, 'status' => 'published', 'published_at' => now()]);
        return back()->with('ok', 'Published at ' . route('site', $slug));
    }

        public function site(string $slug)
    {
        $p = Project::where('slug', $slug)->where('status', 'published')->with('sections')->firstOrFail();
        return view('site', ['p' => $p]);
    }

    public function preview(Project $project)
    {
        abort_if($project->sections()->doesntExist(), 404);
        return view('site', ['p' => $project->load('sections')]);
    }
}
