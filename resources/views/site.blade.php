<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $p->business_name }}</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="text-slate-800 bg-white">
@foreach($p->sections as $s) @php $c = $s->content; @endphp
 @if($s->type === 'hero')
  <header class="bg-indigo-700 text-white text-center py-24 px-6"><h1 class="text-4xl md:text-5xl font-bold">{{ $c['headline'] }}</h1>
   <p class="mt-4 text-lg opacity-90">{{ $c['subheadline'] ?? '' }}</p>
   <a href="#contact" class="inline-block mt-8 bg-white text-indigo-700 font-semibold px-6 py-3 rounded">{{ $c['cta'] ?? 'Contact us' }}</a></header>
 @elseif($s->type === 'about')
  <section class="max-w-3xl mx-auto py-16 px-6"><h2 class="text-2xl font-bold mb-4">About us</h2><p class="leading-relaxed">{{ $c['body'] }}</p></section>
 @elseif($s->type === 'services')
  <section class="bg-slate-50 py-16 px-6"><div class="max-w-4xl mx-auto"><h2 class="text-2xl font-bold mb-6">{{ $c['title'] ?? 'Services' }}</h2>
   <div class="grid md:grid-cols-2 gap-4">@foreach($c['items'] as $it)
    <div class="bg-white p-4 rounded shadow"><div class="flex justify-between font-semibold"><span>{{ $it['name'] }}</span><span>{{ $it['price'] }}</span></div>
    <p class="text-sm text-slate-600 mt-1">{{ $it['description'] }}</p></div>@endforeach</div></div></section>
 @elseif($s->type === 'gallery')
  <section class="max-w-5xl mx-auto py-16 px-6"><h2 class="text-2xl font-bold mb-6">Gallery</h2>
   <div class="grid grid-cols-2 md:grid-cols-3 gap-3">@foreach($c['images'] as $img)<img src="{{ $img }}" class="rounded w-full h-48 object-cover" alt="">@endforeach</div></section>
 @elseif($s->type === 'contact')
  <section id="contact" class="max-w-3xl mx-auto py-16 px-6"><h2 class="text-2xl font-bold mb-4">Contact</h2><ul class="space-y-1">
   @foreach(['phone'=>'Phone','email'=>'Email','address'=>'Address','hours'=>'Hours','website'=>'Website'] as $k=>$l)
    @if(!empty($c[$k]))<li><b>{{ $l }}:</b> {{ $c[$k] }}</li>@endif @endforeach
   @foreach($c['socials'] ?? [] as $u)<li><a class="text-indigo-600 underline" href="{{ \Illuminate\Support\Str::startsWith($u, ['http://','https://']) ? $u : '#' }}" rel="noopener nofollow">{{ $u }}</a></li>@endforeach</ul></section>
 @endif
@endforeach
<footer class="text-center text-xs text-slate-400 py-6">Built with AI Builds My Website</footer></body></html>
