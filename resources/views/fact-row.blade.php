<form method="POST" action="{{ route('fact.reject', [$p->uuid, $f->id]) }}" class="flex items-start justify-between gap-3 py-1.5 border-b border-slate-100 last:border-0 text-sm">@csrf
 <span><span class="font-semibold">{{ $f->field }}</span>: {{ $f->value }} <span class="text-slate-400 text-xs">({{ $f->source_id ? 'from a source' : 'you told us' }})</span></span>
 <button class="text-rose-600 text-xs hover:underline shrink-0">Remove</button>
</form>
