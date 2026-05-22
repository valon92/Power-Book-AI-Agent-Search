# Powerbook.ai në cPanel (pa prekur arontrade.net)

Ky udhëzues është për llogarinë **aronqbxm** ku **arontrade.net** është domeni kryesor. Powerbook vendoset në **folder të veçantë** + **Addon Domain** — `public_html` i arontrade.net **nuk ndryshohet**.

---

## A është e mundur?

**Po.** Në të njëjtin hosting mund të kesh:

| Domain | Vendndodhja tipike | Prek arontrade? |
|--------|-------------------|-----------------|
| `arontrade.net` | `/home/aronqbxm/public_html` | — (mos e prek) |
| `powerbook.ai` | `/home/aronqbxm/powerbook.ai/public` | **Jo** (folder i ri) |

Kërkesa: PHP **8.1+**, `composer`, leje shkrimi në `storage/` dhe `bootstrap/cache/`.

---

## Hapi 1 — DNS në Namecheap (powerbook.ai)

Te **Namecheap → Domain List → powerbook.ai → Advanced DNS**:

| Type | Host | Value | TTL |
|------|------|-------|-----|
| **A** | `@` | `162.0.232.61` | Automatic |
| **A** | `www` | `162.0.232.61` | Automatic |

(IP e përbashkët e serverit — e njëjta si në cPanel → **Shared IP Address**.)

Prit 15 minuta–24 orë që DNS të përhapet.

---

## Hapi 2 — Addon Domain në cPanel (pa prekur arontrade)

1. Hyr në **cPanel** (si user `aronqbxm`).
2. **Domains** → **Create A New Domain** (ose **Addon Domains**).
3. Domain: `powerbook.ai`
4. **Document Root** (e rëndësishme):

   ```
   /home/aronqbxm/powerbook.ai/public
   ```

   Mos përdor `public_html` të arontrade.net.  
   cPanel mund të sugjerojë `powerbook.ai/public_html` — ndryshoje në strukturën Laravel: folderi `public` i projektit.

5. Krijo domainin. Aktivizo **SSL** (AutoSSL / Let's Encrypt) për `powerbook.ai` dhe `www.powerbook.ai`.

**Kontroll:** Hap `arontrade.net` — duhet të funksionojë njësoj si më parë.

---

## Hapi 3 — Ngarko skedarët e projektit

### Opsioni A — Git (rekomandohet)

1. cPanel → **Git Version Control** → **Create**.
2. Clone URL: `https://github.com/valon92/Power-Book-AI-Agent-Search.git`
3. Repository Path:

   ```
   /home/aronqbxm/powerbook.ai
   ```

4. Pas clone, në terminal cPanel (ose SSH):

   ```bash
   cd /home/aronqbxm/powerbook.ai
   composer install --no-dev --optimize-autoloader
   ```

5. **Build frontend** (në Mac, para push ose pas clone):

   ```bash
   npm ci
   npm run build
   ```

   Ngarko/ commit `public/build/` ose ekzekuto `npm run build` në server nëse Node.js është i disponueshëm në cPanel.

### Opsioni B — ZIP

1. Lokalisht:

   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   ```

2. ZIP projektin (pa `node_modules`, pa `.git`, **pa `.env`**).
3. cPanel → **File Manager** → `/home/aronqbxm/powerbook.ai` → Upload & Extract.

---

## Hapi 4 — Skedari `.env` në server

Në `/home/aronqbxm/powerbook.ai/.env` (kopjo nga `.env.example`):

```env
APP_NAME=Powerbook.ai
APP_ENV=production
APP_KEY=base64:...   # php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://powerbook.ai

LOG_CHANNEL=stack
LOG_LEVEL=error

SESSION_DRIVER=array
CACHE_DRIVER=file

POWERBOOK_DEFAULT_CITY=Ferizaj

AI_PROVIDER=openai
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini
OPENAI_ENABLED=true

# Opsionale
GEMINI_API_KEY=
EBAY_CLIENT_ID=
EBAY_CLIENT_SECRET=
SERPAPI_KEY=
```

Gjenero çelësin:

```bash
cd /home/aronqbxm/powerbook.ai
php artisan key:generate
php artisan config:cache
php artisan route:cache
```

---

## Hapi 5 — Lejet (permissions)

```bash
chmod -R 775 storage bootstrap/cache
chown -R aronqbxm:aronqbxm storage bootstrap/cache
```

(Në shared hosting shpesh mjafton **755** për `storage` dhe `bootstrap/cache` nëse shfaqen gabime 500.)

---

## Hapi 6 — PHP version

cPanel → **Select PHP Version** (ose **MultiPHP Manager**):

- Zgjidh **PHP 8.1** ose **8.2** për domain `powerbook.ai`.
- Aktivizo: `curl`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`.

**Mos ndrysho** versionin global në mënyrë që të prishë arontrade nëse ai kërkon version tjetër — vendos PHP vetëm për addon domain nëse ofrohet.

---

## Hapi 7 — Test

1. https://powerbook.ai — faqja kryesore (Vue SPA).
2. https://powerbook.ai/api/health — duhet JSON `ok`.
3. https://arontrade.net — **i njëjti** si para deploy-it.

---

## Çfarë të mos bësh (arontrade.net)

| Mos bëj | Pse |
|---------|-----|
| Mos ngarko Powerbook në `public_html` | Është faqja e arontrade.net |
| Mos ndrysho `.htaccess` në rrënjë të arontrade | Mund ta prishë sitin ekzistues |
| Mos fshi/substituo databazën e arontrade | Powerbook MVP nuk përdor DB |
| Mos përdor **Alias** që tregon në të njëjtin `public_html` | Të dy domainet do përplasen |

---

## Struktura finale në server

```
/home/aronqbxm/
├── public_html/              ← arontrade.net (MOS PREK)
├── powerbook.ai/             ← projekti Laravel
│   ├── app/
│   ├── public/               ← Document Root i powerbook.ai
│   │   ├── index.php
│   │   ├── build/
│   │   └── images/
│   ├── storage/
│   ├── .env
│   └── vendor/
└── ...
```

---

## Probleme të zakonshme

| Problem | Zgjidhje |
|---------|----------|
| 500 Error | Kontrollo `storage/logs/laravel.log`, lejet, `APP_KEY` |
| Faqe bosh / pa CSS | `npm run build`, kontrollo `public/build/` |
| API kthen HTML | Document root duhet të jetë `public/`, jo rrënja e Laravel |
| powerbook.ai nuk hapet | DNS A record → `162.0.232.61`, prit propagim |
| arontrade u prish | Rikthe `public_html` nga backup cPanel; Powerbook duhet në folder tjetër |

---

## Përditësim i ardhshëm

```bash
cd /home/aronqbxm/powerbook.ai
git pull
composer install --no-dev --optimize-autoloader
npm run build   # ose build lokalisht dhe upload public/build
php artisan config:cache
php artisan route:cache
```

---

## Mbështetje cPanel

Nëse **Git** ose **PHP 8.2** nuk janë të aktivizuara, hap ticket te hosting provider: *"Enable Git + PHP 8.1 for addon domain powerbook.ai without modifying primary domain arontrade.net."*
