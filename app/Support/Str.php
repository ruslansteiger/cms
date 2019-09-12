<?php

namespace Statamic\Support;

use Statamic\Facades\Compare;
use Stringy\StaticStringy;

/**
 * Manipulating strings
 */
class Str extends \Illuminate\Support\Str
{
    public function __call($method, $parameters)
    {
        return call_user_func_array([StaticStringy::class, $method], $parameters);
    }

    /**
     * Creates a sentence list from the given $list
     *
     * @param array  $list  List of items to list
     * @param string  $glue  Joining string before the last item when more than one item
     * @param bool  $oxford_comma  Include a comma before $glue?
     * @return string
     */
    public function makeSentenceList(Array $list, $glue = "and", $oxford_comma = true)
    {
        $length = count($list);

        switch ($length) {
            case 0:
            case 1:
                return join("", $list);
                break;

            case 2:
                return join(" " . $glue . " ", $list);
                break;

            default:
                $last = array_pop($list);
                $sentence  = join(", ", $list);
                $sentence .= ($oxford_comma) ? "," : "";

                return $sentence . " " . $glue . " " . $last;
        }
    }

    public function stripTags($html, $tags_list = [])
    {
        if (count($tags_list) > 0) {
            $all_tags = [
                "a", "abbr", "acronym", "address", "applet",
                "area", "article", "aside", "audio", "b",
                "base", "basefont", "bdi", "bdo", "big",
                "blockquote", "body", "br", "button", "canvas",
                "caption", "center", "cite", "code", "col",
                "colgroup", "command", "data", "datagrid", "datalist",
                "dd", "del", "details", "dfn", "dir", "div", "dl",
                "dt", "em", "embed", "eventsource", "fieldset",
                "figcaption", "figure", "font", "footer", "form",
                "frame", "frameset", "h1", "h2", "h3", "h4", "h5",
                "h6", "head", "header", "hgroup", "hr", "html", "i",
                "iframe", "img", "input", "isindex", "ins", "kbd",
                "keygen", "label", "legend", "li", "link", "main",
                "mark", "map", "menu", "meta", "meter", "nav",
                "noframes", "noscript", "object", "ol", "optgroup",
                "option", "output", "p", "param", "pre", "progress",
                "q", "ruby", "rp", "rt", "s", "samp", "script",
                "section", "select", "small", "source", "span",
                "strike", "strong", "style", "sub", "summary", "sup",
                "table", "tbody", "td", "textarea", "tfoot", "th",
                "thead", "time", "title", "tr", "track", "tt", "u",
                "ul", "var", "video", "wbr"
            ];

            $allowed_tags = array_diff($all_tags, $tags_list);
            $allowed_tag_string = "<" . join("><", $allowed_tags) . ">";

            return strip_tags($html, $allowed_tag_string);
        }

        return strip_tags($html);
    }

    public static function studlyToSlug($string)
    {
        return Str::slug(Str::snake($string));
    }

    public static function studlyToTitle($string)
    {
        return Str::modifyMultiple($string, ['snake', 'slugToTitle']);
    }

    public static function slugToTitle($string)
    {
        return Str::modifyMultiple($string, ['deslugify', 'title']);
    }

    public static function isUrl($string)
    {
        return self::startsWith($string, ['http://', 'https://', '/']);
    }

    public static function deslugify($string)
    {
        return str_replace(['-', '_'], ' ', $string);
    }

    /**
     * Get the human file size of a given file.
     *
     * @param int $bytes
     * @param int $decimals
     * @return string
     */
    public static function fileSizeForHumans($bytes, $decimals = 2)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, $decimals) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, $decimals) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, $decimals) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' B';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' B';
        } else {
            $bytes = '0 B';
        }

        return $bytes;
    }

    public function timeForHumans($ms)
    {
        if ($ms < 1000) {
            return $ms . 'ms';
        }

        return round($ms / 1000, 2) . 's';
    }

    /**
     * Attempts to prevent widows in a string by adding a
     * &nbsp; between the last two words of each paragraph.
     *
     * @param string $value
     * @return string
     */
    public function widont($value)
    {
        // thanks to Shaun Inman for inspiration here
        // http://www.shauninman.com/archive/2008/08/25/widont_2_1_1

        // if there are content tags
        if (preg_match("/<\/(?:p|li|h1|h2|h3|h4|h5|h6|figcaption)>/ism", $value)) {
            // step 1, replace spaces in HTML tags with a code
            $value = preg_replace_callback("/<.*?>/ism", function($matches) {
                return str_replace(' ', '%###%##%', $matches[0]);
            }, $value);

            // step 2, replace last space with &nbsp;
            $value = preg_replace("/(?<!<[p|li|h1|h2|h3|h4|h5|h6|div|figcaption])([^\s])[ \t]+([^\s]+(?:[\s]*<\/(?:p|li|h1|h2|h3|h4|h5|h6|div|figcaption)>))$/im", "$1&nbsp;$2", rtrim($value));

            // step 3, re-replace the code from step 1 with spaces
            return str_replace("%###%##%", " ", $value);

            // otherwise
        } else {
            return preg_replace("/([^\s])\s+([^\s]+)\s*$/im", "$1&nbsp;$2", rtrim($value));
        }
    }

    /**
     * Compare two strings
     *
     * @param  string  $str1  First string to compare.
     * @param  string  $str2  Second string to compare.
     */
    public static function compare($str1, $str2)
    {
        return Compare::strings($str1, $str2);
    }

    /**
     * Apply multiple string modifications via array.
     *
     * @param string $string
     * @param array $modifications
     * @return string
     */
    public static function modifyMultiple($string, $modifications)
    {
        foreach ($modifications as $modification) {
            $string = is_callable($modification)
                ? $modification($string)
                : self::$modification($string);
        }

        return $string;
    }

    public static function tailwindWidthClass($width)
    {
        $widths = [
            25 => '1/4',
            33 => '1/3',
            50 => '1/2',
            66 => '2/3',
            75 => '3/4',
            100 => 'full'
        ];

        $class = $widths[$width] ?? 'full';

        return "w-$class";
    }

    public static function bool($string)
    {
        return ((bool) $string) ? 'true' : 'false';
    }
}
