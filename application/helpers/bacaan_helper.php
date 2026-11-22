<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sanitizer HTML berbasis parser DOM (bukan regex), whitelist tag/atribut.
 * Dipakai untuk bacaan/stimulus dan teks soal.
 */
if (!function_exists('_html_aman_tag_berbahaya')) {
    function _html_aman_tag_berbahaya()
    {
        return array(
            'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'textarea',
            'button', 'select', 'option', 'link', 'meta', 'base', 'svg', 'math',
            'template', 'noscript', 'plaintext', 'xmp', 'marquee', 'applet', 'frame',
            'frameset', 'bgsound', 'audio', 'video', 'source', 'track', 'canvas',
        );
    }
}

if (!function_exists('_html_aman_tag_diizinkan')) {
    function _html_aman_tag_diizinkan($allow_img = false)
    {
        $tag = array(
            'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'ul', 'ol', 'li',
            'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'thead', 'tbody',
            'tfoot', 'tr', 'th', 'td', 'hr', 'span', 'div', 'sup', 'sub', 'small',
            'code', 'pre', 'figure', 'figcaption', 'a',
        );
        if ($allow_img) {
            $tag[] = 'img';
        }
        return $tag;
    }
}

if (!function_exists('_html_aman_url')) {
    function _html_aman_url($url)
    {
        $url = trim((string) $url);
        $url = preg_replace('/[\x00-\x1F\x7F]/u', '', $url);
        if ($url === '') {
            return '';
        }
        // Skema yang diizinkan saja; javascript:, data:text/html, vbscript: ditolak.
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (preg_match('#^data:image/(png|jpe?g|gif|webp|bmp);base64,#i', $url)) {
            return $url;
        }
        if (preg_match('#^(/?|\./)?uploads/#i', $url) || $url[0] === '#') {
            return $url;
        }
        return '';
    }
}

if (!function_exists('_html_aman_atribut')) {
    function _html_aman_atribut($el, $tag, $allow_img)
    {
        $boleh = array('class', 'title', 'align', 'width', 'height', 'colspan', 'rowspan', 'alt');
        if ($tag === 'a') {
            $boleh[] = 'href';
        }
        if ($tag === 'img') {
            $boleh[] = 'src';
        }

        $atribut = array();
        foreach ($el->attributes as $attr) {
            $nama = strtolower($attr->nodeName);
            if (!in_array($nama, $boleh, true)) {
                continue;
            }
            $nilai = (string) $attr->nodeValue;
            if ($nama === 'href' || $nama === 'src') {
                $nilai = _html_aman_url($nilai);
                if ($nilai === '') {
                    continue;
                }
            }
            $atribut[$nama] = $nilai;
        }

        while ($el->attributes->length > 0) {
            $el->removeAttribute($el->attributes->item(0)->nodeName);
        }
        foreach ($atribut as $nama => $nilai) {
            $el->setAttribute($nama, $nilai);
        }
        if ($tag === 'a') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }
        if ($tag === 'img' && !$el->hasAttribute('src')) {
            return false;
        }
        return true;
    }
}

if (!function_exists('_html_aman_bersihkan_node')) {
    function _html_aman_bersihkan_node($node, $allow_img)
    {
        $berbahaya = _html_aman_tag_berbahaya();
        $diizinkan = _html_aman_tag_diizinkan($allow_img);

        $anak = array();
        foreach ($node->childNodes as $child) {
            $anak[] = $child;
        }

        foreach ($anak as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower($child->nodeName);
            if (in_array($tag, $berbahaya, true)) {
                $node->removeChild($child);
                continue;
            }
            if (!in_array($tag, $diizinkan, true)) {
                // Tag tidak dikenal: buang tag, pertahankan isinya.
                _html_aman_bersihkan_node($child, $allow_img);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            if ($tag === 'img' && !$allow_img) {
                $node->removeChild($child);
                continue;
            }
            if (_html_aman_atribut($child, $tag, $allow_img) === false) {
                $node->removeChild($child);
                continue;
            }
            _html_aman_bersihkan_node($child, $allow_img);
        }
    }
}

if (!function_exists('_html_aman_parser')) {
    function _html_aman_parser($html, $allow_img = false)
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $lama = libxml_use_internal_errors(true);
        $ok = $doc->loadHTML(
            '<?xml encoding="utf-8" ?><div>' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($lama);
        if (!$ok || !$doc->documentElement) {
            return '';
        }

        // Buang processing instruction dari trik encoding.
        foreach (iterator_to_array($doc->childNodes) as $n) {
            if ($n->nodeType === XML_PI_NODE) {
                $doc->removeChild($n);
            }
        }

        _html_aman_bersihkan_node($doc->documentElement, $allow_img);

        $out = '';
        foreach ($doc->documentElement->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return $out;
    }
}

if (!function_exists('bacaan_isi_aman')) {
    function bacaan_isi_aman($html)
    {
        return _html_aman_parser($html, false);
    }
}

if (!function_exists('soal_html_aman')) {
    function soal_html_aman($html)
    {
        return _html_aman_parser($html, true);
    }
}
