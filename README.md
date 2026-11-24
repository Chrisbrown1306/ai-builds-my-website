# AI Builds My Website (Laravel + MySQL + Gemini)

A business owner shares what they have: name and city, links, a Google Business listing, and files (PDF, price list, photos).
The app reads them, asks the owner when facts are unclear, uses AI to write a website, lets the owner edit or regenerate any
section, and publishes it to a public URL (`/s/{slug}`). Everything is stored in MySQL.

## Run locally
Requirements: PHP 8.2+ (with curl, mbstring, openssl, pdo_mysql, zip, fileinfo), Composer, MySQL 8.

```
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
```
Create an empty MySQL database (e.g. `site_builder`), then set `DB_*` and `GEMINI_API_KEY` in `.env`
(free key: https://aistudio.google.com/apikey).
```
php artisan migrate
php artisan storage:link
php artisan serve
```
Windows note: PHP needs a CA bundle for HTTPS. Download https://curl.se/ca/cacert.pem and set
`curl.cainfo` and `openssl.cafile` in php.ini, otherwise every link fetch and AI call fails with cURL error 60.

## Flow
Create project -> add sources -> **Analyze** -> answer questions -> **Generate** -> edit / regenerate a section -> **Publish**.
The private project URL (random UUID) is the owner's access link. The published site is at `/s/{slug}`.

## Golden rule: never invent facts
- Facts are extracted per source and stored in `facts` with their source. The owner can remove any fact.
- Contact details, prices and photos are inserted by PHP from confirmed facts. The AI only writes headline and descriptions.
- AI copy is validated: no URLs or emails, and every number must already exist in the facts, otherwise it is retried and then rejected.
- Missing phone, address or services become questions to the owner. Generation is blocked until they are answered.

## Edge cases
| Case | Behaviour |
|---|---|
| Several businesses with the same name | Asks the owner which one, then re-reads sources for that one |
| Sources disagree (two phone numbers) | Conflict question; the owner's answer wins and is kept on re-analysis |
| Instagram / Facebook / Maps block scraping | Source marked `blocked`; owner is asked to paste the text |
| Instructions hidden in uploaded files | Instruction-like lines are stripped and flagged; the rest is wrapped as untrusted data |
| AI down, busy or over quota | Retries on a fallback model; if still failing, a plain site is built from confirmed facts without AI |

## Configuration (.env)
`GEMINI_API_KEY`, `GEMINI_MODEL`, `GEMINI_FALLBACK_MODEL`, `GEMINI_SEARCH` (true/false, Google Search grounding; off by default
because free keys hit quota). No keys are stored in the repo.

## Security notes
`.env` is git-ignored. Link fetching blocks private and reserved IPs. All output is escaped by Blade.
