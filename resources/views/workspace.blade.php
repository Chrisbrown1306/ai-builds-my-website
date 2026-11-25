@extends('layout')
@section('title', $p->business_name)
@section('header_right')
 @if($p->sections->count())<a href="{{ route('preview', $p->uuid) }}" target="_blank" rel="noopener" class="text-sm text-teal-700 underline">Preview site</a>@endif
@endsection
@section('content')
@php
 $badge = fn ($s) => match ($s) {
   'ok' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
   'flagged' => 'bg-amber-50 text-amber-800 ring-amber-200',
   default => 'bg-rose-50 text-rose-700 ring-rose-200' };
  $grouped = $facts->toBase()->groupBy('field');
 $steps = [['Sources', $p->sources->count() > 0], ['Facts', $facts->count() > 0], ['Site', $p->sections->count() > 0], ['Published', (bool) $p->slug]];
 $titles = ['hero' => 'Top banner', 'about' => 'About', 'services' => 'Services and prices', 'gallery' => 'Photos', 'contact' => 'Contact details'];
 $btn = 'bg-teal-700 hover:bg-teal-800 text-white rounded-lg px-4 py-2 text-sm font-semibold';
 $card = 'bg-white rounded-xl ring-1 ring-slate-200 p-5 mb-4';
@endphp

<h1 class="text-2xl font-bold">{{ $p->business_name }} <span class="text-slate-400 font-medium text-lg">{{ $p->city }}</span></h1>
<p class="text-xs text-slate-500 mt-1">Bookmark this page. This private link is how you come back to your project.</p>

<ol class="flex flex-wrap gap-2 my-5 text-sm">
 @foreach($steps as $i => [$name, $done])
  <li class="px-3 py-1 rounded-full ring-1 {{ $done ? 'bg-teal-50 text-teal-800 ring-teal-200' : 'bg-white text-slate-500 ring-slate-200' }}">{{ $done ? '✓' : $i + 1 }} {{ $name }}</li>
 @endforeach
</ol>

<section class="{{ $card }}">
 <h2 class="font-semibold mb-3">1. Sources</h2>
 @forelse($p->sources as $s)
  <div class="flex items-start justify-between gap-3 py-1.5 border-b border-slate-100 last:border-0 text-sm">
   <div class="min-w-0"><span class="text-slate-400">{{ $s->type }}</span> <span class="break-all">{{ $s->label }}</span>
    @if($s->note)<p class="text-xs text-slate-500 mt-0.5">{{ $s->note }}</p>@endif</div>
   <span class="text-xs px-2 py-0.5 rounded-full ring-1 shrink-0 {{ $badge($s->status) }}">{{ $s->status }}</span>
  </div>
 @empty<p class="text-sm text-slate-500">No sources yet. Add a link, paste text or upload a file below.</p>@endforelse

 <form method="POST" action="{{ route('source.add', $p->uuid) }}" enctype="multipart/form-data" class="mt-4 space-y-2">@csrf
  <input name="url" placeholder="Add a link" class="w-full border border-slate-300 rounded-lg p-2 text-sm">
  <textarea name="text" rows="3" placeholder="Or paste text, such as an Instagram bio or a price list" class="w-full border border-slate-300 rounded-lg p-2 text-sm"></textarea>
  <div class="flex flex-wrap items-center gap-3"><input type="file" name="file" class="text-sm"><button class="bg-slate-700 hover:bg-slate-800 text-white rounded-lg px-4 py-2 text-sm font-semibold">Add source</button></div>
 </form>
 <form method="POST" action="{{ route('analyze', $p->uuid) }}" data-busy="Reading sources and searching the web… up to a minute" class="mt-4">@csrf
  <button class="{{ $btn }}">Analyze sources and search the web</button></form>
</section>

@if($open->count())
<section class="mb-4">
 <h2 class="font-semibold mb-2">2. We need your help</h2>
 <p class="text-sm text-slate-600 mb-3">We never guess. Answer these and we will continue.</p>
 @foreach($open as $q)
  <form method="POST" action="{{ route('question.answer', [$p->uuid, $q->id]) }}" class="bg-amber-50 ring-1 ring-amber-200 rounded-xl p-4 mb-3">@csrf
   <p class="font-medium mb-2">{{ $q->prompt }}</p>
   @foreach($q->options ?? [] as $o)<label class="block text-sm py-0.5"><input type="radio" name="answer" value="{{ $o }}" required> {{ $o }}</label>@endforeach
   @if(!$q->options)<textarea name="answer" required rows="2" class="w-full border border-slate-300 rounded-lg p-2 text-sm bg-white"></textarea>@endif
   <button class="mt-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg px-4 py-1.5 text-sm font-semibold">Confirm</button>
  </form>
 @endforeach
</section>
@endif

@if($facts->count())
<section class="{{ $card }}">
 <h2 class="font-semibold">3. Facts we will use</h2>
 <p class="text-sm text-slate-600 mb-3">Remove anything that is wrong or that you do not want on the site.</p>
 @foreach($grouped->except('service') as $rows)@foreach($rows as $f)@include('fact-row', ['f' => $f, 'p' => $p])@endforeach @endforeach
 @if($grouped->has('service'))
  <details class="mt-3" {{ $grouped['service']->count() <= 12 ? 'open' : '' }}>
   <summary class="text-sm font-semibold text-teal-800">Services and prices ({{ $grouped['service']->count() }})@if($grouped['service']->count() > 12) <span class="text-xs font-normal text-slate-500">Long lists slow the AI. Keep the 8 to 12 you want shown.</span>@endif</summary>
   <div class="mt-2">@foreach($grouped['service'] as $f)@include('fact-row', ['f' => $f, 'p' => $p])@endforeach</div>
  </details>
 @endif
 @if($p->status !== 'draft' && !$open->count())
  <form method="POST" action="{{ route('generate', $p->uuid) }}" data-busy="Writing your website…" class="mt-4">@csrf
   <button class="bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg px-4 py-2 text-sm font-semibold">{{ $p->sections->count() ? 'Rebuild the whole website' : 'Generate website' }}</button></form>
 @endif
</section>
@endif

@if($p->sections->count())
<h2 class="font-semibold mb-2">4. Edit your site</h2>
@foreach($p->sections as $s)
 <div class="{{ $card }}">
  <h3 class="font-semibold mb-3">{{ $titles[$s->type] ?? ucfirst($s->type) }}</h3>
  <form method="POST" action="{{ route('section.update', [$p->uuid, $s->id]) }}">@csrf @method('PUT')
   @foreach($s->content as $k => $v)
    @if(is_string($v))
     <label class="block text-xs text-slate-500 mt-2">{{ $k }}</label>
     <textarea name="content[{{ $k }}]" rows="{{ strlen($v) > 120 ? 4 : 1 }}" class="w-full border border-slate-300 rounded-lg p-2 text-sm">{{ $v }}</textarea>
    @elseif($k === 'items')
     @foreach($v as $i => $it)
      <div class="border border-slate-200 rounded-lg p-2 mt-2 space-y-1">
       @foreach(['name', 'description', 'price'] as $fld)
        <input name="content[items][{{ $i }}][{{ $fld }}]" value="{{ $it[$fld] ?? '' }}" placeholder="{{ $fld }}" class="w-full border border-slate-200 rounded p-1.5 text-sm">
       @endforeach</div>
     @endforeach
    @elseif($k === 'images')
     <div class="flex flex-wrap gap-2">@foreach($v as $img)<img src="{{ $img }}" alt="" class="h-16 w-16 object-cover rounded">@endforeach</div>
    @endif
   @endforeach
   @if($s->type !== 'gallery')<button class="mt-3 bg-slate-700 hover:bg-slate-800 text-white rounded-lg px-4 py-1.5 text-sm font-semibold">Save changes</button>@endif
  </form>
  @if(in_array($s->type, ['hero', 'about', 'services']))
   <form method="POST" action="{{ route('section.regenerate', [$p->uuid, $s->id]) }}" data-busy="Rewriting…" class="mt-3 flex gap-2">@csrf
    <input name="instruction" placeholder="Optional, for example: shorter or more formal" class="flex-1 border border-slate-300 rounded-lg p-1.5 text-sm">
    <button class="{{ $btn }}">Rewrite this section</button></form>
  @endif
 </div>
@endforeach

<section class="{{ $card }}">
 <h2 class="font-semibold mb-2">5. Publish</h2>
 <form method="POST" action="{{ route('publish', $p->uuid) }}">@csrf <button class="bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg px-4 py-2 text-sm font-semibold">{{ $p->slug ? 'Publish changes' : 'Publish to a public URL' }}</button></form>
 @if($p->slug)
  <p class="mt-3 text-sm">Live at <a id="liveUrl" class="text-teal-700 underline break-all" href="{{ route('site', $p->slug) }}">{{ route('site', $p->slug) }}</a>
   <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('liveUrl').href);this.textContent='Copied'" class="ml-2 text-xs text-slate-500 underline">Copy link</button></p>
 @endif
</section>
@endif

<script>try{var k='abmw_recent',r=JSON.parse(localStorage.getItem(k)||'[]').filter(function(x){return x.url!==location.pathname});
r.unshift({url:location.pathname,name:@json($p->business_name)});localStorage.setItem(k,JSON.stringify(r.slice(0,6)));}catch(e){}</script>
@endsection
