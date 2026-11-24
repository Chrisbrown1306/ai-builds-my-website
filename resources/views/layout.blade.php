<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'AI Builds My Website')</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-50 text-slate-800"><div class="max-w-4xl mx-auto p-6">
@if(session('error'))<div class="bg-red-100 text-red-800 p-3 rounded mb-4">{{ session('error') }}</div>@endif
@if(session('ok'))<div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('ok') }}</div>@endif
@if($errors->any())<div class="bg-red-100 text-red-800 p-3 rounded mb-4">{{ $errors->first() }}</div>@endif
@yield('content')</div></body></html>
