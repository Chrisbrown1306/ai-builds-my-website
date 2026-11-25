<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'AI Builds My Website')</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:Figtree,system-ui,sans-serif}:focus-visible{outline:2px solid #0f766e;outline-offset:2px}details>summary{cursor:pointer;list-style:none}details>summary::-webkit-details-marker{display:none}</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<header class="border-b border-slate-200 bg-white">
 <div class="max-w-3xl mx-auto px-5 py-3 flex items-center justify-between">
  <a href="{{ route('home') }}" class="font-bold text-teal-800">AI Builds My Website</a>
  @yield('header_right')
 </div>
</header>
<main class="max-w-3xl mx-auto px-5 py-8">
 @if(session('error'))<div role="alert" class="bg-rose-50 text-rose-800 ring-1 ring-rose-200 p-3 rounded-lg mb-5 text-sm">{{ session('error') }}</div>@endif
 @if(session('ok'))<div role="status" class="bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 p-3 rounded-lg mb-5 text-sm">{{ session('ok') }}</div>@endif
 @if($errors->any())<div role="alert" class="bg-rose-50 text-rose-800 ring-1 ring-rose-200 p-3 rounded-lg mb-5 text-sm">{{ $errors->first() }}</div>@endif
 @yield('content')
</main>
<script>
document.addEventListener('submit',function(e){var f=e.target,m=f.dataset&&f.dataset.busy;if(!m)return;
 var b=f.querySelector('button[type=submit],button:not([type])');if(!b)return;
 b.dataset.label=b.textContent;b.textContent=m;b.disabled=true;b.classList.add('opacity-60','cursor-wait');});
window.addEventListener('pageshow',function(){document.querySelectorAll('button[data-label]').forEach(function(b){b.disabled=false;b.textContent=b.dataset.label;b.classList.remove('opacity-60','cursor-wait');});});
</script>
</body></html>
