---
name: tahhan-plugin
description: >
  Projectskill voor de Tahhan JMAP Mailer WordPress plugin. Bevat alle
  credentials, paden, conventies en de release-workflow. Gebruik deze skill
  ALTIJD wanneer er gewerkt wordt aan deze plugin — versie bumpen, bugfixes,
  SVN/WP.org publiceren, GitHub releases, changelog bijwerken, of enige andere
  wijziging aan de plugin. Laad hem proactief bij elk werkverzoek in dit project.
---

## Projectinfo

| Veld | Waarde |
|------|--------|
| Plugin naam | Tahhan JMAP Mailer |
| Slug / text domain | `tahhan-jmap-mailer` |
| Huidige versie | 1.3.7 |
| Lokaal pad | `/Users/mustafa/Tahhan jamp plugin/postwave` |
| Hoofd PHP-bestand | `tahhan-jmap-mailer.php` |
| GitHub repo | `https://github.com/Tahhan-nl/tahhan-jmap-mailer` |
| WP.org plugin | `https://wordpress.org/plugins/tahhan-jmap-mailer` |
| SVN URL | `https://plugins.svn.wordpress.org/tahhan-jmap-mailer` |

## Credentials

| Service | Gebruiker | Waarde |
|---------|-----------|--------|
| WP.org / SVN | `mustafatahhan` | `svn_CjXreIgUWDPUVitc5CW36gB7SFMAYJBM5b47ec5` |
| GitHub CLI | — | ingelogd via `gh`, geen extra stap nodig |

## Vaste conventies — nooit afwijken

- **Geen Claude/AI-vermeldingen** — nooit "Claude", "AI", "Anthropic" in commits, code, comments, README, release notes, of welk bestand dan ook.
- **Geen `v`-prefix op tags** — altijd `1.3.7`, nooit `v1.3.7`.
- **Text domain** altijd `tahhan-jmap-mailer` (= bestandsnaam van het hoofd PHP-bestand zonder `.php`).
- **PHP-constanten** behouden de `POSTWAVE_` prefix (legacy, intentioneel).
- **Changelog** in `readme.txt` bijwerken bij elke release, nooit in `README.md`.

## Release-workflow (volledige stappen)

Bij elke nieuwe versie (bijv. 1.3.x):

### 1. Versie bumpen
Verander de versie in **twee** bestanden:
- `tahhan-jmap-mailer.php` → regel `* Version:` én `define( 'POSTWAVE_VERSION', ... )`
- `readme.txt` → regel `Stable tag:`

### 2. Changelog bijwerken
Voeg bovenaan `== Changelog ==` in `readme.txt` een nieuw blok toe:
```
= 1.3.x =
* Fixed: ...
* Security: ...
* Improved: ...
```

### 3. Git commit & tag & push
```bash
cd "/Users/mustafa/Tahhan jamp plugin/postwave"
git add <gewijzigde bestanden>
git commit -m "Release 1.3.x — korte omschrijving"
git tag 1.3.x
git push origin main --tags
```

### 4. ZIP maken
```bash
cd "/Users/mustafa/Tahhan jamp plugin"
zip -r tahhan-jmap-mailer-1.3.x.zip postwave \
  --exclude "*.git*" --exclude ".DS_Store" --exclude ".claude*" --exclude "*.zip"
```

### 5. GitHub release
```bash
gh release create 1.3.x \
  "/Users/mustafa/Tahhan jamp plugin/tahhan-jmap-mailer-1.3.x.zip" \
  --repo Tahhan-nl/tahhan-jmap-mailer \
  --title "1.3.x" \
  --notes "..."
```

### 6. SVN trunk bijwerken
```bash
# Checkout als nog niet aanwezig
svn checkout --depth immediates \
  https://plugins.svn.wordpress.org/tahhan-jmap-mailer /tmp/postwave-svn

# Trunk volledig ophalen
svn update /tmp/postwave-svn/trunk --set-depth infinity

# Bestanden synchroniseren
rsync -a --delete \
  --exclude='.git' --exclude='.gitignore' --exclude='.claude' \
  --exclude='*.zip' --exclude='.DS_Store' \
  "/Users/mustafa/Tahhan jamp plugin/postwave/" \
  "/tmp/postwave-svn/trunk/"

# Commit trunk
svn commit /tmp/postwave-svn/trunk \
  --username mustafatahhan \
  --password 'svn_CjXreIgUWDPUVitc5CW36gB7SFMAYJBM5b47ec5' \
  --no-auth-cache --non-interactive \
  -m "Release 1.3.x — korte omschrijving"
```

### 7. SVN tag aanmaken
```bash
svn copy \
  https://plugins.svn.wordpress.org/tahhan-jmap-mailer/trunk \
  https://plugins.svn.wordpress.org/tahhan-jmap-mailer/tags/1.3.x \
  --username mustafatahhan \
  --password 'svn_CjXreIgUWDPUVitc5CW36gB7SFMAYJBM5b47ec5' \
  --no-auth-cache --non-interactive \
  -m "Tag 1.3.x"
```

## Bestandsstructuur

```
postwave/
├── tahhan-jmap-mailer.php        ← hoofd bootstrap, versie & constanten
├── readme.txt                    ← WP.org beschrijving + changelog
├── README.md                     ← GitHub beschrijving
├── uninstall.php
├── includes/
│   ├── class-admin.php
│   ├── class-jmap-client.php     ← sessie discovery, API, blob upload
│   ├── class-mailer.php          ← pre_wp_mail hook, JMAP send pipeline
│   ├── class-mail-log.php        ← mail log (max 100 entries)
│   ├── class-retry-queue.php     ← WP-Cron retry met exponential backoff
│   ├── class-open-tracker.php    ← tracking pixel REST endpoint
│   ├── class-account-manager.php ← multi-account opslag
│   ├── class-router.php          ← routing rules engine
│   └── class-integration-woocommerce.php
├── templates/
│   └── page-settings.php         ← admin UI (tabs: General/Connection/Log/Accounts/Routing)
└── assets/
    ├── css/admin.css
    ├── js/admin.js
    └── img/                      ← banner, icon, screenshots (SVG + PNG)
```

## Technische aandachtspunten

- **SVN checkout** staat normaal in `/tmp/postwave-svn/` — controleer of die map bestaat voor de sync-stap.
- **PNG's** zijn gegenereerd vanuit SVG's via `rsvg-convert`. Regenereer na SVG-wijzigingen.
- **WP.org assets** (banner, icon, screenshots) horen in `assets/img/` van de plugin zelf; SVN kopieert ze mee via rsync.
- **PHPCS**: text domain moet overeenkomen met de bestandsnaam (slug `tahhan-jmap-mailer`).
- **Constanten** beginnen met `POSTWAVE_` — niet wijzigen, optie-sleutels in de database zijn hierop gebaseerd.
