@php
 $by = $p->sections->keyBy('type');
 $hero = $by['hero']->content ?? [];
 $about = $by['about']->content ?? [];
 $svc = $by['services']->content ?? [];
 $imgs = $by['gallery']->content['images'] ?? [];
 $c = $by['contact']->content ?? [];
 $tel = !empty($c['phone']) ? preg_replace('/[^\d+]/', '', $c['phone']) : null;
 $maps = !empty($c['address']) ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($c['address']) : null;
 $hue = crc32($p->business_name) % 360;                       // every business gets its own accent
 $desc = $hero['subheadline'] ?? ($about['body'] ?? $p->business_name);
 $ld = array_filter(['@context' => 'https://schema.org', '@type' => 'LocalBusiness', 'name' => $p->business_name,
   'description' => $desc, 'telephone' => $c['phone'] ?? null, 'address' => $c['address'] ?? null,
   'openingHours' => $c['hours'] ?? null, 'image' => $imgs ? url($imgs[0]) : null]);
@endphp
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $p->business_name }}{{ $p->city ? ' · ' . $p->city : '' }}</title>
<meta name="description" content="{{ \Illuminate\Support\Str::limit($desc, 155) }}">
<meta property="og:title" content="{{ $p->business_name }}"><meta property="og:description" content="{{ \Illuminate\Support\Str::limit($desc, 155) }}">
@if($imgs)<meta property="og:image" content="{{ url($imgs[0]) }}">@endif
@if(request()->routeIs('preview'))<meta name="robots" content="noindex">@endif
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
<style>
:root{--accent:hsl({{ $hue }} 48% 26%);--accent-2:hsl({{ $hue }} 48% 20%);--soft:hsl({{ $hue }} 40% 95%);--ink:#1c1b1a;--muted:#5d5a56;--line:#e6e2dc}
*{box-sizing:border-box}html{scroll-behavior:smooth}
body{margin:0;font-family:Figtree,system-ui,sans-serif;color:var(--ink);background:#fff;line-height:1.6}
h1,h2{font-family:Fraunces,Georgia,serif;line-height:1.15;margin:0}
a{color:var(--accent)}:focus-visible{outline:3px solid var(--accent);outline-offset:3px}
.wrap{max-width:1040px;margin:0 auto;padding:0 24px}
.bar{position:sticky;top:0;z-index:5;background:rgba(255,255,255,.94);backdrop-filter:blur(6px);border-bottom:1px solid var(--line)}
.bar .wrap{display:flex;align-items:center;justify-content:space-between;height:60px;gap:12px}
.brand{font-family:Fraunces,Georgia,serif;font-weight:700;color:var(--ink);text-decoration:none;font-size:1.15rem}
.btn{display:inline-block;padding:11px 20px;border-radius:999px;background:var(--accent);color:#fff;text-decoration:none;font-weight:600;font-size:.95rem}
.btn:hover{background:var(--accent-2)}.btn.ghost{background:transparent;color:var(--accent);box-shadow:inset 0 0 0 1.5px var(--accent)}
.hero{position:relative;color:#fff;background:var(--accent)}
.hero.has-img{background:linear-gradient(180deg,rgba(0,0,0,.35),rgba(0,0,0,.65)),var(--img) center/cover}
.hero .wrap{padding-top:clamp(72px,14vw,160px);padding-bottom:clamp(56px,10vw,120px)}
.hero h1{font-size:clamp(2.3rem,6vw,4.2rem);max-width:16ch}
.hero p{font-size:clamp(1.05rem,2vw,1.3rem);max-width:44ch;margin:18px 0 28px;opacity:.95}
.hero .btn{background:#fff;color:var(--accent-2)}.hero .btn.ghost{background:transparent;color:#fff;box-shadow:inset 0 0 0 1.5px #fff}
.actions{display:flex;flex-wrap:wrap;gap:12px}
section.blk{padding:clamp(48px,8vw,88px) 0}
.about{display:grid;gap:24px;grid-template-columns:1fr}
@media(min-width:800px){.about{grid-template-columns:1fr 1.6fr;gap:56px}}
h2{font-size:clamp(1.7rem,3.4vw,2.4rem)}
.about p{max-width:62ch;margin:0;font-size:1.1rem;color:var(--muted)}
.tint{background:var(--soft)}
.menu{list-style:none;margin:28px 0 0;padding:0;display:grid;gap:0 56px}
@media(min-width:800px){.menu{grid-template-columns:1fr 1fr}}
.menu li{padding:14px 0;border-bottom:1px solid var(--line)}
.row{display:flex;align-items:baseline;gap:10px}.row b{font-weight:600}
.dots{flex:1;border-bottom:2px dotted #c9c3ba;transform:translateY(-4px);min-width:16px}
.price{font-weight:600;color:var(--accent);white-space:nowrap}
.menu small{display:block;color:var(--muted);margin-top:4px}
.gallery{columns:1;column-gap:14px;margin-top:28px}@media(min-width:600px){.gallery{columns:2}}@media(min-width:900px){.gallery{columns:3}}
.gallery img{width:100%;display:block;border-radius:10px;margin:0 0 14px;break-inside:avoid}
.contact{display:grid;gap:28px;margin-top:24px}@media(min-width:800px){.contact{grid-template-columns:repeat(3,1fr)}}
.contact h3{margin:0 0 6px;font-size:.85rem;letter-spacing:.04em;color:var(--muted);font-weight:600}
.contact p{margin:0}
footer{border-top:1px solid var(--line);padding:22px 0;color:var(--muted);font-size:.85rem}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
</style></head>
<body>
<header class="bar"><div class="wrap"><a class="brand" href="#top">{{ $p->business_name }}</a>
 <nav class="actions">@if($tel)<a class="btn" href="tel:{{ $tel }}">Call</a>@else<a class="btn" href="#contact">Contact</a>@endif</nav></div></header>

<section id="top" class="hero {{ $imgs ? 'has-img' : '' }}" @if($imgs) style="--img:url('{{ $imgs[0] }}')" @endif>
 <div class="wrap"><h1>{{ $hero['headline'] ?? $p->business_name }}</h1>
  @if(!empty($hero['subheadline']))<p>{{ $hero['subheadline'] }}</p>@endif
  <div class="actions">@if($tel)<a class="btn" href="tel:{{ $tel }}">Call {{ $c['phone'] }}</a>@endif
   @if($maps)<a class="btn ghost" href="{{ $maps }}" target="_blank" rel="noopener">Get directions</a>@else<a class="btn" href="#contact">{{ $hero['cta'] ?? 'Contact us' }}</a>@endif</div></div>
</section>

@if(!empty($about['body']))
<section class="blk"><div class="wrap about"><h2>About us</h2><p>{{ $about['body'] }}</p></div></section>
@endif

@if(!empty($svc['items']))
<section class="blk tint"><div class="wrap"><h2>{{ $svc['title'] ?? 'Our services' }}</h2>
 <ul class="menu">@foreach($svc['items'] as $it)
  <li><div class="row"><b>{{ $it['name'] }}</b>@if(!empty($it['price']))<span class="dots"></span><span class="price">{{ $it['price'] }}</span>@endif</div>
   @if(!empty($it['description']))<small>{{ $it['description'] }}</small>@endif</li>@endforeach</ul></div></section>
@endif

@if(count($imgs) > 0)
<section class="blk"><div class="wrap"><h2>Gallery</h2><div class="gallery">@foreach($imgs as $img)<img src="{{ $img }}" alt="{{ $p->business_name }}" loading="lazy">@endforeach</div></div></section>
@endif

<section id="contact" class="blk tint"><div class="wrap"><h2>Visit or get in touch</h2><div class="contact">
 @if(!empty($c['address']))<div><h3>Address</h3><p>{{ $c['address'] }}</p>@if($maps)<p><a href="{{ $maps }}" target="_blank" rel="noopener">Open in Maps</a></p>@endif</div>@endif
 @if(!empty($c['hours']))<div><h3>Hours</h3><p>{{ $c['hours'] }}</p></div>@endif
 <div><h3>Contact</h3>
  @if(!empty($c['phone']))<p><a href="tel:{{ $tel }}">{{ $c['phone'] }}</a></p>@endif
  @if(!empty($c['email']))<p><a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></p>@endif
  @if(!empty($c['website']))<p>{{ $c['website'] }}</p>@endif
  @foreach($c['socials'] ?? [] as $u)@if(\Illuminate\Support\Str::startsWith($u, ['http://', 'https://']))<p><a href="{{ $u }}" rel="noopener nofollow">{{ parse_url($u, PHP_URL_HOST) }}</a></p>@endif @endforeach</div>
</div></div></section>
<footer><div class="wrap">© {{ date('Y') }} {{ $p->business_name }}. Made with AI Builds My Website.</div></footer>
</body></html>
