# DECISIONS

## Key choices
- **Facts first, copy second.** Sources -> facts (with provenance) -> site. The AI writes only headline, about and short
  descriptions. Phone, address, hours, prices and photos come straight from confirmed facts, so the golden rule is enforced
  by structure and not only by prompting.
- **Questions over guesses.** Ambiguity, conflicts and missing essentials create `questions` the owner must answer first.
- **Prompt-injection defence in layers:** instruction-like lines are stripped and flagged, source text is wrapped in
  `<source>` tags and the model is told it is untrusted data, and the AI never controls contact data.
- **Graceful AI failure:** one fallback model, then a no-AI template from confirmed facts. Editing and publishing still work.
- **Gemini via REST** with a feature flag for Google Search grounding (`GEMINI_SEARCH`). It is off by default because free-tier
  keys returned quota errors; the app then relies on the owner's links and pasted text.
- **No login.** The unguessable project UUID is the owner's access link. Fastest path for a one-person demo.
- **Synchronous processing**, no queue. Simple to run and demo.
- **Sections stored as JSON**, so editing or regenerating one section is a single-row update.

## Known limitations
- A validator blocks invented numbers, URLs and emails, but descriptive claims (ingredients, dietary labels) rely on the
  prompt and on owner review before publishing.
- Very long service lists (40+) make the AI call slow; the owner trims them in the fact review step.
- Link fetching blocks private IPs on the first hop only (redirects are not re-checked).
- The keyless DuckDuckGo search parses their HTML and may break if it changes; results are only ever untrusted sources that go through the same fact review.
- Hosting: the app runs locally; "publish" creates a public page at /s/{slug}. Cloud deployment was cut for time.

## What I cut
- Accounts and auth, custom domains, SEO metadata, themes.
- OCR and vision for scanned PDFs and images (photos are used only as a gallery).
- Queue workers with progress UI, rate limiting on public endpoints.
- Persistent file storage for uploads on ephemeral hosts (needs object storage).
- Published snapshots (the public site renders the live sections).

## What's next
Queued analysis with live progress, vision-based reading of photos and scanned price lists, source citations beside each fact,
theme picker, versioning and undo, user accounts, automated tests for the validator and resolver.
