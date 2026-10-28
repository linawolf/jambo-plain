# Jambo County Council — static design

The design the sitepackage demo is built from. Two pages, one stylesheet and
nothing loaded from the network.

The stylesheet is no longer kept here. It is compiled from Sass in the
sitepackage, and these pages link to the compiled result, so the design and the
TYPO3 site can never drift apart.

## What is here

```
index.html            the home page      → page layout "Default"
services.html         a service page     → page layout "Subpage"
assets/img/logo.svg   the emblem
assets/img/favicon.svg

The stylesheet lives in ../packages/jambo_theme (Sass source in
Resources/Private/Scss, compiled to Resources/Public/Css/main.css).
```

The two pages differ in layout on purpose. The home page is a hero, a tile grid
and a notice list over the full width. The service page is a two-column grid
with a section navigation beside the text. That is where the two backend
layouts an editor chooses from come from.

## The design

| Token | Value | Used for |
| ----- | ----- | -------- |
| Brand green | `#06603f` | main navigation, tile rule, emblem |
| Brand dark | `#04452d` | hero background, link colour |
| Accent | `#a8431a` | phase banner tag, callout rule |
| Ink | `#14261f` | body text, utility bar, footer |
| Page | `#f7f5f0` | page background |
| Surface | `#ffffff` | cards, masthead |

The type is a system font stack, so no font is fetched. Every colour pair meets
WCAG AA contrast. The markup carries a skip link, landmark elements, a visible
focus outline, `aria-current` on the active navigation item and a caption and
row headers on the table.

## This council does not exist

Jambo County is fictional, and every page says so in the phase banner and in the
footer. Nothing here copies a real authority's identity, so the folder can be
published with the talk.

## Using it with an agent

Point the agent at this folder rather than at a URL:

> Build a TYPO3 sitepackage for the design in ../jambo-design. Follow its
> colours, its navigation and its two page layouts. The extension key is
> jambo_site, the vendor is jambo.

The design step then reads local files and takes seconds.
