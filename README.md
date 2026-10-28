# Jambo County Council

A demo website for a fictional Kenyan county council, built on **TYPO3 v14** with
**no third-party extensions**: core, one site package, and nothing else. It exists
as talk material — to show how far plain TYPO3 gets you, and where it stops.

The site serves three languages or reading levels: English, Swahili, and an
**Easy Read** version for readers who need short sentences and a picture per
point.

![GPL-2.0-or-later](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)

## What is in here

| Path | What it is |
| --- | --- |
| `packages/jambo_theme/` | The site package: templates, site set, SCSS, forms, two content elements, a CLI command. It has [its own README](packages/jambo_theme/README.md) documenting every decision. |
| `config/` | Site configuration (`config/sites/main/`) and TYPO3 settings. |
| `theme/` | The static HTML/CSS design the site was built from, kept for comparison. |
| `Build/db.sql.gz` | The content: pages, all three languages, forms, notices. Without this a clone is an empty TYPO3. |
| `public/fileadmin/` | The images the content points at. |
| `SESSION-TRANSCRIPT.md` | Every prompt and every answer that produced this site, in order. |

## Running it locally

You need [DDEV](https://ddev.readthedocs.io/) and Docker. Nothing else — no local
PHP, no database.

```bash
git clone git@github.com:linawolf/jambo-plain.git
cd jambo-plain
ddev start
ddev composer install
ddev import-db --file=Build/db.sql.gz
```

Two values are deliberately **not** in this repository, so you have to create
them once.

**An encryption key.** TYPO3 signs form state and other tokens with it:

```bash
printf "\n\$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '%s';\n" \
  "$(ddev exec php -r 'echo bin2hex(random_bytes(48));')" >> config/system/additional.php
```

DDEV writes `config/system/additional.php` itself and overwrites it on every
`ddev start` as long as the `#ddev-generated` line is still in the file. Delete
that line, or your key disappears on the next start.

**A backend user**, since no accounts are published:

```bash
ddev typo3 backend:user:create
ddev typo3 referenceindex:update
ddev launch
```

Mail goes nowhere real: both forms deliver to Mailpit, at
`https://jambo-plain.ddev.site:8026` (`ddev launch -m`).

The Swahili language uses the locale `sw_KE.UTF-8`, which the DDEV web image
does not compile. `.ddev/web-build/Dockerfile.locales` compiles it at build
time; without it TYPO3 logs a missing-locale warning on every Swahili request.

## What is not in the repository, and why

* `vendor/`, `var/`, `public/typo3`, `public/_assets` and the `_processed_`
  thumbnails — all generated, all reproducible.
* `config/system/additional.php` — holds the local encryption key, the
  install-tool password and the database credentials.
* **Nobody's personal data.** `be_users`, the session tables, `sys_log`,
  `sys_history` and the waste-report submissions are in the dump as empty
  tables: their structure is published, their rows are not. No name, no mail
  address and no password hash leaves the machine this was built on.

## Before you take it for finished

* The slider photographs and the Easy Read pictograms are placeholders drawn
  with GD, not real pictures.
* Both forms deliver to `@jambo-county.example` addresses.
* `public/robots.txt` and the social-sharing image still name the placeholder
  domain.
* The Easy Read wording has not been checked by a real Easy Read reviewer, which
  is the one part of this that software cannot settle.

## License

GPL-2.0-or-later, the same as TYPO3. Jambo County, its wards, its officers and
every notice on the site are fictional.
