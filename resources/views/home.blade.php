@extends('layout')
@section('content')
<h1 class="text-3xl font-bold mb-1">AI Builds My Website</h1>
<p class="mb-6 text-slate-600">Tell us what you have. Give whatever you have, any mix works. I never invent facts; if unsure, I ask you.</p>
<form method="POST" action="{{ route('project.store') }}" enctype="multipart/form-data" class="bg-white p-5 rounded shadow space-y-3">@csrf
 <input name="business_name" required placeholder="Business name *" class="w-full border rounded p-2" value="{{ old('business_name') }}">
 <input name="city" placeholder="City" class="w-full border rounded p-2" value="{{ old('city') }}">
 <input name="gbp_url" placeholder="Google Business listing link (optional)" class="w-full border rounded p-2">
 <textarea name="links" rows="3" placeholder="Website / Instagram / Facebook links, one per line" class="w-full border rounded p-2"></textarea>
 <textarea name="notes" rows="3" placeholder="Anything else you want to tell us (optional)" class="w-full border rounded p-2"></textarea>
 <label class="block text-sm">Files (PDF, price list, photos) <input type="file" name="files[]" multiple class="block mt-1"></label>
 <button class="bg-indigo-600 text-white px-4 py-2 rounded">Start</button>
</form>
@endsection
