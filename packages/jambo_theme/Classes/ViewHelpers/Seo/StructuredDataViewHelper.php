<?php

declare(strict_types=1);

namespace Jambo\Theme\ViewHelpers\Seo;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders the JSON-LD of the site: the authority itself, and the breadcrumb
 * trail of the current page.
 *
 * Building this in Fluid is not practical - a Fluid template reads every "{" as
 * the start of an expression, and escaping each brace of a JSON document makes
 * it unreadable. Handing the data to json_encode() also gets the escaping of
 * editable titles right for free.
 */
final class StructuredDataViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('siteTitle', 'string', 'Name of the authority', true);
        $this->registerArgument('baseUrl', 'string', 'Absolute URL of the site root', true);
        $this->registerArgument('logoUrl', 'string', 'Absolute URL of the emblem', true);
        $this->registerArgument('areaServed', 'string', 'Area the authority serves', false, '');
        $this->registerArgument('breadcrumb', 'array', 'Rootline as built by the menu processor', false, []);
    }

    public function render(): string
    {
        $baseUrl = (string)$this->arguments['baseUrl'];

        $documents = [[
            '@context' => 'https://schema.org',
            '@type' => 'GovernmentOrganization',
            'name' => (string)$this->arguments['siteTitle'],
            'url' => $baseUrl,
            'logo' => (string)$this->arguments['logoUrl'],
        ]];
        if (($this->arguments['areaServed'] ?? '') !== '') {
            $documents[0]['areaServed'] = (string)$this->arguments['areaServed'];
        }

        $breadcrumb = $this->arguments['breadcrumb'] ?? [];
        // A single entry is just the current page and says nothing worth marking up.
        if (is_array($breadcrumb) && count($breadcrumb) > 1) {
            $items = [];
            foreach (array_values($breadcrumb) as $position => $crumb) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $position + 1,
                    'name' => (string)($crumb['title'] ?? ''),
                    'item' => $this->absoluteUrl((string)($crumb['link'] ?? ''), $baseUrl),
                ];
            }
            $documents[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $items,
            ];
        }

        $output = '';
        foreach ($documents as $document) {
            $json = json_encode(
                $document,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
            $output .= '<script type="application/ld+json">' . $json . '</script>' . LF;
        }
        return $output;
    }

    private function absoluteUrl(string $link, string $baseUrl): string
    {
        if ($link === '' || str_starts_with($link, 'http://') || str_starts_with($link, 'https://')) {
            return $link;
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($link, '/');
    }
}
