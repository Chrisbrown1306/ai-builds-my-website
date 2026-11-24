@extends('layout')
@section('title', $p->business_name)
@section('content')
<h1 class="text-2xl font-bold">{{ $p->business_name }} <span class="text-slate-500 text-lg">{{ $p->city }}</span></h1>
<p class="text-xs text-slate-500 mb-4">Bookmark this page — this private link is how you come back to your project.</p>

<h2 class="font-semibold mt-4 mb-2">1. Sources</h2>
@foreach($p->sources as $s)
 <div class="bg-white p-2 rounded shadow mb-1 text-sm flex justify-between">
  <span>{{ $s->type }} · {{ $s->label }}</span>
  <span class="{{ in_array($s->status,['blocked','failed']) ? 'text-red-600' : ($s->status==='flagged' ? 'text-amber-600' : 'text-green-600') }}">{{ $s->status }}{{ $s->note ? ' — '.$s->note : '' }}</span>
 </div>
@endforeach
<form method="POST" action="{{ route('source.add', $p->uuid) }}" enctype="multipart/form-data" class="bg-white p-3 rounded shadow mt-2 space-y-2">@csrf
 <input name="url" placeholder="Add a link" class="w-full border rounded p-2 text-sm">
 <textarea name="text" rows="3" placeholder="Or paste text (e.g. your Instagram bio, price list)" class="w-full border rounded p-2 text-sm"></textarea>
 <input type="file" name="file" class="text-sm"> <button class="bg-slate-700 text-white px-3 py-1 rounded text-sm">Add</button>
</form>
<form method="POST" action="{{ route('analyze', $p->uuid) }}" class="mt-3">@csrf
 <button class="bg-indigo-600 text-white px-4 py-2 rounded">Analyze sources &amp; search the web</button></form>

@if($open->count())
<h2 class="font-semibold mt-6 mb-2">2. I need your help (I won't guess)</h2>
@foreach($open as $q)
 <form method="POST" action="{{ route('question.answer', [$p->uuid, $q->id]) }}" class="bg-amber-50 border border-amber-300 p-3 rounded mb-2">@csrf
  <p class="mb-2">{{ $q->prompt }}</p>
  @foreach($q->options ?? [] as $o)
   <label class="block text-sm"><input type="radio" name="answer" value="{{ $o }}" required> {{ $o }}</label>
  @endforeach
  @if(!$q->options)<textarea name="answer" required rows="2" class="w-full border rounded p-2"></textarea>@endif
  <button class="mt-2 bg-amber-600 text-white px-3 py-1 rounded text-sm">Confirm</button>
 </form>
@endforeach
@endif

@if($facts->count())
<h2 class="font-semibold mt-6 mb-2">3. Facts I'll use (remove anything wrong)</h2>
<div class="bg-white rounded shadow p-3 text-sm">
@foreach($facts as $f)
 <form method="POST" action="{{ route('fact.reject', [$p->uuid, $f->id]) }}" class="flex justify-between border-b py-1">@csrf
  <span><b>{{ $f->field }}</b>: {{ $f->value }} <i class="text-slate-400">({{ $f->source_id ? 'from source' : 'you told me' }})</i></span>
  <button class="text-red-600">remove</button></form>
@endforeach</div>
@endif

@if($p->status !== 'draft' && !$open->count())
<form method="POST" action="{{ route('generate', $p->uuid) }}" class="mt-4">@csrf
 <button class="bg-green-600 text-white px-4 py-2 rounded">{{ $p->sections->count() ? 'Regenerate whole website' : 'Generate website' }}</button></form>
@endif

@if($p->sections->count())
<h2 class="font-semibold mt-6 mb-2">4. Edit</h2>
@foreach($p->sections as $s)
 <div class="bg-white p-4 rounded shadow mb-3">
 <form method="POST" action="{{ route('section.update', [$p->uuid, $s->id]) }}">@csrf @method('PUT')
  <h3 class="font-semibold capitalize mb-2">{{ $s->type }}</h3>
  @foreach($s->content as $k => $v)
   @if(is_string($v))
    <label class="text-xs text-slate-500">{{ $k }}</label>
    <textarea name="content[{{ $k }}]" rows="{{ strlen($v) > 120 ? 4 : 1 }}" class="w-full border rounded p-2 mb-2">{{ $v }}</textarea>
   @elseif($k === 'items')
    @foreach($v as $i => $it)<div class="border rounded p-2 mb-2">
     @foreach(['name','description','price'] as $fld)
      <input name="content[items][{{ $i }}][{{ $fld }}]" value="{{ $it[$fld] ?? '' }}" placeholder="{{ $fld }}" class="w-full border rounded p-1 mb-1 text-sm">
     @endforeach</div>@endforeach
   @endif
  @endforeach
  <button class="bg-slate-700 text-white px-3 py-1 rounded text-sm">Save</button>
 </form>
 @if(in_array($s->type, ['hero','about','services']))
 <form method="POST" action="{{ route('section.regenerate', [$p->uuid, $s->id]) }}" class="mt-2 flex gap-2">@csrf
  <input name="instruction" placeholder="Optional: e.g. more formal, shorter" class="flex-1 border rounded p-1 text-sm">
  <button class="bg-indigo-600 text-white px-3 py-1 rounded text-sm">Regenerate this section</button></form>
 @endif
 </div>
@endforeach

<h2 class="font-semibold mt-6 mb-2">5. Publish</h2>
<form method="POST" action="{{ route('publish', $p->uuid) }}">@csrf <button class="bg-green-700 text-white px-4 py-2 rounded">{{ $p->slug ? 'Update published site' : 'Publish to a public URL' }}</button></form>
@if($p->slug)<p class="mt-2">Live: <a class="text-indigo-600 underline" href="{{ route('site', $p->slug) }}">{{ route('site', $p->slug) }}</a></p>@endif
@endif
@endsection
