<?php
/**
 * XSS Prevention class
 *
 * @category   JawsType
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     David Coallier <david@echolibre.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2017 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_XSS
{
    /**
     * Allowed HTML tags
     *
     * @var     array
     * @access  private
     */
    private $allowed_tags = '<body><br><a><img><ol><ul><li><blockquote><cite><code><div><p><pre><span><del><ins>
        <strong><b><mark><i><s><u><em><strike><table><tbody><thead><tfoot><th><tr><td><font><center>';

    /**
     * Allowed HTML tag attributes
     *
     * @var array
     * @access  private
     */
    private $allowed_attributes = array(
        'href', 'src', 'alt', 'title', 'style', 'class', 'dir',
        'height', 'width', 'rowspan', 'colspan', 'align', 'valign',
        'rows', 'cols', 'color', 'bgcolor', 'border'
    );

    /**
     * Allowed HTML entities
     *
     * @var array
     * @access  private
     */
    private $allowed_entities = array(
        '/&nbsp;/', '/&cent;/', '/&pound;/', '/&yen;/', '/&euro;/', '/&copy;/', '/&reg;/'
    );

    /**
     *  URL based HTML tag attributes
     *
     * @var     array
     * @access  private
     */
    private $urlbased_attributes = array('href', 'src');

    /**
     * Allowed URL pattern
     *
     * @var     string
     * @access  private
     */
    private $allowed_url_pattern = "@(^[(http|https|ftp)://]?)(?!javascript:)([^\\\\[:space:]\"]+)$@iu";

    /**
     * Allowed style pattern
     *
     * @var     string
     * @access  private
     */
    private $allowed_style_pattern = array(
        '/^(',
        '(\s*(background\-)?color\s*:\s*#[0-9A-Fa-f]+[\s|;]*)',
        '|',
        '((\s*(width)|(height)|(margin\-left)|(margin\-right)|(font\-size))\s*:\s*\d+((px)|(em)|(pt))?[\s|;]*)',
        '|',
        '((\s*(text\-align))\s*:\s*((left)|(right)|(center)|(justify))?[\s|;]*)',
        ')+$/',
    );

    /**
     * Constructor
     *
     * @access  public
     * @return  void
     */
    private function __construct()
    {
        // join pattern parts together
        $this->allowed_style_pattern = implode('', $this->allowed_style_pattern);
    }

    /**
     * Creates the Jaws_Request instance if it doesn't exist else it returns the already created one
     *
     * @access  public
     * @return  object returns the instance
     */
    static function getInstance()
    {
        static $objRequest;
        if (!isset($objRequest)) {
            $objRequest = new Jaws_XSS();
        }

        return $objRequest;
    }

    /**
     * Parses the text
     *
     * @access  public
     * @param   string $string String to parse
     * @param   bool   $strict How strict we can be. True will be very strict (default), false
     *                         will allow some attributes (id) and tags (object, applet, embed)
     * @return  string The safe string
     */
    static function parse($string, $strict = null)
    {
        static $safe_xss;
        static $xss_parsing_level;
        if (!isset($safe_xss)) {
            $xss_parsing_level = $GLOBALS['app']->Registry->fetch('xss_parsing_level', 'Policy');

            //Create safe html object
            require_once PEAR_PATH. 'HTML/Safe.php';
            $safe_xss = new HTML_Safe();
        }

        if (is_null($strict)) {
            $strict = ($xss_parsing_level == "paranoid");
        }

        $string = $safe_xss->parse($string, $strict);
        $safe_xss->clear();
        return $string;
    }

    /**
     * Parses the text
     *
     * @access  public
     * @param   string $string String to parse
     * @param   bool   $strict How strict we can be. True will be very strict (default), false
     *                         will allow some attributes (id) and tags (object, applet, embed)
     * @return  string The safe string
     */
    function strip($text)
    {
        $result = '';
        // eliminate DOCTYPE|head|style|script tags
        $text = preg_replace(
            array(
                '@<\!DOCTYPE\s.*?>@sim',
                '@<head[^>]*>.*?</head>@sim',
                '@<style[^>]*>.*?</style>@sim',
                '@<script[^>]*>.*?</script>@sim'
            ),
            '',
            $text
        );
        // strip not allowed tags
        $text = strip_tags($text, $this->allowed_tags);
        // escape special characters
        $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
        $text = Jaws_UTF8::str_replace(array('&lt;', '&gt;'), array('<', '>'), $text);

        $hxml = simplexml_load_string(
            '<?xml version="1.0" encoding="UTF-8"?><html>'. $text .'</html>',
            'SimpleXMLElement',
            LIBXML_COMPACT | LIBXML_NOERROR
        );
        if ($hxml) {
            foreach ($hxml->xpath('descendant::*[@*]') as $tag) {
                $attributes = (array)$tag->attributes();
                foreach ($attributes['@attributes'] as $attrname => $attrvalue) {
                    // strip not allowed attributes
                    if (!in_array(strtolower($attrname), $this->allowed_attributes)) {
                        unset($tag->attributes()->{$attrname});
                        continue;
                    }
                    // url based attributes
                    if (in_array(strtolower($attrname), $this->urlbased_attributes)) {
                        if (!preg_match($this->allowed_url_pattern, $attrvalue)) {
                            unset($tag->attributes()->{$attrname});
                            continue;
                        }
                    }
                    // style attribute
                    if (strtolower($attrname) == 'style') {
                        if (!preg_match($this->allowed_style_pattern, $attrvalue)) {
                            unset($tag->attributes()->{$attrname});
                            continue;
                        }
                    }
                }
            }

            // remove xml/html tags
            $result = substr($hxml->asXML(), 45, -8);
        }

        return htmlspecialchars_decode($result, ENT_NOQUOTES);
    }


    /**
     * Convert special characters to HTML entities
     *
     * @access  public
     * @param   string  $string     The string being converted
     * @param   bool    $noquotes   Will leave both double and single quotes unconverted
     * @return  string  The converted string
     */
    static function filter($string, $noquotes = false)
    {
        return htmlspecialchars($string, $noquotes? ENT_NOQUOTES : ENT_QUOTES, 'UTF-8');
    }


    /**
     * Convert special HTML entities back to characters
     *
     * @access  public
     * @param   string  $string     The string to decode
     * @param   bool    $noquotes   Will leave both double and single quotes unconverted
     * @return  string  Returns the decoded string
     */
    static function defilter($string, $noquotes = false)
    {
        return htmlspecialchars_decode($string, $noquotes? ENT_NOQUOTES : ENT_QUOTES);
    }

    /**
     * Convert special characters to HTML entities
     *
     * @access  public
     * @param   string  $string     The string to decode
     * @param   bool    $noquotes   Will leave both double and single quotes unconverted
     * @return  string  Returns the decoded string
     */
    static function refilter($string, $noquotes = false)
    {
        return self::filter(self::defilter($string, $noquotes), $noquotes);
    }

}