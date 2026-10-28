# Jambo Theme

TYPO3 sitepackage for the Jambo County Council design in `../../theme`.

## Structure

```
ext_localconf.php              registers the Swahili locale with TYPO3
Classes/ViewHelpers/Seo/        StructuredDataViewHelper (JSON-LD)
Configuration/Form/JamboTheme/   makes Resources/Private/Forms readable to EXT:form
Configuration/TCA/Overrides/
    tt_content.php             registers the "Image slider" content element
Configuration/Sets/JamboTheme/
    config.yaml                site set "jambo/theme"
    settings.definitions.yaml  site title, tagline, phase banner
    setup.typoscript           PAGEVIEW, content areas, menus, CSS
    page.tsconfig              the two page layouts
Resources/Private/Templates/
    Pages/Default.html         page layout "default"  - hero, highlights, main
    Pages/Subpage.html         page layout "subpage"  - section nav beside prose
    Layouts/Default.html       the chrome every page shares
    Partials/Chrome/           utility bar, masthead, phase banner, footer
    Partials/Navigation/       main navigation, breadcrumb, section navigation
Resources/Private/ContentElements/
    Templates/                 Fluid templates of our own content elements
    Partials/                  overrides of single Fluid Styled Content partials
Resources/Private/Extensions/
    IndexedSearch/             overridden templates of EXT:indexed_search
Resources/Private/Language/
    locallang.xlf              English labels of the chrome
    sw.locallang.xlf           their Swahili translations
Resources/Private/Scss/        Sass source, one partial per section
Resources/Public/
    Css/main.css               compiled from the Sass, kept in git
    Css/swiper-bundle.min.css  copied from node_modules by "npm run build"
    JavaScript/                image-slider.js plus the copied Swiper bundle
    Icons/                     logo.svg, favicon.svg
```

## Page layouts

`PAGEVIEW` picks `Pages/<PageLayout>.html` from the page layout chosen in the
page properties, and the `page-content` data processor hands every column of
that layout to Fluid as `{content.<identifier>}`.

| Layout    | Columns (identifier / colPos)                             |
| --------- | --------------------------------------------------------- |
| `default` | `hero` / 1, `highlights` / 2, `notices` / 3, `main` / 0   |
| `subpage` | `main` / 0, `sidebar` / 2                                 |
| `easyread`| `main` / 0                                                |
| `notices` | `main` / 0 - an introduction; the list is built by the template |
| `footer`  | `address` / 0, `hours` / 1 (the footer folder, see below)  |

Empty columns render nothing, so a band only appears once an editor puts
content in it.

`highlights` and `notices` wrap every element of the column in markup of their
own - one `li.tile` per element in the tile grid, one `li` per element in the
notice list - with the `recordAs` argument of `f:render.contentArea`. An element
in `notices` shows its *Date* field above the heading.

## Changing the design

The stylesheet is written in Sass under `Resources/Private/Scss/` and compiled
to `Resources/Public/Css/main.css`, which is the only stylesheet in the project:
TypoScript includes it and the static pages in `../../theme` link to it.

```bash
ddev exec -- bash -c 'cd /var/www/html/packages/jambo_theme && npm install'
ddev exec -- bash -c 'cd /var/www/html/packages/jambo_theme && npm run build'   # once
ddev exec -- bash -c 'cd /var/www/html/packages/jambo_theme && npm run watch'   # while working
```

The compiled file stays in version control, so the site works for anyone who
never runs npm. Do not edit it by hand; it carries a banner saying so.

Colours and spacing stay CSS custom properties on `:root` rather than Sass
variables, because they are read at runtime and can be overridden per theme.

### Classes an editor can actually apply

A class in the stylesheet is only usable if the rich text editor can produce
it. Two did not qualify and were renamed or dropped:

* The lead paragraph is `.lead`, not `.lede`. "Lead" is what the Styles
  dropdown of the default RTE preset writes. The old name could not be applied
  from the editor at all, and CKEditor drops a class that is not in its schema,
  so editing such a paragraph would have quietly lost the styling. The preset
  has no `htmlSupport` block, which is what would have had to allow it.
* Tables need no class. `.frame table` styles every table in every content
  element, so a table inserted with the RTE's own table tool is styled
  straight away. `.data-table` is kept only for the static design in
  `../../theme`.

The rule of thumb: before styling a class, check that the Styles dropdown of
the preset in use can write it - otherwise it belongs on an element the theme
renders itself, not in content.

### Callouts

The grey boxes with the accent rule - "Renewing a permit", "Need help
applying?" - are ordinary **text elements** with the frame *Callout*, chosen on
the Appearance tab. `frame_class` is registered in
`Configuration/TCA/Overrides/tt_content.php` and styled as `.frame-callout`.

They started life as elements of type *Plain HTML* holding a hand-written
`<div class="callout">`, because a text element put through `parseFunc` came out
with an empty `<p></p>` in it. That cured the symptom and left an editor
writing markup and remembering a class name - and a heading that no longer
counted as a heading. With the frame, the heading is the normal header field
(so its level comes from the *Type* select, h2 in the main column and h3 in the
sidebar) and the body keeps the rich text editor. The `parseFunc` problem does
not come back, because the bodytext no longer contains a `div`.

`Resources/Private/ContentElements/Partials/` overrides single Fluid Styled
Content partials; it is wired up through the `styles.templates.partialRootPath`
setting in `settings.yaml`. Right now it only holds an empty `Header/Date`
partial, because the notice list prints the date itself.

## Notices

A notice is a **page** below the notices section (`jamboTheme.page.notices`),
so it has an address of its own to link to and to share, lands in the sitemap
and in the search index, and is translated through the page translation
workflow like everything else.

* `tx_jambotheme_notice_date` on `pages` is the date the council published the
  notice. It sits on the Metadata tab of a standard page, next to the abstract.
  The `pages` table has no field for this: `lastUpdated` means something else
  and feeds the sitemap's `lastmod`, and a notice keeps its date whatever is
  edited afterwards.
* The `abstract` of the page is the teaser in the list, and the title is the
  link. Nothing in the list is typed twice.
* Two `database-query` processors order by that date, newest first - not by the
  position in the page tree, which an editor would have to keep sorted by hand:
  `latestNotices` (limited by `jamboTheme.notices.latest`, three) for the band
  on the home page, and `allNotices` for the notices page. The second is
  written out rather than copied with `=<`, because the value of a data
  processor is a service identifier and a reference would reach the registry as
  the literal string `< .70`.
* `Partials/Notices/List.html` renders both. The caller passes the heading
  level: the band sits under an h2, the notices page under its h1, and Fluid
  cannot build a tag name from a variable.
* The entries of `database-query` wrap each row as `.data`, so it is
  `{notice.data.title}`.

Dates use an **ICU pattern**, `d MMMM y`, not a PHP format string. The pattern
takes the month name from the locale of the current language, so Swahili reads
"22 Oktoba 2026"; `format="j F Y"` said "October" in every language. The
`datetime` attribute of `<time>` keeps the ISO format, which is
language-independent on purpose.

### What this is not

There is no pagination, no category, no feed and no archive: the notices page
lists every notice there is. That is fine for a handful and wrong for a few
hundred. At that point the honest answer is EXT:news, which brings all of it -
see the note in the project README.

## Footer

Everything in the footer comes from the content of the site, so a change does
not need a deployment.

| Column         | Source                                                     |
| -------------- | ---------------------------------------------------------- |
| Address        | content element, column *Address* of the footer folder      |
| Services       | the children of `jamboTheme.menu.services` (the services page) |
| The council    | the page list in `jamboTheme.menu.council`                  |
| Opening hours  | content element, column *Opening hours* of the footer folder |
| Bottom row     | the page list in `jamboTheme.menu.legal`                    |

The address and the opening hours used to be labels in `locallang.xlf`, which
meant an editor could not touch them: an office that moves or a counter that
closes for a holiday would have needed a developer and a deployment. They are
now ordinary content elements in a **sysfolder** - "Footer blocks", page id 31,
named by the `jamboTheme.page.footer` setting - with the `footer` page layout,
so its two columns are labelled *Address* and *Opening hours*. Editors change
them in the page module like any other content, and translate them with the
normal translation workflow; `lib.footerAddress` and `lib.footerHours` in
`setup.typoscript` read them back with `select.languageField` set, which is what
switches the language overlay on.

The page module of a sysfolder shows an info box pointing at the list module.
The labelled columns appear underneath it and work as usual.

Each block needs a version per language. Both Swahili and Easy Read are
**strict** languages, so an untranslated element is dropped rather than falling
back to English - which is the right behaviour for a page, and would silently
empty the footer. The *Services* and *The council* columns hang on their menu
being non-empty, heading included: in Easy Read almost no page is translated,
and a heading with nothing under it is worse than no column.

The *Services* column used to render `mainNavigation`, which is not a list of
services: it repeated Services, Departments, Tenders, County assembly, and
three of those four appeared again in the column right next to it. It now lists
the children of the services page.

The utility bar links and the search form are still the static markup of the
design.

## Content element "Image slider"

`Classes/ViewHelpers/Seo/        StructuredDataViewHelper (JSON-LD)
Configuration/Form/JamboTheme/   makes Resources/Private/Forms readable to EXT:form
Configuration/TCA/Overrides/tt_content.php` registers the CType
`jambotheme_imageslider` with `ExtensionManagementUtility::addRecordType()`. The
New Content Element wizard is built from the CType items in TCA, so the element
shows up there without any page TSconfig. Images go into the `assets` field,
which core keeps around for exactly this kind of reuse.

Rendering is Swiper, copied out of `node_modules` into `Resources/Public/` by
`npm run build` - no bundler is involved, the shipped `swiper-bundle.min.js` is
self-contained. The template registers Swiper's CSS and JavaScript through
`f:asset.css` / `f:asset.script`, so they are only sent to browsers on pages
that really hold a slider.

Arrows and pagination are only rendered for more than one slide. Without
JavaScript the markup stays a readable list of `figure` elements.

## Search

Search is EXT:indexed_search. The set `typo3/indexed-search` is a dependency of
`jambo/theme`, and it already switches indexing on (`config.index_enable = 1`),
so a page enters the index the first time it is rendered uncached. A site this
size needs no crawler and no scheduler.

* The plugin sits on the page "Search" (not in the menu, `no_search` set).
  Its id is the `indexedsearch.targetPid` setting, which the masthead form
  reads as well, so form and plugin can not point at different pages.
* `indexedsearch.rootPidList` keeps results inside this site.
* Templates are overridden in `Resources/Private/Extensions/IndexedSearch/`:
  a plain form, and hits as a list that reuses the look of the notice list.
* `<!--TYPO3SEARCH_begin-->` / `<!--TYPO3SEARCH_end-->` in the page templates
  bracket the real content, so masthead, navigation, phase banner, footer and
  the section navigation stay out of the excerpts.

The masthead form uses **POST**. Plugin arguments are cache relevant, so a GET
would need a valid `cHash` and TYPO3 answers a request without one with a 404.

After changing content, the page has to be rendered once uncached before the
index knows about it. To rebuild from scratch, empty the `index_*` tables and
visit the pages again.

## Languages

The site runs English (`en_US.UTF-8`) and Swahili (`sw_KE.UTF-8`), both with
`fallbackType: strict`: a page exists under `/sw/` only when it really has a
translation, everything else is a 404 there. The language menu in the utility bar is built from the `language-menu` data
processor. A language the current page does not exist in is still offered - a
visitor on an English-only page must not be stuck without a way over - but the
link then points at that language's home page instead of at a 404, is marked
with `--fallback`, and carries an `aria-label` saying so. This assumes the site
root is translated; it is the one page that always should be.

**TYPO3 does not know Swahili out of the box.** Its locale registry has no `sw`,
so the site language cannot be created, no label file is looked up, and the
whole chrome silently stays English. `ext_localconf.php` registers it:

```php
$GLOBALS['TYPO3_CONF_VARS']['SYS']['localization']['locales']['user']['sw'] = 'Swahili';
```

Registering the base language is what makes the labels resolve; `sw_KE` falls
back to `sw`, which is why a single `sw.locallang.xlf` serves the `sw-KE` site
language.

Separately, the **operating system** needs the locale too. PHP's `setlocale()`
is not TYPO3's registry: the DDEV image ships no compiled Swahili locale, so
every Swahili request logged `Locale "sw_KE.UTF-8" and "sw_KE" not found.`
`.ddev/web-build/Dockerfile.locales` compiles it at image build time. On a
server, `locale-gen sw_KE.UTF-8` does the same job.

Text that has to read differently per language lives in the label file, not in
site settings - settings are one value per site, not per language. Only
`jamboTheme.siteTitle` is still a setting, because the name of the authority
stays the same in both languages.

A translated page needs its page layout set as well. `backend_layout` is part
of the page overlay, so a translation without it resolves to no layout at all:
the template still renders, but every content area comes back empty.

## SEO

* `<title>` is "<page> - <website title>"; the website title comes from the site
  configuration, so it follows the site language.
* Meta descriptions are page properties, filled for every content page.
* Open Graph and Twitter defaults are TypoScript (`page.meta`), so a shared link
  has a preview even on a page where no SEO field was filled.
* JSON-LD is rendered by `StructuredDataViewHelper`: the authority, plus a
  BreadcrumbList on pages below the root. It is a PHP class on purpose - Fluid
  reads every `{` of a JSON document as an expression, and escaping each brace
  makes the template unreadable, while `json_encode()` gets the escaping of
  editable titles right by itself.
* The search pages carry `no_index`, which also drops them from the sitemap.
* `public/robots.txt` is a static file: the nginx of the DDEV image answers
  `/robots.txt` from its own location block, so a TypoScript page type never
  gets the request. Its `Sitemap:` lines name the domain and have to be
  updated per environment.
* The first slide of an image slider is the usual Largest Contentful Paint
  element, so it loads with `loading="eager"` and `fetchpriority="high"` while
  the slides behind it stay lazy. All slides carry a `srcset`.
* The main column of a subpage stands before the section navigation in the
  source, so a page begins with its `h1`.
* 404s are handled by the site configuration and rendered by the page "Page not
  found" (uid 10), which is translated, so the error page keeps the chrome and
  the language of the request.
* The Open Graph image is a purpose-drawn 1200x630 file in
  `fileadmin/social/`. `og:image:width`, `:height` and `:alt` are not set:
  `page.meta` hands the meta tag manager an empty sub-property array, so those
  can only be set through the MetaTagManager API from PHP.

## Accessibility

* The focus ring is `--focus`, and the dark bands (utility bar, navigation,
  hero, footer, slider) redefine that token to white. The dark value of the
  design reaches 1.1:1 on the ink bar and 2.3:1 on the green navigation, so a
  keyboard user could not see where they were; white reaches 7.6:1 at worst.
* `.visually-hidden` carries the names of the slider arrows in the markup, so
  they mean something before Swiper runs. The same strings are handed to Swiper
  through `data-prev-label` / `data-next-label`, so the `aria-label` it injects
  is in the language of the page.
* A `prefers-reduced-motion` block damps every transition, which also reaches
  Swiper's stylesheet - it brings no such rule of its own.
* The chrome links come from `special = list` menus over page ids kept in the
  settings, with `includeNotInMenu = 1` because those pages are hidden from the
  main navigation on purpose. A page missing in the current language simply
  drops out of the list rather than linking into a 404.
* Not verified here: no browser in this environment, so nothing was driven with
  a keyboard or a screen reader. Swiper runs in loop mode, which clones slides;
  whether those clones reach the accessibility tree needs a real pass.

## Contact form

EXT:form, with the definition versioned in the sitepackage rather than living in
fileadmin: `Resources/Private/Forms/Contact.form.yaml`. TYPO3 v14.2 discovers
form configuration automatically from `Configuration/Form/<SetName>/config.yaml`
- the TypoScript registration through `plugin.tx_form.settings.yamlConfigurations`
is deprecated - and that file declares the sitepackage path under
`persistenceManager.allowedExtensionPaths`.

One definition serves both languages. Labels, the submit button, the e-mail
subject and the confirmation text are looked up in
`Resources/Private/Language/Form/contact.xlf` and its `sw.` counterpart. The key
chain is not obvious:

* an element label is `element.<elementIdentifier>.properties.label`
* a finisher option is `finisher.<finisherIdentifier>.<optionKey>`
* the submit button is `element.<formIdentifier>.renderingOptions.submitButtonLabel`,
  because the form itself is the element whose rendering option is translated
* a validation message is `validation.error.<elementIdentifier>.<errorCode>`,
  where the code is the one the validator passes to `addError()`:
  `1221560718` for NotEmpty on an empty string, `1221560910` for null,
  `1221559976` for EmailAddress

The plugin needs the `typo3/form` set in the site set dependencies; without it
the content element renders nothing at all.

The `Page` element carries **no** label. EXT:form renders it as an `<h2>`, and
the content element above the form already has a heading the editor controls -
two headings saying nearly the same thing is what came out of the first build.

### Error messages

Without a `validation.error.*` key, a visitor gets the Extbase default: *"The
given subject was empty."* - which names neither the field nor the remedy, and
reads as nonsense on a form with no subject field. Every required field here
has its own message in both languages.

Two details were wrong in the first build and are easy to repeat:

* **EXT:form does not derive the HTML `required` attribute from the NotEmpty
  validator.** It comes from `properties.fluidAdditionalAttributes.required`,
  which the form editor writes but a hand-written YAML has to spell out. A
  select with an empty first option and no `required` submits happily, and only
  the server objects - which is exactly how a visitor ends up reading that
  Extbase default message.
* **The error summary has to translate the errors itself.** Printing `{error}`
  from the validation results gives the untranslated default; the translated
  text comes from `formvh:translateElementError(element: element, error: error)`,
  which needs the element, so the summary in
  `Resources/Private/Extensions/Form/Templates/Form.fluid.html` walks the
  elements of the page rather than the flattened errors. Each entry links to
  `#<element.uniqueIdentifier>`, except the hidden submission guard. If no
  error belongs to a visible element - a tripped honeypot points at a field
  name that no longer exists - a generic sentence takes their place, so the box
  is never empty.

The default templates of EXT:form use Bootstrap class names (`is-invalid`,
`invalid-feedback`). This theme does not load Bootstrap, so `_form.scss` styles
those names itself. Half-adopting them would be worse than ignoring them:
`.invalid-feedback` is `display: none` in Bootstrap until a sibling is invalid.

### Spam protection

Three layers, none of which costs a visitor anything and none of which sends
data to a third party:

1. the honeypot of EXT:form, on by default. Its partial is overridden in
   `Resources/Private/Extensions/Form/Partials/` to add `autocomplete="off"`:
   browsers fill hidden-by-css text fields, and a visitor using autofill would
   otherwise trip the honeypot and lose their message without a word.
2. `SubmissionGuard`, a hidden field holding the render time, signed with
   `HashService` so it cannot be back-dated. `SubmissionGuardValidator` refuses
   a submission that comes back faster than a person could type.
3. the same validator asks a rate limiter built with the `RateLimiterFactory`
   of the core - no extension, no service - and refuses after five submissions
   per address and hour.

Both refusals answer with an ordinary validation error, listed in the summary
at the top of the form, and both messages are translated.

`ext_localconf.php` re-registers the form plugin with the `render` action
non-cacheable. EXT:form marks only `perform`, so the rendered form comes out of
the page cache and every visitor would receive the same, ever older timestamp -
the timing check would measure the age of the cache entry, not of the visit.
The cost is that a page holding a form is no longer fully cached.

The rate limit counts by address. Mobile carriers put many people behind one
address, so five an hour is deliberately loose; lower it only with evidence.

The form framework emits Bootstrap-like class names. `_form.scss` gives those
few classes the look of the site instead of pulling in a CSS framework.

## Easy Read

A third site language (id 2, base `/easy/`), for people with a learning
disability and for readers whose English is not strong.

* `locale: en_US.UTF-8`, because "easy" is a register of English and not a
  language: date formatting and PHP's `setlocale()` keep working.
* `hreflang: en-x-easy`, set explicitly. Two site languages must not both
  claim `en-US` - that is an invalid hreflang set. The private-use subtag says
  "English, a variant" without claiming to be another language.
* `fallbackType: strict`: only pages that were really rewritten exist here. A
  hard page under an easy address would defeat the point, and the language menu
  already offers the easy home page instead of a dead link.

Content is kept in **free mode** - the records carry the language but no
`l18n_parent`. An Easy Read page is not this element and then that element
translated; it is its own, shorter set of points.

The page layout `easyread` has one column and the template drops the section
navigation and the sidebar: an Easy Read page offers one path, not a set of
choices beside the text. `.easy-page` narrows the measure to 46ch, raises the
size to 1.3125rem and the line height to 1.7, and never justifies.

The content element "Easy Read point" holds one short point and one picture.
The picture carries an empty `alt`: it repeats what the text beside it says,
and a screen reader reading both would say everything twice.

**The pictograms are placeholders**, drawn with GD. Real Easy Read uses a
tested symbol set, and the pictures need the same validation as the words.

## Missed waste collection

A report form whose submissions are **collected**, not mailed one by one, and a
command that mails the collection once a day as a CSV.

* `Resources/Private/Forms/WasteReport.form.yaml` - ward, street, the day,
  the kind of waste, optional details, and how to reach the reporter. It uses
  the same `SubmissionGuard` as the contact form, with its own rate limit
  bucket so one form cannot lock out the other.
* The only finishers are `SaveToDatabase` and a confirmation. No mail per
  report: that is the point of the daily list.
* `tx_jambotheme_wastereport` holds the reports. `ext_tables.sql` spells out
  `uid` and the primary key: the schema analyzer added pid, tstamp, crdate and
  deleted from the TCA but no uid, and the digest needs a key. The TCA is
  `readOnly`, so the records can be read in the backend but not typed there.
* `jambo:waste:digest` mails everything with `digest_sent_at = 0`, then marks
  it. It sends after a successful mail, never before, so a failing mail server
  means the reports go out with the next run instead of disappearing.
* The same command deletes reports **90 days** after they were reported. The
  retention rule rides along with the digest deliberately: one cron line that
  cannot be half-configured, so there is no way to end up mailing the reports
  daily while keeping them forever.

```bash
ddev typo3 jambo:waste:digest --dry-run   # print the CSV, delete nothing
ddev typo3 jambo:waste:digest             # send, mark, delete the expired ones
ddev typo3 jambo:waste:digest -r team@example.org
ddev typo3 jambo:waste:digest --retention-days=30
ddev typo3 jambo:waste:digest --retention-days=0   # keep everything
```

Run it once a day from cron:

```
0 6 * * * /var/www/html/vendor/bin/typo3 jambo:waste:digest
```

The command is registered as `schedulable`, so it can also be clicked together
as a task if EXT:scheduler is installed - that extension is not a dependency
here.

The CSV starts with a byte order mark, so a spreadsheet program opens the
Swahili characters correctly instead of showing mojibake.

### Retention

A report carries a name, an email address, a telephone number and an address,
so the records are **deleted outright**, not flagged as deleted: a flagged row
still holds the data. Three details of the rule are deliberate.

* The clock runs from `crdate`, when the report was made - not from the day it
  was mailed. "Ninety days" is how long the county keeps the data, whatever
  happened to it in between.
* The cutoff is midnight of the day the period ran out, so two runs on the same
  day delete the same records.
* A report that has **never** been mailed is kept past its date, and the
  command says so loudly. If the digest mail has been failing for three months,
  deleting the evidence is the wrong half of the job to automate. Once such a
  report does go out, the run that mails it also deletes it.

`--retention-days` overrides the period for a single run; `0` keeps everything.
Changing the rule for good means changing `RETENTION_DAYS` in the command, so
the default in the code stays the one documented figure.
