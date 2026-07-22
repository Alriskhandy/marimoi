<?php

namespace App\Support;

class HtmlSanitizer
{
    protected static ?\HTMLPurifier $purifier = null;

    /**
     * Sanitize user/import-supplied HTML so it can be rendered safely,
     * keeping basic styling (tables, colors, fonts) while stripping
     * scripts, event handlers, and other XSS vectors.
     */
    public static function clean(mixed $html): string
    {
        if (!is_scalar($html) || blank($html)) {
            return '';
        }

        return static::purifier()->purify((string) $html);
    }

    protected static function purifier(): \HTMLPurifier
    {
        if (static::$purifier !== null) {
            return static::$purifier;
        }

        $cacheDir = storage_path('framework/cache/htmlpurifier');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $cacheDir);
        $config->set('HTML.Allowed', implode(',', [
            'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
            'p', 'br', 'b', 'strong', 'i', 'em', 'u', 'span', 'div',
            'ul', 'ol', 'li', 'a[href|title]', 'img[src|alt|width|height]',
        ]));
        $config->set('CSS.AllowedProperties', [
            'color', 'background', 'background-color', 'font-family',
            'font-size', 'font-weight', 'text-align', 'width', 'height',
            'border', 'border-collapse', 'border-spacing', 'padding', 'margin',
        ]);
        $config->set('HTML.AllowedAttributes', implode(',', [
            'table.style', 'table.width', 'table.border', 'table.cellpadding', 'table.cellspacing',
            'tr.style', 'tr.bgcolor',
            'td.style', 'td.bgcolor', 'td.colspan', 'td.rowspan',
            'th.style', 'th.bgcolor', 'th.colspan', 'th.rowspan',
            'span.style', 'div.style',
            'a.href', 'a.title', 'a.target',
            'img.src', 'img.alt', 'img.width', 'img.height',
        ]));
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.TargetBlank', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);

        return static::$purifier = new \HTMLPurifier($config);
    }
}
