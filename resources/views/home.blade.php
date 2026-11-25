@extends('layout')
@section('content')
<h1 class="text-3xl font-bold tracking-tight">Turn what you have into a website</h1>
<p class="mt-2 text-slate-600 max-w-xl">Give us a name and city, plus any links, files or photos you already have. We only use facts we can find in them. If something is unclear, we ask you.</p>

<form method="POST" action="{{ route('project.store') }}" enctype="multipart/form-data" class="mt-6 bg-white rounded-xl ring-1 ring-slate-200 p-5 space-y-4">@csrf
 <div class="grid sm:grid-cols-2 gap-4">
  <label class="block text-sm font-medium">Business name
   <input name="business_name" required maxlength="150" value="{{ old('business_name') }}" class="mt-1 w-full border border-slate-300 rounded-lg p-2.5 font-normal"></label>
  <label class="block text-sm font-medium">City
   <input name="city" maxlength="100" value="{{ old('city') }}" class="mt-1 w-full border border-slate-300 rounded-lg p-2.5 font-normal"></label>
 </div>
 <label class="block text-sm font-medium">Google Business listing link <span class="text-slate-400 font-normal">(optional)</span>
  <input name="gbp_url" class="mt-1 w-full border border-slate-300 rounded-lg p-2.5 font-normal"></label>
 <label class="block text-sm font-medium">Website and social links <span class="text-slate-400 font-normal">(one per line)</span>
  <textarea name="links" rows="3" class="mt-1 w-full border border-slate-300 rounded-lg p-2.5 font-normal"></textarea></label>
 <label class="block text-sm font-medium">Anything else we should know <span class="text-slate-400 font-normal">(optional)</span>
  <textarea name="notes" rows="3" class="mt-1 w-full border border-slate-300 rounded-lg p-2.5 font-normal"></textarea></label>
 <label class="block text-sm font-medium">Files: PDF, price list, photos
  <input type="file" name="files[]" multiple accept=".pdf,.txt,.csv,.jpg,.jpeg,.png,.webp" class="mt-1 block text-sm font-normal"></label>
 <button data-busy="Reading your files…" class="bg-teal-700 hover:bg-teal-800 text-white rounded-lg px-5 py-2.5 font-semibold">Start</button>
</form>

<section id="recent" class="hidden mt-10"><h2 class="font-semibold mb-2">Your recent projects</h2><ul id="recentList" class="space-y-1 text-sm"></ul></section>
<script>
try{var r=JSON.parse(localStorage.getItem('abmw_recent')||'[]');var ul=document.getElementById('recentList');
r.forEach(function(x){if(typeof x.url!=='string'||x.url.indexOf('/projects/')!==0)return;var li=document.createElement('li'),a=document.createElement('a');
 a.href=x.url;a.textContent=x.name;a.className='text-teal-700 underline';li.appendChild(a);ul.appendChild(li);});
if(ul.children.length)document.getElementById('recent').classList.remove('hidden');}catch(e){}
</script>
@endsection
