<?php
/**
 * phpQuery is a server-side, chainable, CSS3 selector driven
 * Document Object Model (DOM) API based on jQuery JavaScript Library.
 *
 * @version 0.9.5
 * @link    http://code.google.com/p/phpquery/
 * @link    http://phpquery-library.blogspot.com/
 * @link    http://jquery.com/
 * @author  Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @package phpQuery
 */

// -- Multibyte Compatibility functions ----------------------------------------
// http://svn.iphonewebdev.com/lace/lib/mb_compat.php
/**
 *  mb_internal_encoding()
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_internal_encoding')) {
    function mb_internal_encoding($enc)
    {
        return true;
    }
}
/**
 *  mb_regex_encoding()
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_regex_encoding')) {
    function mb_regex_encoding($enc)
    {
        return true;
    }
}
/**
 *  mb_strlen()
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_strlen')) {
    function mb_strlen($str)
    {
        return strlen($str);
    }
}
/**
 *  mb_strpos()
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_strpos')) {
    function mb_strpos($haystack, $needle, $offset = 0)
    {
        return strpos($haystack, $needle, $offset);
    }
}
/**
 *  mb_stripos()
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_stripos')) {
    function mb_stripos($haystack, $needle, $offset = 0)
    {
        return stripos($haystack, $needle, $offset);
    }
}
/**
 *  mb_substr()
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_substr')) {
    function mb_substr($str, $start, $length = 0)
    {
        return substr($str, $start, $length);
    }
}
/**
 *  mb_substr_count()
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_substr_count')) {
    function mb_substr_count($haystack, $needle)
    {
        return substr_count($haystack, $needle);
    }
}

// -- Callback class list ----------------------------------------

/**
 * Interface ICallbackNamed
 */
interface ICallbackNamed
{
    /**
     * Check Callback Name
     *
     * @return bool
     */
    public function hasName();

    /**
     * Get Callback Name
     *
     * @return string
     */
    public function getName();
}

/**
 * Callback class introduces currying-like pattern.
 * Example:
 * function foo($param1, $param2, $param3) {
 *     var_dump($param1, $param2, $param3);
 * }
 * $fooCurried = new Callback('foo',
 *     'param1 is now statically set',
 *     new CallbackParam,
 *     new CallbackParam
 * );
 * phpQuery::callbackRun($fooCurried,
 *       array('param2 value', 'param3 value')
 * );
 * Callback class is supported in all phpQuery methods which accepts callbacks.
 *
 * @link   http://code.google.com/p/phpquery/wiki/Callbacks#Param_Structures
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 */
class Callback implements ICallbackNamed
{
    /**
     * @var mixed|null
     */
    public $callback = null;
    /**
     * @var array|null
     */
    public $params = null;
    /**
     * @var string
     */
    protected $name;

    /**
     * Callback Constructor
     *
     * @param mixed $callback
     * @param mixed $param1
     * @param mixed $param2
     * @param mixed $param3
     */
    public function __construct($callback, $param1 = null, $param2 = null, $param3 = null)
    {
        $params = func_get_args();
        $params = array_slice($params, 1);
        if ($callback instanceof Callback) {
            // TODO: implement recurention
        }
        else {
            $this->callback = $callback;
            $this->params = $params;
        }
    }

    /**
     * Check Callback Name
     *
     * @return bool
     */
    public function hasName()
    {
        return isset($this->name) && $this->name;
    }

    /**
     * Get Callback Name
     *
     * @return string
     */
    public function getName()
    {
        return 'Callback: '.$this->name;
    }

    /**
     * Set Callback name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Add new Params
     * TODO: test me
     *
     * @return callable
     */
    public function addParams()
    {
        $params = func_get_args();
        return new Callback($this->callback, $this->params + $params);
    }
}

/**
 * Shorthand for new Callback(create_function(...), ...);
 *
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 */
class CallbackBody extends Callback implements ICallbackNamed
{
    /**
     * CallbackBody Constructor
     *
     * @param mixed $paramList
     * @param mixed $code
     * @param mixed $param1
     * @param mixed $param2
     * @param mixed $param3
     */
    public function __construct($paramList, $code, $param1 = null, $param2 = null, $param3 = null)
    {
        $params = func_get_args();
        $params = array_slice($params, 2);

        $this->callback = create_function($paramList, $code);
        $this->params = $params;
    }
}

/**
 * Callback type which on execution returns reference passed during creation.
 *
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 */
class CallbackReturnReference extends Callback implements ICallbackNamed
{
    /**
     * @var callable
     */
    protected $reference;

    /**
     * CallbackReturnReference Constructor
     *
     * @param callable    $reference
     * @param string|null $name
     */
    public function __construct(&$reference, $name = null)
    {
        $this->reference =& $reference;
        $this->callback = [$this, 'callback'];
    }

    /**
     * Callback
     *
     * @return callable
     */
    public function callback()
    {
        return $this->reference;
    }
}

/**
 * Callback type which on execution returns value passed during creation.
 *
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 */
class CallbackReturnValue extends Callback implements ICallbackNamed
{
    /**
     * @var mixed
     */
    protected $value;
    /**
     * @var string|null
     */
    protected $name;

    /**
     * CallbackReturnValue Constructor
     *
     * @param mixed  $value
     * @param string $name
     */
    public function __construct($value, $name = null)
    {
        $this->value =& $value;
        $this->name = $name;
        $this->callback = [$this, 'callback'];
    }

    /**
     * Callback
     *
     * @return mixed
     */
    public function callback()
    {
        return $this->value;
    }

    /**
     * To String
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}

/**
 * CallbackParameterToReference can be used when we don't really want a callback,
 * only parameter passed to it. CallbackParameterToReference takes first
 * parameter's value and passes it to reference.
 *
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 */
class CallbackParameterToReference extends Callback
{
    /**
     * CallbackParameterToReference Constructor
     *
     * @param $reference
     * param index choose which callback param will be passed to reference
     */
    public function __construct(&$reference)
    {
        $this->callback =& $reference;
    }
}

/**
 * Class CallbackParam
 */
class CallbackParam { }

// -- DOM class list ----------------------------------------

/**
 * Class DOMEvent
 * Based on
 *
 * @link    http://developer.mozilla.org/En/DOM:event
 * @author  Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 * @TODO    implement ArrayAccess ?
 */
class DOMEvent
{
    /**
     * Returns a boolean indicating whether the event bubbles up through the DOM or not.
     *
     * @var bool
     */
    public $bubbles = true;
    /**
     * Returns a boolean indicating whether the event is cancelable.
     *
     * @var bool
     */
    public $cancelable = true;
    /**
     * Returns a reference to the currently registered target for the event.
     *
     * @var DOMNode
     */
    public $currentTarget;
    /**
     * Returns detail about the event, depending on the type of event.
     *
     * @var mixed
     * @link http://developer.mozilla.org/en/DOM/event.detail
     */
    public $detail;    // ???
    /**
     * Used to indicate which phase of the event flow is currently being evaluated.
     * NOT IMPLEMENTED
     *
     * @var mixed
     * @link http://developer.mozilla.org/en/DOM/event.eventPhase
     */
    public $eventPhase;    // ???
    /**
     * The explicit original target of the event (Mozilla-specific).
     * NOT IMPLEMENTED
     *
     * @var DOMNode
     */
    public $explicitOriginalTarget; // moz only
    /**
     * The original target of the event, before any retargeting (Mozilla-specific).
     * NOT IMPLEMENTED
     *
     * @var DOMNode
     */
    public $originalTarget;    // moz only
    /**
     * Identifies a secondary target for the event.
     *
     * @var DOMNode
     */
    public $relatedTarget;
    /**
     * Returns a reference to the target to which the event was originally dispatched.
     *
     * @var DOMNode
     */
    public $target;
    /**
     * Returns the time that the event was created.
     *
     * @var int
     */
    public $timeStamp;
    /**
     * Returns the name of the event (case-insensitive).
     *
     * @var string
     */
    public $type;
    /**
     * @var bool
     */
    public $runDefault = true;
    /**
     * @var null|mixed
     */
    public $data = null;

    /**
     * DOMEvent Constructor
     *
     * @param array $data
     */
    public function __construct($data)
    {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
        if (!$this->timeStamp) {
            $this->timeStamp = time();
        }
    }

    /**
     * Cancels the event (if it is cancelable).
     *
     * @return void
     */
    public function preventDefault()
    {
        $this->runDefault = false;
    }

    /**
     * Stops the propagation of events further along in the DOM.
     *
     * @return void
     */
    public function stopPropagation()
    {
        $this->bubbles = false;
    }
}

/**
 * Class DOMDocumentWrapper
 * class simplifies work with DOMDocument.
 * Know bug:
 * - in XHTML fragments, <br /> changes to <br clear="none" />
 *
 * @author  Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 * @TODO    check XML catalogs compatibility
 */
class DOMDocumentWrapper
{
    /**
     * DOMDocument class.
     *
     * @var DOMDocument
     */
    public $document;
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $contentType = '';
    /**
     * XPath interface.
     *
     * @var DOMXPath
     */
    public $xpath;
    /**
     * @var int
     */
    public $uuid = 0;
    /**
     * @var array
     */
    public $data = [];
    public $dataNodes = [];
    public $events = [];
    public $eventsNodes = [];
    public $eventsGlobal = [];
    public $frames = [];
    /**
     * Document root, by default equals to document itself.
     * Used by documentFragments.
     *
     * @var DOMNode
     */
    public $root;
    /**
     * @var bool
     */
    public $isDocumentFragment;
    public $isXML = false;
    public $isXHTML = false;
    public $isHTML = false;
    /**
     * @var string
     */
    public $charset;

    /**
     * DOMDocumentWrapper Constructor
     *
     * @param null|string|DOMDocument $markup
     * @param null|string             $contentType
     * @param null|string             $newDocumentID
     * @throws Exception
     */
    public function __construct($markup = null, $contentType = null, $newDocumentID = null)
    {
        if (!empty($markup)) {
            $this->load($markup, $contentType, $newDocumentID);
        }
        $this->id = !empty($newDocumentID) ? $newDocumentID : md5(microtime());
    }

    /**
     * Load by Markup
     *
     * @param string|DOMDocument $markup
     * @param null|string        $contentType
     * @param null|string        $newDocumentID
     * @return bool
     * @throws Exception
     */
    public function load($markup, $contentType = null, $newDocumentID = null)
    {
        $this->contentType = strtolower($contentType);
        if ($markup instanceof DOMDocument) {
            $this->document = $markup;
            $this->root = $this->document;
            $this->charset = $this->document->encoding;
            // TODO: isDocumentFragment
            $loaded = true;
        }
        else {
            $loaded = $this->loadMarkup($markup);
        }
        if ($loaded) {
            //$this->document->formatOutput = true;
            $this->document->preserveWhiteSpace = true;
            $this->xpath = new DOMXPath($this->document);
            $this->afterMarkupLoad();

            return true;
        }
        return false;
    }

    /**
     * After Markup loaded
     *
     * @return void
     */
    protected function afterMarkupLoad()
    {
        if ($this->isXHTML) {
            $this->xpath->registerNamespace("html", "http://www.w3.org/1999/xhtml");
        }
    }

    /**
     * Handler load Markup
     *
     * @param string $markup
     * @return bool
     * @throws Exception
     */
    protected function loadMarkup($markup)
    {
        $loaded = false;
        if ($this->contentType) {
            $this->debug("Load markup for content type {$this->contentType}");

            // content determined by contentType
            list($contentType, $charset) = $this->contentTypeToArray($this->contentType);
            switch ($contentType) {
                case 'text/html':
                    $this->debug("Loading HTML, content type '{$this->contentType}'");
                    $loaded = $this->loadMarkupHTML($markup, $charset);
                    break;
                case 'text/xml':
                case 'application/xhtml+xml':
                    $this->debug("Loading XML, content type '{$this->contentType}'");
                    $loaded = $this->loadMarkupXML($markup, $charset);
                    break;
                default:
                    // for feeds or anything that sometimes doesn't use text/xml
                    if (strpos('xml', $this->contentType) !== false) {
                        $this->debug("Loading XML, content type '{$this->contentType}'");
                        $loaded = $this->loadMarkupXML($markup, $charset);
                    }
                    else {
                        $this->debug("Could not determine document type from content type '{$this->contentType}'");
                    }
            }
        }
        else {
            // content type autodetect
            if ($this->isXML($markup)) {
                $this->debug("Loading XML, isXML() == true");
                $loaded = $this->loadMarkupXML($markup);
                if (!$loaded && $this->isXHTML) {
                    $this->debug('Loading as XML failed, trying to load as HTML, isXHTML == true');
                    $loaded = $this->loadMarkupHTML($markup);
                }
            }
            else {
                $this->debug("Loading HTML, isXML() == false");
                $loaded = $this->loadMarkupHTML($markup);
            }
        }
        return (bool)$loaded;
    }

    /**
     * Load Markup reset
     *
     * @return void
     */
    protected function loadMarkupReset()
    {
        $this->isXML = $this->isXHTML = $this->isHTML = false;
    }

    /**
     * Handler create new Document
     *
     * @param string $charset
     * @param string $version
     * @return void
     */
    protected function documentCreate($charset, $version = '1.0')
    {
        if (empty($version)) {
            $version = '1.0';
        }

        $this->document = new DOMDocument($version, $charset);
        $this->charset = $this->document->encoding;
        //$this->document->encoding = $charset;
        $this->document->formatOutput = true;
        $this->document->preserveWhiteSpace = true;
    }

    /**
     * Handler load Markup HTML
     *
     * @param string      $markup
     * @param null|string $requestedCharset
     * @return bool
     */
    protected function loadMarkupHTML($markup, $requestedCharset = null)
    {
        $this->debug('Full markup load (HTML): '.substr($markup, 0, 250));

        $this->loadMarkupReset();
        $this->isHTML = true;
        if (!isset($this->isDocumentFragment)) {
            $this->isDocumentFragment = self::isDocumentFragmentHTML($markup);
        }

        $charset = '';
        $documentCharset = $this->charsetFromHTML($markup);
        $addDocumentCharset = false;
        if (!empty($documentCharset)) {
            $charset = $documentCharset;
            $markup = $this->charsetFixHTML($markup);
        }
        elseif (!empty($requestedCharset)) {
            $charset = $requestedCharset;
        }

        if (empty($charset)) {
            $charset = $this->getDefaultCharset();
        }

        // HTTP 1.1 says that the default charset is ISO-8859-1
        // @see http://www.w3.org/International/O-HTTP-charset
        if (empty($documentCharset)) {
            $documentCharset = 'ISO-8859-1';
            $addDocumentCharset = true;
        }

        // Should be careful here, still need 'magic encoding detection' since lots of pages have other 'default encoding'
        // Worse, some pages can have mixed encodings... we'll try not to worry about that
        $requestedCharset = strtoupper($requestedCharset);
        $documentCharset = strtoupper($documentCharset);
        $this->debug("DOC: $documentCharset REQ: $requestedCharset");
        if (!empty($requestedCharset) && !empty($documentCharset) && $requestedCharset !== $documentCharset) {
            $this->debug("CHARSET CONVERT");

            // Document Encoding Conversion
            // http://code.google.com/p/phpquery/issues/detail?id=86
            if (function_exists('mb_detect_encoding')) {
                $possibleCharsets = [$documentCharset, $requestedCharset, 'AUTO'];
                $docEncoding = mb_detect_encoding($markup, implode(', ', $possibleCharsets));
                if (!$docEncoding) {
                    // ok trust the document
                    $docEncoding = $documentCharset;
                }

                $this->debug("DETECTED '$docEncoding'");
                // Detected does not match what document says...
                //if ($docEncoding !== $documentCharset) {
                //    // TODO: Tricky..
                //}

                if ($docEncoding !== $requestedCharset) {
                    $this->debug("CONVERT $docEncoding => $requestedCharset");
                    $markup = mb_convert_encoding($markup, $requestedCharset, $docEncoding);
                    $markup = $this->charsetAppendToHTML($markup, $requestedCharset);
                    $charset = $requestedCharset;
                }
            }
            else {
                $this->debug("TODO: charset conversion without mbstring...");
            }
        }

        $return = false;
        if ($this->isDocumentFragment) {
            $this->debug("Full markup load (HTML), DocumentFragment detected, using charset '$charset'");
            $return = $this->documentFragmentLoadMarkup($this, $charset, $markup);
        }
        else {
            if ($addDocumentCharset) {
                $this->debug("Full markup load (HTML), appending charset: '$charset'");
                $markup = $this->charsetAppendToHTML($markup, $charset);
            }

            $this->debug("Full markup load (HTML), documentCreate('$charset')");
            $this->documentCreate($charset);

            $return = $this->getDebug() === 2
                ? $this->document->loadHTML($markup)
                : @$this->document->loadHTML($markup);

            if ($return) {
                $this->root = $this->document;
            }
        }

        if ($return && !$this->contentType) {
            $this->contentType = 'text/html';
        }

        return (bool)$return;
    }

    /**
     * Handler load Markup XML
     *
     * @param string      $markup
     * @param null|string $requestedCharset
     * @return bool
     * @throws Exception
     */
    protected function loadMarkupXML($markup, $requestedCharset = null)
    {
        $this->debug('Full markup load (XML): '.substr($markup, 0, 250));

        $this->loadMarkupReset();
        $this->isXML = true;

        // check against XHTML in contentType or markup
        $isContentTypeXHTML = $this->isXHTML();
        $isMarkupXHTML = $this->isXHTML($markup);
        if ($isContentTypeXHTML || $isMarkupXHTML) {
            $this->debug('Full markup load (XML), XHTML detected');
            $this->isXHTML = true;
        }

        // determine document fragment
        if (!isset($this->isDocumentFragment)) {
            $this->isDocumentFragment = $this->isXHTML
                ? self::isDocumentFragmentXHTML($markup)
                : self::isDocumentFragmentXML($markup);
        }

        // this charset will be used
        $charset = '';

        // charset from XML declaration @var string
        $documentCharset = $this->charsetFromXML($markup);
        if (!$documentCharset) {
            if ($this->isXHTML) {
                // this is XHTML, try to get charset from content-type meta header
                $documentCharset = $this->charsetFromHTML($markup);
                if ($documentCharset) {
                    $this->debug("Full markup load (XML), appending XHTML charset '$documentCharset'");
                    $markup = $this->charsetAppendToXML($markup, $documentCharset);
                    $charset = $documentCharset;
                }
            }
            if (empty($documentCharset) && !empty($requestedCharset)) {
                // if still no document charset...
                $charset = $requestedCharset;
            }
        }
        elseif (!empty($requestedCharset)) {
            $charset = $requestedCharset;
        }

        if (!empty($requestedCharset) && !empty($documentCharset) && $requestedCharset != $documentCharset) {
            // Place for charset conversion
            $charset = $requestedCharset;
        }

        if (empty($charset)) {
            $charset = $this->getDefaultCharset();
        }

        $return = false;
        if ($this->isDocumentFragment) {
            $this->debug("Full markup load (XML), DocumentFragment detected, using charset '$charset'");
            $return = $this->documentFragmentLoadMarkup($this, $charset, $markup);
        }
        else {
            // FIXME ???
            if ($isContentTypeXHTML && !$isMarkupXHTML) {
                if (!$documentCharset) {
                    $this->debug("Full markup load (XML), appending charset '$charset'");
                    $markup = $this->charsetAppendToXML($markup, $charset);
                }
            }

            // see http://pl2.php.net/manual/en/book.dom.php#78929
            // LIBXML_DTDLOAD (>= PHP 5.1)
            // does XML ctalogues works with LIBXML_NONET
            //$this->document->resolveExternals = true;
            // TODO test LIBXML_COMPACT for performance improvement

            // create document
            $this->documentCreate($charset);
            if (phpversion() < 5.1) {
                $this->document->resolveExternals = true;
                $return = $this->getDebug() === 2
                    ? $this->document->loadXML($markup)
                    : @$this->document->loadXML($markup);
            }
            else {
                /** @link http://pl2.php.net/manual/en/libxml.constants.php */
                $libxmlStatic = $this->getDebug() === 2
                    ? LIBXML_DTDLOAD | LIBXML_DTDATTR | LIBXML_NONET
                    : LIBXML_DTDLOAD | LIBXML_DTDATTR | LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR;
                $return = $this->document->loadXML($markup, $libxmlStatic);
                //if (!$return) {
                //    $return = $this->document->loadHTML($markup);
                //}
            }
            if ($return) {
                $this->root = $this->document;
            }
        }

        if ($return) {
            if (!$this->contentType) {
                if ($this->isXHTML) {
                    $this->contentType = 'application/xhtml+xml';
                }
                else {
                    $this->contentType = 'text/xml';
                }
            }

            return (bool)$return;
        }
        else {
            throw new Exception("Error loading XML markup");
        }
    }

    /**
     * Check is XHTML
     *
     * @param null|string $markup
     * @return bool
     */
    protected function isXHTML($markup = null)
    {
        if (!isset($markup)) {
            return strpos($this->contentType, 'xhtml') !== false;
        }
        // XXX ok ?
        return strpos($markup, "<!DOCTYPE html") !== false;
        //return stripos($doctype, 'xhtml') !== false;
        //$doctype = isset($dom->doctype) && is_object($dom->doctype)
        //    ? $dom->doctype->publicId
        //    : $this->getDefaultDoctype();
    }

    /**
     * Check Markup is XML
     *
     * @param string $markup
     * @return bool
     */
    protected function isXML($markup)
    {
        //return strpos($markup, '<?xml') !== false && stripos($markup, 'xhtml') === false;
        return strpos(substr($markup, 0, 100), '<'.'?xml') !== false;
    }

    /**
     * Convert ContentType to Array
     *
     * @param string $contentType
     * @return array [$contentType, $charset]
     */
    protected function contentTypeToArray($contentType)
    {
        $matches = explode(';', trim(strtolower($contentType)));
        if (isset($matches[1])) {
            $matches[1] = explode('=', $matches[1]);
            // strip 'charset='
            $matches[1] = isset($matches[1][1]) && trim($matches[1][1])
                ? $matches[1][1]
                : $matches[1][0];
        }
        else {
            $matches[1] = '';
        }

        list($contentType, $charset) = [$matches[0], $matches[1]];
        return [$contentType, $charset];
    }

    /**
     * Get ContentType from HTML
     *
     * @param string $markup HTML content
     * @return array [$contentType, $charset]
     */
    protected function contentTypeFromHTML($markup)
    {
        $matches = [];
        // find meta tag
        preg_match('@<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i', $markup, $matches);
        if (!isset($matches[0])) {
            return ['', ''];
        }

        // get attr 'content'
        preg_match('@content\\s*=\\s*(["|\'])(.+?)\\1@', $matches[0], $matches);
        if (!isset($matches[0])) {
            return ['', ''];
        }

        list($contentType, $charset) = $this->contentTypeToArray($matches[2]);
        if (empty($contentType)) {
            $contentType = '';
        }
        if (empty($charset)) {
            $charset = '';
        }

        return [$contentType, $charset];
    }

    /**
     * Get Charset from HTML
     *
     * @param string $markup HTML content
     * @return string
     */
    protected function charsetFromHTML($markup)
    {
        list($contentType, $charset) = $this->contentTypeFromHTML($markup);

        return !empty($charset) ? (string)$charset : '';
    }

    /**
     * Get Charset from XML
     *
     * @param string $markup HTML content
     * @return string
     */
    protected function charsetFromXML($markup)
    {
        $matches = [];
        // find declaration
        preg_match('@<'.'?xml[^>]+encoding\\s*=\\s*(["|\'])(.*?)\\1@i', $markup, $matches);

        return isset($matches[2])
            ? strtolower($matches[2])
            : '';
    }

    /**
     * Fix Charset in HTML
     * Repositions meta[type=charset] at the start of head. Bypasses DOMDocument bug.
     *
     * @link http://code.google.com/p/phpquery/issues/detail?id=80
     * @param string $markup HTML content
     * @return string markup
     */
    protected function charsetFixHTML($markup)
    {
        $matches = [];
        // find meta tag
        preg_match('@\s*<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i', $markup, $matches, PREG_OFFSET_CAPTURE);
        if (!isset($matches[0])) {
            return $markup;
        }

        $metaContentType = $matches[0][0];
        $markup = substr($markup, 0, $matches[0][1]).substr($markup, $matches[0][1] + strlen($metaContentType));
        $headStart = stripos($markup, '<head>');
        return substr($markup, 0, $headStart + 6).$metaContentType.substr($markup, $headStart + 6);
    }

    /**
     * Append Charset To HTML
     *
     * @param string $html
     * @param string $charset
     * @param bool   $xhtml
     * @return string
     */
    protected function charsetAppendToHTML($html, $charset, $xhtml = false)
    {
        $return = $html;
        // remove existing meta[type=content-type]
        $html = preg_replace('@\s*<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i', '', $html);
        $meta = '<meta http-equiv="Content-Type" content="text/html;charset='.$charset.'" '.($xhtml ? '/' : '').'>';
        if (strpos($html, '<head') === false) {
            if (strpos($html, '<html') === false) {
                $return = $meta.$html;
            }
            else {
                $return = preg_replace('@<html(.*?)(?(?<!\?)>)@s', "<html\\1><head>{$meta}</head>", $html);
            }
        }
        else {
            $return = preg_replace('@<head(.*?)(?(?<!\?)>)@s', '<head\\1>'.$meta, $html);
        }

        return !empty($return) ? $return : $html;
    }

    /**
     * Append Charset To XML
     *
     * @param string $markup
     * @param string $charset
     * @return string
     */
    protected function charsetAppendToXML($markup, $charset)
    {
        $declaration = '<'.'?xml version="1.0" encoding="'.$charset.'"?'.'>';
        return $declaration.$markup;
    }

    /**
     * Check is Document Fragment HTML
     *
     * @param string $markup
     * @return bool
     */
    public static function isDocumentFragmentHTML($markup)
    {
        return stripos($markup, '<html') === false && stripos($markup, '<!doctype') === false;
    }

    /**
     * Check is Document Fragment XML
     *
     * @param string $markup
     * @return bool
     */
    public static function isDocumentFragmentXML($markup)
    {
        return stripos($markup, '<'.'?xml') === false;
    }

    /**
     * Check is Document Fragment XHTML
     *
     * @param string $markup
     * @return bool
     */
    public static function isDocumentFragmentXHTML($markup)
    {
        return self::isDocumentFragmentHTML($markup);
    }

    /**
     * Import Source
     *
     * @param string|array|DOMNode|DOMNodeList $source
     * @param null|string                      $sourceCharset
     * @return array Array of imported nodes.
     * @throws Exception
     */
    public function import($source, $sourceCharset = null)
    {
        // TODO: charset conversions
        $return = [];
        if ($source instanceof DOMNode && !($source instanceof DOMNodeList)) {
            $source = [$source];
        }

        //if (is_array($source)) {
        //    foreach ($source as $node) {
        //        if (is_string($node)) {
        //            // string markup
        //            $fake = $this->documentFragmentCreate($node, $sourceCharset);
        //            if ($fake === false) {
        //                throw new Exception("Error loading documentFragment markup");
        //            }
        //            else {
        //                $return = array_merge($return,$this->import($fake->root->childNodes));
        //            }
        //        }
        //        else {
        //            $return[] = $this->document->importNode($node, true);
        //        }
        //    }
        //    return $return;
        //}
        if (is_array($source) || $source instanceof DOMNodeList) {
            // dom nodes
            $this->debug('Importing nodes to document');
            foreach ($source as $node) {
                $return[] = $this->document->importNode($node, true);
            }
        }
        else {
            // string markup
            $fake = $this->documentFragmentCreate($source, $sourceCharset);
            if ($fake === false) {
                throw new Exception("Error loading documentFragment markup");
            }
            else {
                return $this->import($fake->root->childNodes);
            }
        }
        return $return;
    }

    /**
     * Creates new document fragment.
     *
     * @param string|array|DOMNode|DOMNodeList $source
     * @param null|string                      $charset
     * @return false|DOMDocumentWrapper
     * @throws Exception
     */
    protected function documentFragmentCreate($source, $charset = null)
    {
        $fake = new DOMDocumentWrapper();
        $fake->contentType = $this->contentType;
        $fake->isXML = $this->isXML;
        $fake->isHTML = $this->isHTML;
        $fake->isXHTML = $this->isXHTML;
        $fake->root = $fake->document;

        if (!$charset) {
            $charset = $this->charset;
        }
        //$fake->documentCreate($this->charset);

        if ($source instanceof DOMNode && !($source instanceof DOMNodeList)) {
            $source = [$source];
        }

        if (is_array($source) || $source instanceof DOMNodeList) {
            // dom nodes
            // load fake document
            if (!$this->documentFragmentLoadMarkup($fake, $charset)) {
                return false;
            }

            $nodes = $fake->import($source);
            foreach ($nodes as $node) {
                $fake->root->appendChild($node);
            }
        }
        else {
            // string markup
            $this->documentFragmentLoadMarkup($fake, $charset, $source);
        }

        return $fake;
    }

    /**
     * Document Fragment load Markup
     *
     * @param DOMDocumentWrapper $fragment
     * @param string             $charset
     * @param null|string        $markup
     * @return bool
     * @throws Exception
     */
    private function documentFragmentLoadMarkup($fragment, $charset, $markup = null)
    {
        // TODO: error handling
        // TODO: copy doctype

        // temporary turn off
        $fragment->isDocumentFragment = false;
        if ($fragment->isXML) {
            if ($fragment->isXHTML) {
                // add FAKE element to set default namespace
                $fragment->loadMarkupXML('<?xml version="1.0" encoding="'.$charset.'"?>'
                    .'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" '
                    .'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
                    .'<fake xmlns="http://www.w3.org/1999/xhtml">'.$markup.'</fake>');
                $fragment->root = $fragment->document->firstChild->nextSibling;
            }
            else {
                $fragment->loadMarkupXML('<?xml version="1.0" encoding="'.$charset.'"?><fake>'.$markup.'</fake>');
                $fragment->root = $fragment->document->firstChild;
            }
        }
        else {
            $markup2 = $this->getDefaultDoctype().'<html lang="en"><head><meta http-equiv="Content-Type" content="text/html;charset='.$charset.'"><title>Hello World!</title></head>';
            $noBody = strpos($markup, '<body') === false;

            if ($noBody) {
                $markup2 .= '<body>';
            }
            $markup2 .= $markup;
            if ($noBody) {
                $markup2 .= '</body>';
            }

            $markup2 .= '</html>';
            $fragment->loadMarkupHTML($markup2);

            // TODO: resolve body tag merging issue
            //$fragment->root = $noBody
            //    ? $fragment->document->firstChild->nextSibling->firstChild->nextSibling
            //    : $fragment->document->firstChild->nextSibling->firstChild->nextSibling;
            $fragment->root = $fragment->document->firstChild->nextSibling->firstChild->nextSibling;
        }
        if (!$fragment->root) {
            return false;
        }

        $fragment->isDocumentFragment = true;
        return true;
    }

    /**
     * Convert Document Fragment to Markup
     *
     * @param DOMDocumentWrapper $fragment
     * @return string
     */
    protected function documentFragmentToMarkup($fragment)
    {
        $this->debug('documentFragmentToMarkup');

        $tmp = $fragment->isDocumentFragment;
        $fragment->isDocumentFragment = false;
        $markup = $fragment->markup();
        if ($fragment->isXML) {
            $markup = substr($markup, 0, strrpos($markup, '</fake>'));
            if ($fragment->isXHTML) {
                $markup = substr($markup, strpos($markup, '<fake') + 43);
            }
            else {
                $markup = substr($markup, strpos($markup, '<fake>') + 6);
            }
        }
        else {
            $markup = substr($markup, strpos($markup, '<body>') + 6);
            $markup = substr($markup, 0, strrpos($markup, '</body>'));
        }
        $fragment->isDocumentFragment = $tmp;
        $this->debug('documentFragmentToMarkup: '.substr($markup, 0, 150));

        return $markup;
    }

    /**
     * Return document markup, starting with optional $nodes as root.
     *
     * @param array|DOMNode|DOMNodeList $nodes
     * @return string
     */
    public function markup($nodes = null, $innerMarkup = false)
    {
        if (isset($nodes) && count($nodes) == 1 && $nodes[0] instanceof DOMDocument) {
            $nodes = null;
        }

        if (isset($nodes)) {
            $markup = '';
            if (!is_array($nodes) && !($nodes instanceof DOMNodeList)) {
                $nodes = [$nodes];
            }
            if ($this->isDocumentFragment && !$innerMarkup) {
                foreach ($nodes as $i => $node) {
                    if ($node->isSameNode($this->root)) {
                        $nodes = array_slice($nodes, 0, $i)
                            + phpQuery::DOMNodeListToArray($node->childNodes)
                            + array_slice($nodes, $i + 1);
                    }
                }
            }

            if ($this->isXML && !$innerMarkup) {
                $this->debug("Getting outerXML with charset '{$this->charset}'");
                // we need outerXML, so we can benefit from
                // $node param support in saveXML()
                foreach ($nodes as $node) {
                    $markup .= $this->document->saveXML($node);
                }
            }
            else {
                $loop = [];
                if ($innerMarkup) {
                    foreach ($nodes as $node) {
                        if ($node->childNodes) {
                            foreach ($node->childNodes as $child) {
                                $loop[] = $child;
                            }
                        }
                        else {
                            $loop[] = $node;
                        }
                    }
                }
                else {
                    $loop = $nodes;
                }

                $this->debug("Getting markup, moving selected nodes (".count($loop).") to new DocumentFragment");
                $fake = $this->documentFragmentCreate($loop);
                $markup = $this->documentFragmentToMarkup($fake);
            }

            if ($this->isXHTML) {
                $this->debug("Fixing XHTML");
                $markup = self::markupFixXHTML($markup);
            }

            $this->debug("Markup: ".substr($markup, 0, 250));
            return $markup;
        }
        else {
            if ($this->isDocumentFragment) {
                // documentFragment, html only...
                $this->debug("Getting markup, DocumentFragment detected");
                //return $this->markup(
                //    //$this->document->getElementsByTagName('body')->item(0)
                //    $this->document->root, true
                //);

                $markup = $this->documentFragmentToMarkup($this);
                // no need for markupFixXHTML, as it's done thought markup($nodes) method
                return $markup;
            }
            else {
                $this->debug("Getting markup (".($this->isXML ? 'XML' : 'HTML')."), final with charset '{$this->charset}'");
                $markup = $this->isXML
                    ? $this->document->saveXML()
                    : $this->document->saveHTML();

                if ($this->isXHTML) {
                    $this->debug("Fixing XHTML");
                    $markup = self::markupFixXHTML($markup);
                }

                $this->debug("Markup: ".substr($markup, 0, 250));
                return $markup;
            }
        }
    }

    /**
     * Markup fix XHTML
     *
     * @param string $markup
     * @return string
     */
    protected static function markupFixXHTML($markup)
    {
        $markup = self::expandEmptyTag('script', $markup);
        $markup = self::expandEmptyTag('select', $markup);
        $markup = self::expandEmptyTag('textarea', $markup);
        return $markup;
    }

    /**
     * Expand Empty Tag
     *
     * @param string $tag
     * @param string $xml
     * @return string
     * @author mjaque at ilkebenson dot com
     * @link   http://php.net/manual/en/domdocument.savehtml.php#81256
     */
    public static function expandEmptyTag($tag, $xml)
    {
        $indice = 0;
        while ($indice < strlen($xml)) {
            $pos = strpos($xml, "<$tag ", $indice);
            if ($pos) {
                $posCierre = strpos($xml, ">", $pos);
                if ($xml[$posCierre - 1] == "/") {
                    $xml = substr_replace($xml, "></$tag>", $posCierre - 1, 2);
                }
                $indice = $posCierre;
            }
            else {
                break;
            }
        }
        return $xml;
    }

    /**
     * Get default Charset
     *
     * @return string
     */
    protected function getDefaultCharset()
    {
        return phpQuery::$defaultCharset;
    }

    /**
     * Get default Doctype
     *
     * @return string
     */
    protected function getDefaultDoctype()
    {
        return phpQuery::$defaultDoctype;
    }

    /**
     * Get Debug status
     *
     * @return bool|int
     */
    protected function getDebug()
    {
        return phpQuery::$debug;
    }

    /**
     * Log Debug
     *
     * @param string $text
     */
    protected function debug($text)
    {
        phpQuery::debug($text);
    }
}

/**
 * Event handling class.
 *
 * @author  Tobiasz Cudnik
 * @package phpQuery
 * @static
 */
abstract class phpQueryEvents
{
    /**
     * Trigger a type of event on every matched element.
     *
     * @param DOMDocument|DOMNode|phpQueryObject|string $document
     * @param string                                    $type
     * @param array                                     $data
     * @param null|DOMNode                              $node
     * @return void
     * @TODO exclusive events (with !)
     * @TODO global events (test)
     * @TODO support more than event in $type (space-separated)
     */
    public static function trigger($document, $type, $data = [], $node = null)
    {
        // trigger: function(type, data, elem, donative, extra) {
        $documentID = phpQuery::getDocumentID($document);
        $namespace = null;
        if (strpos($type, '.') !== false) {
            list($name, $namespace) = explode('.', $type);
        }
        else {
            $name = $type;
        }

        if (empty($node)) {
            if (phpQueryEvents::issetGlobal($documentID, $type)) {
                $pq = phpQuery::getDocument($documentID);
                // TODO check add($pq->document)
                $pq->find('*')
                    ->add($pq->document)
                    ->trigger($type, $data);
            }
        }
        else {
            if (isset($data[0]) && $data[0] instanceof DOMEvent) {
                $event = $data[0];
                $event->relatedTarget = $event->target;
                $event->target = $node;
                $data = array_slice($data, 1);
            }
            else {
                $event = new DOMEvent([
                    'type'      => $type,
                    'target'    => $node,
                    'timeStamp' => time(),
                ]);
            }

            $i = 0;
            while ($node) {
                // TODO whois
                phpQuery::debug("Triggering ".($i ? "bubbled " : '')."event '{$type}' on node \n");//.phpQueryObject::whois($node)."\n");

                $event->currentTarget = $node;
                $eventNode = phpQueryEvents::getNode($documentID, $node);
                if (isset($eventNode->eventHandlers)) {
                    foreach ($eventNode->eventHandlers as $eventType => $handlers) {
                        $eventNamespace = null;
                        if (strpos($type, '.') !== false) {
                            list($eventName, $eventNamespace) = explode('.', $eventType);
                        }
                        else {
                            $eventName = $eventType;
                        }

                        if ($name != $eventName) {
                            continue;
                        }
                        if (!empty($namespace) && !empty($eventNamespace) && $namespace != $eventNamespace) {
                            continue;
                        }

                        foreach ($handlers as $handler) {
                            phpQuery::debug("Calling event handler\n");
                            $event->data = !empty($handler['data']) ? $handler['data'] : null;
                            $params = array_merge([$event], $data);

                            $return = phpQuery::callbackRun($handler['callback'], $params);
                            if ($return === false) {
                                $event->bubbles = false;
                            }
                        }
                    }
                }

                // to bubble or not to bubble...
                if (!$event->bubbles) {
                    break;
                }

                $node = $node->parentNode;
                $i++;
            }
        }
    }

    /**
     * Binds a handler($data and $callback) to the eventHandlers of $node in $document
     * (Binds a handler to one or more events (like click) for each matched element.
     * Can also bind custom events.)
     *
     * @param DOMDocument|DOMNode|phpQueryObject|string $document
     * @param DOMNode                                   $node
     * @param string                                    $type
     * @param mixed                                     $data Optional
     * @param null|callback                             $callback
     * @return void
     * @TODO support '!' (exclusive) events
     * @TODO support more than event in $type (space-separated)
     * @TODO support binding to global events
     */
    public static function add($document, $node, $type, $data, $callback = null)
    {
        phpQuery::debug("Binding '$type' event");

        $documentID = phpQuery::getDocumentID($document);
        //if (is_null($callback) && is_callable($data)) {
        //    $callback = $data;
        //    $data = null;
        //}

        $eventNode = phpQueryEvents::getNode($documentID, $node);
        if (empty($eventNode)) {
            $eventNode = phpQueryEvents::setNode($documentID, $node);
        }

        // eventHandlers is a self-defined property for DOMNode
        if (!isset($eventNode->eventHandlers[$type])) {
            $eventNode->eventHandlers[$type] = [];
        }

        $eventNode->eventHandlers[$type][] = [
            'callback' => $callback,
            'data'     => $data,
        ];
    }

    /**
     * Remove $type in eventHandlers of Node
     * (with $callback name)
     *
     * @param DOMDocument|DOMNode|phpQueryObject|string $document
     * @param DOMNode                                   $node
     * @param null|string                               $type
     * @param null|callable                             $callback
     * @TODO namespace events
     * @TODO support more than event in $type (space-separated)
     */
    public static function remove($document, $node, $type = null, $callback = null)
    {
        $documentID = phpQuery::getDocumentID($document);
        $eventNode = phpQueryEvents::getNode($documentID, $node);
        if (is_object($eventNode) && isset($eventNode->eventHandlers[$type])) {
            if ($callback) {
                foreach ($eventNode->eventHandlers[$type] as $k => $handler) {
                    if ($handler['callback'] == $callback) {
                        unset($eventNode->eventHandlers[$type][$k]);
                    }
                }
            }
            else {
                unset($eventNode->eventHandlers[$type]);
            }
        }
    }

    /**
     * Get Node by isSameNode in eventsNodes of Document
     *
     * @param string  $documentID
     * @param DOMNode $node
     * @return null|DOMNode
     */
    protected static function getNode($documentID, $node)
    {
        foreach (phpQuery::$documents[$documentID]->eventsNodes as $eventNode) {
            if ($node->isSameNode($eventNode)) {
                return $eventNode;
            }
        }
        return null;
    }

    /**
     * Set Node to eventsNodes of Document with ID = $documentID
     *
     * @param string  $documentID
     * @param DOMNode $node
     * @return DOMNode last Node
     */
    protected static function setNode($documentID, $node)
    {
        phpQuery::$documents[$documentID]->eventsNodes[] = $node;
        // return last Node
        return phpQuery::$documents[$documentID]->eventsNodes[count(phpQuery::$documents[$documentID]->eventsNodes) - 1];
    }

    /**
     * Check $type isset in eventsGlobal
     *
     * @param string $documentID
     * @param string $type
     * @return bool
     */
    protected static function issetGlobal($documentID, $type)
    {
        return isset(phpQuery::$documents[$documentID]) && in_array($type, phpQuery::$documents[$documentID]->eventsGlobal);
    }
}

/**
 * Class representing phpQuery objects.
 *
 * @author  Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 * @method phpQueryObject clone () clone ()
 * @method phpQueryObject empty() empty()
 * @method phpQueryObject next() next($selector = null)
 * @method phpQueryObject prev() prev($selector = null)
 * @property Int $length
 */
class phpQueryObject implements Iterator, Countable, ArrayAccess
{
    /**
     * @var string
     */
    public $documentID = null;
    /**
     * DOMDocument class.
     *
     * @var DOMDocument
     */
    public $document = null;
    /**
     * @var string
     */
    public $charset = null;
    /**
     * DOMDocumentWrapper class.
     *
     * @var DOMDocumentWrapper
     */
    public $documentWrapper = null;
    /**
     * XPath interface.
     *
     * @var DOMXPath
     */
    public $xpath = null;
    /**
     * Stack of selected elements.
     *
     * @var array
     * @TODO refactor to ->nodes
     */
    public $elements = [];
    /**
     * @var array
     */
    protected $elementsBackup = [];
    /**
     * phpQueryObject class.
     *
     * @var phpQueryObject
     */
    protected $previous = null;
    /**
     * DOMNode class.
     *
     * @var DOMNode
     * @TODO deprecate
     */
    protected $root = null;
    /**
     * Indicated if document is just a fragment (no <html> tag).
     * Every document is really a full document, so even documentFragments can
     * be queried against <html>, but getDocument(id)->htmlOuter() will return
     * only contents of <body>.
     *
     * @var bool
     */
    public $documentFragment = true;
    /**
     * Iterator interface helper
     *
     * @var array
     */
    protected $elementsInterator = [];
    /**
     * Iterator interface helper
     *
     * @var bool
     */
    protected $valid = false;
    /**
     * Iterator interface helper
     *
     * @var int
     */
    protected $current = null;

    /**
     * phpQueryObject Constructor
     *
     * @param string|phpQueryObject $documentID
     * @return void
     * @throws Exception
     */
    public function __construct($documentID)
    {
        $id = $documentID instanceof self
            ? $documentID->getDocumentID()
            : $documentID;

        if (!isset(phpQuery::$documents[$id])) {
            throw new Exception("Document with ID '{$id}' isn't loaded. Use phpQuery::newDocument(\$html) or phpQuery::newDocumentFile(\$file) first.");
        }

        $this->documentID = $id;
        $this->documentWrapper =& phpQuery::$documents[$id];
        $this->document =& $this->documentWrapper->document;
        $this->xpath =& $this->documentWrapper->xpath;
        $this->charset =& $this->documentWrapper->charset;
        $this->documentFragment =& $this->documentWrapper->isDocumentFragment;
        // TODO: check $this->DOM->documentElement;
        //$this->root = $this->document->documentElement;
        $this->root =& $this->documentWrapper->root;
        //$this->toRoot();
        $this->elements = [$this->root];
    }

    /**
     * Get Attribute value
     *
     * @param string $attr
     * @return mixed
     */
    public function __get($attr)
    {
        switch ($attr) {
            // FIXME doesnt work at all ?
            case 'length':
                return $this->size();
            default:
                return $this->{$attr};
        }
    }

    /**
     * Saves actual object to $var by reference.
     * Useful when need to break chain.
     *
     * @param phpQueryObject $var
     * @return $this
     */
    public function toReference(&$var)
    {
        return $var = $this;
    }

    /**
     * Get Document Fragment
     *
     * @param null|mixed $state
     * @return bool
     */
    public function documentFragment($state = null)
    {
        if ($state) {
            phpQuery::$documents[$this->getDocumentID()]['documentFragment'] = $state;
        }
        return $this->documentFragment;
    }

    /**
     * Check Node is Root
     *
     * @param DOMDocument|DOMNode $node
     * @return bool
     * @TODO documentWrapper
     */
    protected function isRoot($node)
    {
        //return $node instanceof DOMDocument || $node->tagName == 'html';
        return $node instanceof DOMDocument
            || ($node instanceof DOMElement && $node->tagName == 'html')
            || $this->root->isSameNode($node);
    }

    /**
     * check Stack Node with only Root?
     *
     * @return bool
     */
    protected function stackIsRoot()
    {
        return $this->size() == 1 && $this->isRoot($this->elements[0]);
    }

    /**
     * Assign Stack Node with a list with only root
     * (Watch out, it doesn't create new instance, can be reverted with end().)
     *
     * @return phpQueryObject
     */
    public function toRoot()
    {
        $this->elements = [$this->root];

        return $this;
        //return $this->newInstance(array($this->root));
    }

    /**
     * Saves object's DocumentID to $var by reference.
     * <code>
     * $myDocumentId;
     * phpQuery::newDocument('<div/>')
     *     ->getDocumentIDRef($myDocumentId)
     *     ->find('div')->...
     * </code>
     *
     * @param mixed $documentID
     * @return phpQueryObject
     * @see phpQuery::newDocumentFile
     * @see phpQuery::newDocument
     */
    public function getDocumentIDRef(&$documentID)
    {
        $documentID = $this->getDocumentID();
        return $this;
    }

    /**
     * Returns object with stack set to document root.
     *
     * @return phpQueryObject
     */
    public function getDocument()
    {
        return phpQuery::getDocument($this->getDocumentID());
    }

    /**
     * Get DOMDocument
     *
     * @return DOMDocument
     */
    public function getDOMDocument()
    {
        return $this->document;
    }

    /**
     * Get object's Document ID.
     *
     * @return string
     */
    public function getDocumentID()
    {
        return $this->documentID;
    }

    /**
     * Unloads whole document from memory.
     * CAUTION! None further operations will be possible on this document.
     * All objects referring to it will be useless.
     *
     * @return void
     */
    public function unloadDocument()
    {
        phpQuery::unloadDocuments($this->getDocumentID());
    }

    /**
     * Check is HTML
     *
     * @return bool
     */
    public function isHTML()
    {
        return $this->documentWrapper->isHTML;
    }

    /**
     * Check is XHTML
     *
     * @return bool
     */
    public function isXHTML()
    {
        return $this->documentWrapper->isXHTML;
    }

    /**
     * Check is XML
     *
     * @return bool
     */
    public function isXML()
    {
        return $this->documentWrapper->isXML;
    }

    /**
     * Encode a set of form elements as a string for submission.
     *
     * @link https://api.jquery.com/serialize
     * @return string
     */
    public function serialize()
    {
        return phpQuery::param($this->serializeArray());
    }

    /**
     * Encode a set of form elements as an array of names and values.
     *
     * @link https://api.jquery.com/serializeArray
     * @return array
     */
    public function serializeArray($submit = null)
    {
        $source = $this->filter('form, input, select, textarea')
            ->find('input, select, textarea')
            ->andSelf()
            ->not('form');

        $return = [];
        foreach ($source as $input) {
            $input = phpQuery::pq($input);
            if ($input->is('[disabled]')) {
                continue;
            }
            if (!$input->is('[name]')) {
                continue;
            }
            if ($input->is('[type=checkbox]') && !$input->is('[checked]')) {
                continue;
            }
            // jquery diff
            if ($submit && $input->is('[type=submit]')) {
                if ($submit instanceof DOMElement && !$input->elements[0]->isSameNode($submit)) {
                    continue;
                }
                elseif (is_string($submit) && $input->attr('name') != $submit) {
                    continue;
                }
            }

            $return[] = [
                'name'  => $input->attr('name'),
                'value' => $input->val(),
            ];
        }
        return $return;
    }

    /**
     * Check $pattern is Regexp
     *
     * @param string $pattern
     * @return bool
     */
    protected function isRegexp($pattern)
    {
        return in_array($pattern[mb_strlen($pattern) - 1], ['^', '*', '$']);
    }

    /**
     * Determines if $char is really a char.
     *
     * @param string $char
     * @return bool
     * @TODO rewrite me to char_code range ! ;)
     */
    protected function isChar($char)
    {
        return extension_loaded('mbstring') && phpQuery::$mbstringSupport
            ? mb_eregi('\w', $char)
            : preg_match('@\w@', $char);
    }

    /**
     * Handler Parse Selector
     *
     * @param string $query
     * @return array[]
     */
    protected function parseSelector($query)
    {
        // clean spaces
        // TODO include this inside parsing ?
        $query = trim(
            preg_replace('@\s+@', ' ',
                preg_replace('@\s*(>|\\+|~)\s*@', '\\1', $query)
            )
        );

        $queries = [[]];
        if (empty($query)) {
            return $queries;
        }

        $return =& $queries[0];
        $specialChars = ['>', ' '];
        //$specialCharsMapping = array('/' => '>');
        $specialCharsMapping = [];
        $strlen = mb_strlen($query);
        $classChars = ['.', '-'];
        $pseudoChars = ['-'];
        $tagChars = ['*', '|', '-'];

        // split multibyte string
        // http://code.google.com/p/phpquery/issues/detail?id=76
        $_query = [];
        for ($i = 0; $i < $strlen; $i++) {
            $_query[] = mb_substr($query, $i, 1);
        }
        $query = $_query;

        // it works, but I don't like it...
        $i = 0;
        while ($i < $strlen) {
            $c = $query[$i];
            $tmp = '';
            // TAG
            if ($this->isChar($c) || in_array($c, $tagChars)) {
                while (
                    isset($query[$i])
                    && ($this->isChar($query[$i]) || in_array($query[$i], $tagChars))
                ) {
                    $tmp .= $query[$i];
                    $i++;
                }
                $return[] = $tmp;
            }
            // IDs
            elseif ($c == '#') {
                $i++;
                while (
                    isset($query[$i])
                    && ($this->isChar($query[$i]) || $query[$i] == '-')
                ) {
                    $tmp .= $query[$i];
                    $i++;
                }
                $return[] = '#'.$tmp;
            }
            // SPECIAL CHARS
            elseif (in_array($c, $specialChars)) {
                $return[] = $c;
                $i++;
            }
            // MAPPED SPECIAL MULTICHARS
            //elseif ($c.$query[$i + 1] == '//') {
            //    $return[] = ' ';
            //    $i = $i + 2;
            //}
            // MAPPED SPECIAL CHARS
            elseif (isset($specialCharsMapping[$c])) {
                $return[] = $specialCharsMapping[$c];
                $i++;
            }
            // COMMA
            elseif ($c == ',') {
                $queries[] = [];
                $return =& $queries[count($queries) - 1];
                $i++;
                while (isset($query[$i]) && $query[$i] == ' ') {
                    $i++;
                }
            }
            // CLASSES
            elseif ($c == '.') {
                while (
                    isset($query[$i])
                    && ($this->isChar($query[$i]) || in_array($query[$i], $classChars))
                ) {
                    $tmp .= $query[$i];
                    $i++;
                }
                $return[] = $tmp;
            }
            // ~ General Sibling Selector
            elseif ($c == '~') {
                $spaceAllowed = true;
                $tmp .= $query[$i++];
                while (
                    isset($query[$i])
                    && (
                        $this->isChar($query[$i])
                        || in_array($query[$i], $classChars)
                        || $query[$i] == '*'
                        || ($query[$i] == ' ' && $spaceAllowed)
                    )
                ) {
                    if ($query[$i] != ' ') {
                        $spaceAllowed = false;
                    }
                    $tmp .= $query[$i];
                    $i++;
                }
                $return[] = $tmp;
            }
            // + Adjacent sibling selectors
            elseif ($c == '+') {
                $spaceAllowed = true;
                $tmp .= $query[$i++];
                while (isset($query[$i])
                    && (
                        $this->isChar($query[$i])
                        || in_array($query[$i], $classChars)
                        || $query[$i] == '*'
                        || ($spaceAllowed && $query[$i] == ' ')
                    )
                ) {
                    if ($query[$i] != ' ') {
                        $spaceAllowed = false;
                    }
                    $tmp .= $query[$i];
                    $i++;
                }
                $return[] = $tmp;
            }
            // ATTRS
            elseif ($c == '[') {
                $stack = 1;
                $tmp .= $c;
                while (isset($query[++$i])) {
                    $tmp .= $query[$i];
                    if ($query[$i] == '[') {
                        $stack++;
                    }
                    elseif ($query[$i] == ']') {
                        $stack--;
                        if (!$stack) {
                            break;
                        }
                    }
                }
                $return[] = $tmp;
                $i++;
            }
            // PSEUDO CLASSES
            elseif ($c == ':') {
                $tmp .= $query[$i++];
                while (
                    isset($query[$i])
                    && ($this->isChar($query[$i]) || in_array($query[$i], $pseudoChars))
                ) {
                    $tmp .= $query[$i];
                    $i++;
                }
                // with arguments ?
                if (isset($query[$i]) && $query[$i] == '(') {
                    $tmp .= $query[$i];
                    $stack = 1;
                    while (isset($query[++$i])) {
                        $tmp .= $query[$i];
                        if ($query[$i] == '(') {
                            $stack++;
                        }
                        elseif ($query[$i] == ')') {
                            $stack--;
                            if (!$stack) {
                                break;
                            }
                        }
                    }
                    $return[] = $tmp;
                    $i++;
                }
                else {
                    $return[] = $tmp;
                }
            }
            else {
                $i++;
            }
        }

        foreach ($queries as $k => $q) {
            if (isset($q[0])) {
                if (isset($q[0][0]) && $q[0][0] == ':') {
                    array_unshift($queries[$k], '*');
                }
                if ($q[0] != '>') {
                    array_unshift($queries[$k], ' ');
                }
            }
        }
        return $queries;
    }

    /**
     * Return matched DOM nodes.
     *
     * @param null|int             $index index of list Node (elements)
     * @param null|string|callable $callback1
     * @param null|string|callable $callback2
     * @param null|string|callable $callback3
     * @return array|DOMElement Single DOMElement or array of DOMElement.
     */
    public function get($index = null, $callback1 = null, $callback2 = null, $callback3 = null)
    {
        $return = isset($index)
            ? (isset($this->elements[$index]) ? $this->elements[$index] : null)
            : $this->elements;

        // pass thou callbacks
        $args = func_get_args();
        $args = array_slice($args, 1);
        foreach ($args as $callback) {
            if (is_array($return)) {
                foreach ($return as $k => $v) {
                    $return[$k] = phpQuery::callbackRun($callback, [$v]);
                }
            }
            else {
                $return = phpQuery::callbackRun($callback, [$return]);
            }
        }

        return $return;
    }

    /**
     * Return matched DOM nodes.
     * jQuery difference.
     *
     * @param null|int             $index
     * @param null|string|callable $callback1
     * @param null|string|callable $callback2
     * @param null|string|callable $callback3
     * @return array|string Returns string if $index != null
     * @TODO implement callbacks
     * @TODO return only arrays ?
     * @TODO maybe other name...
     */
    public function getString($index = null, $callback1 = null, $callback2 = null, $callback3 = null)
    {
        if ($index) {
            $return = $this->eq($index)->text();
        }
        else {
            $return = [];
            for ($i = 0; $i < $this->size(); $i++) {
                $return[] = $this->eq($i)->text();
            }
        }

        // pass thou callbacks
        $args = func_get_args();
        $args = array_slice($args, 1);
        foreach ($args as $callback) {
            $return = phpQuery::callbackRun($callback, [$return]);
        }
        return $return;
    }

    /**
     * Return matched DOM nodes.
     * jQuery difference.
     *
     * @param null|int             $index
     * @param null|string|callable $callback1
     * @param null|string|callable $callback2
     * @param null|string|callable $callback3
     * @return array|string Returns string if $index != null
     * @TODO implement callbacks
     * @TODO return only arrays ?
     * @TODO maybe other name...
     */
    public function getStrings($index = null, $callback1 = null, $callback2 = null, $callback3 = null)
    {
        if ($index) {
            $return = $this->eq($index)->text();
        }
        else {
            $return = [];
            for ($i = 0; $i < $this->size(); $i++) {
                $return[] = $this->eq($i)->text();
            }
        }

        // pass thou callbacks
        $args = func_get_args();
        $args = array_slice($args, 1);
        foreach ($args as $callback) {
            if (is_array($return))
                foreach ($return as $k => $v) {
                    $return[$k] = phpQuery::callbackRun($callback, [$v]);
                }
            else {
                $return = phpQuery::callbackRun($callback, [$return]);
            }
        }
        return $return;
    }

    /**
     * Returns new instance of actual class.
     *
     * @param null|string|array $newStack Optional. Will replace old stack with new and move old one to history.
     * @return phpQueryObject
     */
    public function newInstance($newStack = null)
    {
        $class = get_class($this);

        // support inheritance by passing old object to overloaded constructor
        $new = ($class != 'phpQuery')
            ? new $class($this, $this->getDocumentID())
            : new phpQueryObject($this->getDocumentID());

        $new->previous = $this;
        if (is_null($newStack)) {
            $new->elements = $this->elements;
            if ($this->elementsBackup) {
                $this->elements = $this->elementsBackup;
            }
        }
        elseif (is_string($newStack)) {
            $new->elements = phpQuery::pq($newStack, $this->getDocumentID())->stack();
        }
        else {
            $new->elements = $newStack;
        }

        return $new;
    }

    /**
     * Match Classes
     * In the future, when PHP will support XLS 2.0, then we would do that this way:
     * contains(tokenize(@class, '\s'), "something")
     *
     * @param string             $class
     * @param DOMElement|DOMNode $node
     * @return bool
     */
    protected function matchClasses($class, $node)
    {
        // multi-class
        if (mb_strpos($class, '.', 1)) {
            $classes = explode('.', substr($class, 1));
            $classesCount = count($classes);
            $nodeClasses = explode(' ', $node->getAttribute('class'));
            $nodeClassesCount = count($nodeClasses);
            if ($classesCount > $nodeClassesCount) {
                return false;
            }

            $diff = count(array_diff($classes, $nodeClasses));
            if (!$diff) {
                return true;
            }
        }
        // single-class
        else {
            return in_array(
            // strip leading dot from class name
                substr($class, 1),
                // get classes for element as array
                explode(' ', $node->getAttribute('class'))
            );
        }
        return false;
    }

    /**
     * runQuery
     *
     * @param string      $XQuery
     * @param null|mixed  $selector
     * @param null|string $compare Compare function name
     * @return void
     */
    protected function runQuery($XQuery, $selector = null, $compare = null)
    {
        if ($compare && !method_exists($this, $compare)) {
            return;
        }

        $stack = [];
        if (empty($this->elements)) {
            $this->debug('Stack empty, skipping...');
        }

        // DOMElement, DOMDocument, DOMNotation
        foreach ($this->stack([XML_ELEMENT_NODE, XML_DOCUMENT_NODE, XML_HTML_DOCUMENT_NODE]) as $k => $stackNode) {
            $detachAfter = false;
            // to work on detached nodes we need temporary place them somewhere
            // that's because context xpath queries sucks ;]
            $testNode = $stackNode;
            while ($testNode) {
                if (!$testNode->parentNode && !$this->isRoot($testNode)) {
                    $this->root->appendChild($testNode);
                    $detachAfter = $testNode;
                    break;
                }
                $testNode = isset($testNode->parentNode) ? $testNode->parentNode : null;
            }

            // XXX tmp ?
            $xpath = $this->documentWrapper->isXHTML
                ? $this->getNodeXpath($stackNode, 'html')
                : $this->getNodeXpath($stackNode);

            // FIXME pseudo-classes-only query, support XML
            $query = ($XQuery == '//' && $xpath == '/html[1]') ? '//*' : $xpath.$XQuery;

            $this->debug("XPATH: {$query}");

            // run query, get elements
            $nodes = $this->xpath->query($query);

            $this->debug("QUERY FETCHED");

            if (!$nodes->length) {
                $this->debug('Nothing found');
            }

            $debug = [];
            foreach ($nodes as $node) {
                $matched = false;
                if ($compare) {
                    if ($this->getDebug()) {
                        $this->debug("Found: ".$this->whois($node).", comparing with {$compare}()");
                    }
                    $phpQueryDebug = $this->getDebug();
                    $this->setDebug(false);
                    // TODO ??? use phpQuery::callbackRun()
                    if (call_user_func_array([$this, $compare], [$selector, $node])) {
                        $matched = true;
                    }
                    $this->setDebug($phpQueryDebug);
                }
                else {
                    $matched = true;
                }

                if ($matched) {
                    if ($this->getDebug()) {
                        $debug[] = $this->whois($node);
                    }
                    $stack[] = $node;
                }
            }
            if ($this->getDebug()) {
                $this->debug("Matched ".count($debug).": ".implode(', ', $debug));
            }
            if ($detachAfter) {
                $this->root->removeChild($detachAfter);
            }
        }
        $this->elements = $stack;
    }

    /**
     * find Selector
     *
     * @param mixed      $selectors
     * @param null|mixed $context   (DOMElement|phpQueryObject|array..)
     * @param bool       $noHistory using backup Elements
     * @return phpQueryObject
     */
    public function find($selectors, $context = null, $noHistory = false)
    {
        if (!$noHistory) {
            // backup last stack /for end()/
            $this->elementsBackup = $this->elements;
        }

        // allow to define context
        // TODO: combine code below with phpQuery::pq() context guessing code as generic function
        if ($context) {
            if (!is_array($context) && $context instanceof DOMElement) {
                $this->elements = [$context];
            }
            elseif (is_array($context)) {
                $this->elements = [];
                foreach ($context as $c) {
                    if ($c instanceof DOMElement) {
                        $this->elements[] = $c;
                    }
                }
            }
            elseif ($context instanceof self) {
                $this->elements = $context->elements;
            }
        }

        $queries = $this->parseSelector($selectors);
        $this->debug(['FIND', $selectors, $queries]);
        $XQuery = '';

        // remember stack state because of multi-queries
        $oldStack = $this->elements;

        // here we will be keeping found elements
        $stack = [];
        foreach ($queries as $selector) {
            $this->elements = $oldStack;
            $delimiterBefore = false;
            foreach ($selector as $s) {
                // TAG
                $isTag = extension_loaded('mbstring') && phpQuery::$mbstringSupport
                    ? mb_ereg_match('^[\w|\||-]+$', $s) || $s == '*'
                    : preg_match('@^[\w|\||-]+$@', $s) || $s == '*';
                if ($isTag) {
                    if ($this->isXML()) {
                        // namespace support
                        if (mb_strpos($s, '|') !== false) {
                            $ns = $tag = null;
                            list($ns, $tag) = explode('|', $s);
                            $XQuery .= "$ns:$tag";
                        }
                        elseif ($s == '*') {
                            $XQuery .= "*";
                        }
                        else {
                            $XQuery .= "*[local-name()='$s']";
                        }
                    }
                    else {
                        $XQuery .= $s;
                    }
                }
                // ID
                elseif ($s[0] == '#') {
                    if ($delimiterBefore) {
                        $XQuery .= '*';
                    }
                    $XQuery .= "[@id='".substr($s, 1)."']";
                }
                // ATTRIBUTES
                elseif ($s[0] == '[') {
                    if ($delimiterBefore) {
                        $XQuery .= '*';
                    }
                    // strip side brackets
                    $attr = trim($s, '][');
                    $execute = false;
                    // attr with specified value
                    if (mb_strpos($s, '=')) {
                        list($attr, $value) = explode('=', $attr);
                        $value = trim($value, "'\"");
                        if ($this->isRegexp($attr)) {
                            // cut regexp character
                            $attr = substr($attr, 0, -1);
                            $execute = true;
                            $XQuery .= "[@{$attr}]";
                        }
                        else {
                            $XQuery .= "[@{$attr}='{$value}']";
                        }
                    }
                    // attr without specified value
                    else {
                        $XQuery .= "[@{$attr}]";
                    }

                    if ($execute) {
                        $this->runQuery($XQuery, $s, 'is');
                        $XQuery = '';
                        if (!$this->size()) {
                            break;
                        }
                    }
                }
                // CLASSES
                elseif ($s[0] == '.') {
                    // TODO: use return $this->find("./self::*[contains(concat(\" \",@class,\" \"), \" $class \")]");
                    // thx wizDom ;)
                    if ($delimiterBefore) {
                        $XQuery .= '*';
                    }
                    $XQuery .= '[@class]';

                    $this->runQuery($XQuery, $s, 'matchClasses');

                    $XQuery = '';
                    if (!$this->size()) {
                        break;
                    }
                }
                // ~ General Sibling Selector
                elseif ($s[0] == '~') {
                    $this->runQuery($XQuery);
                    $XQuery = '';
                    $this->elements = $this
                        ->siblings(substr($s, 1))
                        ->elements;
                    if (!$this->size()) {
                        break;
                    }
                }
                // + Adjacent sibling selectors
                elseif ($s[0] == '+') {
                    // TODO: /following-sibling::
                    $this->runQuery($XQuery);
                    $XQuery = '';

                    $subSelector = substr($s, 1);
                    $subElements = $this->elements;
                    $this->elements = [];
                    foreach ($subElements as $node) {
                        // search first DOMElement sibling
                        $test = $node->nextSibling;
                        while ($test && !($test instanceof DOMElement)) {
                            $test = $test->nextSibling;
                        }

                        if ($test && $this->is($subSelector, $test)) {
                            $this->elements[] = $test;
                        }
                    }
                    if (!$this->size()) {
                        break;
                    }
                }
                // PSEUDO CLASSES
                elseif ($s[0] == ':') {
                    // TODO: optimization for :first :last
                    if ($XQuery) {
                        $this->runQuery($XQuery);
                        $XQuery = '';
                    }
                    if (!$this->size()) {
                        break;
                    }
                    $this->pseudoClasses($s);
                    if (!$this->size()) {
                        break;
                    }
                }
                // DIRECT DESCENDANDS
                elseif ($s == '>') {
                    $XQuery .= '/';
                    $delimiterBefore = 2;
                }
                // ALL DESCENDANDS
                elseif ($s == ' ') {
                    $XQuery .= '//';
                    $delimiterBefore = 2;
                }
                // ERRORS
                else {
                    $this->debug("Unrecognized token '$s'");
                }
                $delimiterBefore = $delimiterBefore === 2;
            }

            // run query if any
            if ($XQuery && $XQuery != '//') {
                $this->runQuery($XQuery);
                $XQuery = '';
            }

            foreach ($this->elements as $node) {
                if (!$this->elementsContainsNode($node, $stack)) {
                    $stack[] = $node;
                }
            }
        }
        $this->elements = $stack;
        return $this->newInstance();
    }

    /**
     * pseudoClasses
     *
     * @param string $class CSS class name
     * @TODO: create API for classes with pseudo-selectors
     */
    protected function pseudoClasses($class)
    {
        // TODO clean args parsing ?
        $class = ltrim($class, ':');
        $haveArgs = mb_strpos($class, '(');
        $args = null;
        if ($haveArgs !== false) {
            $args = substr($class, $haveArgs + 1, -1);
            $class = substr($class, 0, $haveArgs);
        }

        switch ($class) {
            case 'even':
            case 'odd':
                $stack = [];
                foreach ($this->elements as $i => $node) {
                    if ($class == 'even' && ($i % 2) == 0) {
                        $stack[] = $node;
                    }
                    elseif ($class == 'odd' && $i % 2) {
                        $stack[] = $node;
                    }
                }
                $this->elements = $stack;
                break;
            case 'eq':
                $k = intval($args);
                $this->elements = isset($this->elements[$k]) ? [$this->elements[$k]] : [];
                break;
            case 'gt':
                $this->elements = array_slice($this->elements, $args + 1);
                break;
            case 'lt':
                $this->elements = array_slice($this->elements, 0, $args + 1);
                break;
            case 'first':
                if (isset($this->elements[0])) {
                    $this->elements = [$this->elements[0]];
                }
                break;
            case 'last':
                if ($this->elements) {
                    $this->elements = [$this->elements[count($this->elements) - 1]];
                }
                break;
            case 'contains':
                $text = trim($args, "\"'");
                $stack = [];
                foreach ($this->elements as $node) {
                    if (mb_stripos($node->textContent, $text) === false) {
                        continue;
                    }
                    $stack[] = $node;
                }
                $this->elements = $stack;
                break;
            case 'not':
                $selector = self::unQuote($args);
                $this->elements = $this->not($selector)->stack();
                break;
            case 'slice':
                // TODO: jQuery difference ?
                $args = explode(',',
                    str_replace(', ', ',', trim($args, "\"'"))
                );
                $start = $args[0];
                $end = isset($args[1]) ? $args[1] : null;
                if ($end > 0) {
                    $end = $end - $start;
                }
                $this->elements = array_slice($this->elements, $start, $end);
                break;
            case 'has':
                $selector = trim($args, "\"'");
                $stack = [];
                foreach ($this->stack(1) as $el) {
                    if ($this->find($selector, $el, true)->length) {
                        $stack[] = $el;
                    }
                }
                $this->elements = $stack;
                break;
            case 'submit':
            case 'reset':
                $this->elements = phpQuery::merge(
                    $this->map([$this, 'is'], "input[type=$class]", new CallbackParam()),
                    $this->map([$this, 'is'], "button[type=$class]", new CallbackParam())
                );
                break;
            //$stack = [];
            //foreach ($this->elements as $node) {
            //    if ($node->is('input[type=submit]') || $node->is('button[type=submit]')) {
            //        $stack[] = $el;
            //    }
            //}
            //$this->elements = $stack;
            //break;
            case 'input':
                $this->elements = $this->map([$this, 'is'], 'input', new CallbackParam())->elements;
                break;
            case 'password':
            case 'checkbox':
            case 'radio':
            case 'hidden':
            case 'image':
            case 'file':
                $this->elements = $this->map([$this, 'is'], "input[type=$class]", new CallbackParam())->elements;
                break;
            case 'parent':
                $this->elements = $this->map(
                    create_function(
                        '$node',
                        'return $node instanceof DOMELEMENT && $node->childNodes->length ? $node : null;'
                    )
                )->elements;
                break;
            //case 'parent':
            //    $stack = [];
            //    foreach ($this->elements as $node) {
            //        if ($node->childNodes->length) {
            //            $stack[] = $node;
            //        }
            //    }
            //    $this->elements = $stack;
            //    break;
            case 'empty':
                $this->elements = $this->map(
                    create_function(
                        '$node',
                        'return $node instanceof DOMELEMENT && $node->childNodes->length ? null : $node;'
                    )
                )->elements;
                break;
            case 'disabled':
            case 'selected':
            case 'checked':
                $this->elements = $this->map([$this, 'is'], "[$class]", new CallbackParam())->elements;
                break;
            case 'enabled':
                $this->elements = $this->map(
                    create_function(
                        '$node',
                        'return pq($node)->not(":disabled") ? $node : null;'
                    )
                )->elements;
                break;
            case 'header':
                $this->elements = $this->map(
                    create_function(
                        '$node',
                        '$isHeader = isset($node->tagName) && in_array($node->tagName, ["h1", "h2", "h3", "h4", "h5", "h6", "h7"]);
                        return $isHeader ? $node : null;'
                    )
                )->elements;
                //$this->elements = $this->map(
                //    create_function(
                //        '$node', 
                //        '$node = pq($node);
                //        return $node->is("h1")
                //            || $node->is("h2")
                //            || $node->is("h3")
                //            || $node->is("h4")
                //            || $node->is("h5")
                //            || $node->is("h6")
                //            || $node->is("h7")
                //            ? $node
                //            : null;'
                //    )
                //)->elements;
                break;
            case 'only-child':
                $this->elements = $this->map(
                    create_function(
                        '$node',
                        'return pq($node)->siblings()->size() == 0 ? $node : null;'
                    )
                )->elements;
                break;
            case 'first-child':
                $this->elements = $this->map(
                    create_function(
                        '$node',
                        'return pq($node)->prevAll()->size() == 0 ? $node : null;'
                    )
                )->elements;
                break;
            case 'last-child':
                $this->elements = $this->map(
                    create_function(
                        '$node',
                        'return pq($node)->nextAll()->size() == 0 ? $node : null;'
                    )
                )->elements;
                break;
            case 'nth-child':
                $param = trim($args, "\"'");
                if (!$param) {
                    break;
                }

                // nth-child(n+b) to nth-child(1n+b)
                if ($param{0} == 'n') {
                    $param = '1'.$param;
                }

                // :nth-child(index/even/odd/equation)
                if ($param == 'even' || $param == 'odd') {
                    $mapped = $this->map(
                        create_function(
                            '$node, $param',
                            '$index = pq($node)->prevAll()->size()+1;
                            if ($param == "even" && ($index%2) == 0) {
                                return $node;
                            }
                            else if ($param == "odd" && $index%2 == 1) {
                                return $node;
                            }
                            else {
                                return null;
                            }'
                        ),
                        new CallbackParam(),
                        $param
                    );
                }
                elseif (mb_strlen($param) > 1 && $param{1} == 'n') {
                    // an+b
                    $mapped = $this->map(
                        create_function(
                            '$node, $param',
                            '$prevs = pq($node)->prevAll()->size();
                            $index = $prevs + 1;
                            $b = mb_strlen($param) > 3 ? $param{3} : 0;
                            $a = $param{0};
                            if ($b && $param{2} == "-") {
                                $b = -$b;
                            }
                            if ($a > 0) {
                                return ($index-$b)%$a == 0 ? $node : null;
                                phpQuery::debug($a."*".floor($index/$a)."+$b-1 == ".($a*floor($index/$a)+$b-1)." ?= $prevs");
                                return $a*floor($index/$a)+$b-1 == $prevs ? $node : null;
                            }
                            else if ($a == 0) {
                                return $index == $b ? $node : null;
                            }
                            else {
                                // negative value
                                return $index <= $b ? $node : null;
                            }
                            //if (!$b) {
                            //    return $index%$a == 0 ? $node : null;
                            //}
                            //else {
                            //    return ($index-$b)%$a == 0 ? $node : null;
                            //}
                            '
                        ),
                        new CallbackParam(), $param
                    );
                }
                // index
                else {
                    $mapped = $this->map(
                        create_function('$node, $index',
                            '$prevs = pq($node)->prevAll()->size();
                            if ($prevs && $prevs == $index-1) {
                                return $node;
                            }
                            else if (! $prevs && $index == 1) {
                                return $node;
                            }
                            else {
                                return null;
                            }'
                        ),
                        new CallbackParam(),
                        $param
                    );
                    $this->elements = $mapped->elements;
                }
                break;
            default:
                $this->debug("Unknown pseudo-class '{$class}', skipping...");
        }
    }

    /**
     * Check Exist $selector in Elements or $nodes
     *
     * @param mixed              $selector
     * @param null|array|DOMNode $nodes
     * @return null|bool|array $nodes notEmpty return null or array, when else return bool
     */
    public function is($selector, $nodes = null)
    {
        $this->debug(["Is:", $selector]);

        if (!$selector) {
            return false;
        }

        $oldStack = $this->elements;
        if ($nodes && is_array($nodes)) {
            $this->elements = $nodes;
        }
        elseif ($nodes) {
            $this->elements = [$nodes];
        }

        $this->filter($selector, true);
        $stack = $this->elements;
        $this->elements = $oldStack;
        if ($nodes) {
            return !empty($stack) ? $stack : null;
        }
        return (bool)count($stack);
    }

    /**
     * filterCallback
     * jQuery difference.
     * Callback:
     * - $index int
     * - $node DOMNode
     *
     * @link http://docs.jquery.com/Traversing/filter
     * @return phpQueryObject
     */
    public function filterCallback($callback, $_skipHistory = false)
    {
        if (!$_skipHistory) {
            $this->debug("Filtering by callback");
            $this->elementsBackup = $this->elements;
        }

        $newStack = [];
        foreach ($this->elements as $index => $node) {
            $result = phpQuery::callbackRun($callback, [$index, $node]);
            if (is_null($result) || (!is_null($result) && $result)) {
                $newStack[] = $node;
            }
        }

        $this->elements = $newStack;
        return $_skipHistory
            ? $this
            : $this->newInstance();
    }

    /**
     * filter $selectors
     *
     * @link http://docs.jquery.com/Traversing/filter
     * @param mixed $selectors
     * @param bool  $_skipHistory
     * @return phpQueryObject
     */
    public function filter($selectors, $_skipHistory = false)
    {
        if ($selectors instanceof Callback || $selectors instanceof Closure) {
            return $this->filterCallback($selectors, $_skipHistory);
        }

        if (!$_skipHistory) {
            $this->elementsBackup = $this->elements;
        }

        $notSimpleSelector = [' ', '>', '~', '+', '/'];

        if (!is_array($selectors)) {
            $selectors = $this->parseSelector($selectors);
        }

        if (!$_skipHistory) {
            $this->debug(["Filtering:", $selectors]);
        }

        $finalStack = [];
        foreach ($selectors as $selector) {
            $stack = [];
            if (!$selector) {
                break;
            }

            // avoid first space or /
            if (in_array($selector[0], $notSimpleSelector)) {
                $selector = array_slice($selector, 1);
            }

            // PER NODE selector chunks
            foreach ($this->stack() as $node) {
                $break = false;
                foreach ($selector as $s) {
                    // all besides DOMElement
                    if (!($node instanceof DOMElement)) {
                        if ($s[0] == '[') {
                            $attr = trim($s, '[]');
                            if (mb_strpos($attr, '=')) {
                                list($attr, $val) = explode('=', $attr);
                                if ($attr == 'nodeType' && $node->nodeType != $val) {
                                    $break = true;
                                }
                            }
                        }
                        else {
                            $break = true;
                        }
                    }
                    // DOMElement only
                    else {
                        // ID
                        if ($s[0] == '#') {
                            if ($node->getAttribute('id') != substr($s, 1)) {
                                $break = true;
                            }
                        }
                        // CLASSES
                        elseif ($s[0] == '.') {
                            if (!$this->matchClasses($s, $node)) {
                                $break = true;
                            }
                        }
                        // ATTRS
                        elseif ($s[0] == '[') {
                            // strip side brackets
                            $attr = trim($s, '[]');
                            if (mb_strpos($attr, '=')) {
                                list($attr, $val) = explode('=', $attr);
                                $val = self::unQuote($val);
                                if ($attr == 'nodeType') {
                                    if ($val != $node->nodeType) {
                                        $break = true;
                                    }
                                }
                                elseif ($this->isRegexp($attr)) {
                                    $val = extension_loaded('mbstring') && phpQuery::$mbstringSupport
                                        ? quotemeta(trim($val, '"\''))
                                        : preg_quote(trim($val, '"\''), '@');
                                    // switch last character
                                    switch (substr($attr, -1)) {
                                        // quote-meta used insted of preg_quote
                                        // http://code.google.com/p/phpquery/issues/detail?id=76
                                        case '^':
                                            $pattern = '^'.$val;
                                            break;
                                        case '*':
                                            $pattern = '.*'.$val.'.*';
                                            break;
                                        case '$':
                                            $pattern = '.*'.$val.'$';
                                            break;
                                    }

                                    // cut last character
                                    $attr = substr($attr, 0, -1);
                                    $isMatch = extension_loaded('mbstring') && phpQuery::$mbstringSupport
                                        ? mb_ereg_match($pattern, $node->getAttribute($attr))
                                        : preg_match("@{$pattern}@", $node->getAttribute($attr));
                                    if (!$isMatch) {
                                        $break = true;
                                    }
                                }
                                elseif ($node->getAttribute($attr) != $val) {
                                    $break = true;
                                }
                            }
                            elseif (!$node->hasAttribute($attr)) {
                                $break = true;
                            }
                        }
                        // PSEUDO CLASSES
                        elseif ($s[0] == ':') {
                            // skip
                        }
                        // TAG
                        elseif (trim($s)) {
                            if ($s != '*') {
                                // TODO:  namespaces
                                if (isset($node->tagName)) {
                                    if ($node->tagName != $s) {
                                        $break = true;
                                    }
                                }
                                elseif ($s == 'html' && !$this->isRoot($node)) {
                                    $break = true;
                                }
                            }
                        }
                        // AVOID NON-SIMPLE SELECTORS
                        elseif (in_array($s, $notSimpleSelector)) {
                            $break = true;
                            $this->debug(['Skipping non simple selector', $selector]);
                        }
                    }

                    if ($break) {
                        break;
                    }
                }

                // if element passed all chunks of selector - add it to new stack
                if (!$break) {
                    $stack[] = $node;
                }
            }

            $tmpStack = $this->elements;
            $this->elements = $stack;

            // PER ALL NODES selector chunks
            foreach ($selector as $s) {
                // PSEUDO CLASSES
                if ($s[0] == ':') {
                    $this->pseudoClasses($s);
                }
            }

            foreach ($this->elements as $node) {
                // XXX it should be merged without duplicates
                // but jQuery does not do that
                $finalStack[] = $node;
            }
            $this->elements = $tmpStack;
        }

        $this->elements = $finalStack;
        if ($_skipHistory) {
            return $this;
        }
        else {
            $this->debug("Stack length after filter(): ".count($finalStack));
            return $this->newInstance();
        }
    }

    /**
     * unQuote
     *
     * @param string|array $value
     * @return false|string
     * @TODO implement in all methods using passed parameters
     */
    protected static function unQuote($value)
    {
        return $value[0] == '\'' || $value[0] == '"'
            ? substr($value, 1, -1)
            : $value;
    }

    /**
     * load data by url
     *
     * @link http://docs.jquery.com/Ajax/load
     * @return phpQueryObject
     * @TODO Support $selector
     */
    public function load($url, $data = null, $callback = null)
    {
        if ($data && !is_array($data)) {
            $callback = $data;
            $data = null;
        }

        if (mb_strpos($url, ' ') !== false) {
            $matches = null;
            if (extension_loaded('mbstring') && phpQuery::$mbstringSupport) {
                mb_ereg('^([^ ]+) (.*)$', $url, $matches);
            }
            else {
                preg_match('^\(\[^ ]+) (.*)$', $url, $matches);
            }
            $url = $matches[1];
            $selector = $matches[2];
            // FIXME this sucks, pass as callback param
            $this->_loadSelector = $selector;
        }
        $ajax = [
            'url'      => $url,
            'type'     => $data ? 'POST' : 'GET',
            'data'     => $data,
            'complete' => $callback,
            'success'  => [$this, '__loadSuccess']
        ];
        phpQuery::ajax($ajax);
        return $this;
    }

    /**
     * Callback load Success
     *
     * @param mixed $html
     */
    public function __loadSuccess($html)
    {
        if (isset($this->_loadSelector) && $this->_loadSelector) {
            $html = phpQuery::newDocument($html)->find($this->_loadSelector);
            unset($this->_loadSelector);
        }
        foreach ($this->stack(1) as $node) {
            phpQuery::pq($node, $this->getDocumentID())
                ->markup($html);
        }
    }

    /**
     * Trigger a type of event on every matched element.
     *
     * @param mixed $type
     * @param mixed $data
     * @return phpQueryObject
     * @TODO support more than event in $type (space-separated)
     */
    public function trigger($type, $data = [])
    {
        foreach ($this->elements as $node) {
            phpQueryEvents::trigger($this->getDocumentID(), $type, $data, $node);
        }
        return $this;
    }

    /**
     * This particular method triggers all bound event handlers on an element (for a specific event type) WITHOUT executing the browsers default actions.
     *
     * @param mixed $type
     * @param array $data
     * @return phpQueryObject
     * @TODO Implement
     */
    public function triggerHandler($type, $data = [])
    {
        // TODO: Implement
        return $this;
    }

    /**
     * Binds a handler to one or more events (like click) for each matched element.
     * Can also bind custom events.
     *
     * @param mixed      $type
     * @param mixed      $data Optional
     * @param null|mixed $callback
     * @return phpQueryObject
     * @TODO support '!' (exclusive) events
     * @TODO support more than event in $type (space-separated)
     */
    public function bind($type, $data, $callback = null)
    {
        // TODO: check if $data is callable, not using is_callable
        if (!isset($callback)) {
            $callback = $data;
            $data = null;
        }

        foreach ($this->elements as $node) {
            phpQueryEvents::add($this->getDocumentID(), $node, $type, $data, $callback);
        }

        return $this;
    }

    /**
     * unbind handler
     *
     * @param null|mixed $type
     * @param null|mixed $callback
     * @return phpQueryObject
     * @TODO namespace events
     * @TODO support more than event in $type (space-separated)
     */
    public function unbind($type = null, $callback = null)
    {
        foreach ($this->elements as $node) {
            phpQueryEvents::remove($this->getDocumentID(), $node, $type, $callback);
        }

        return $this;
    }

    /**
     * bind 'change' event handler
     *
     * @param null|mixed $callback
     * @return phpQueryObject
     */
    public function change($callback = null)
    {
        if ($callback) {
            return $this->bind('change', $callback);
        }
        return $this->trigger('change');
    }

    /**
     * bind 'submit' event handler
     *
     * @param null|mixed $callback
     * @return phpQueryObject
     */
    public function submit($callback = null)
    {
        if ($callback) {
            return $this->bind('submit', $callback);
        }
        return $this->trigger('submit');
    }

    /**
     * bind 'click' event handler
     *
     * @param null|mixed $callback
     * @return phpQueryObject
     */
    public function click($callback = null)
    {
        if ($callback) {
            return $this->bind('click', $callback);
        }
        return $this->trigger('click');
    }

    /**
     * wrapAllOld
     *
     * @param string|phpQueryObject $wrapper
     * @return phpQueryObject
     */
    public function wrapAllOld($wrapper)
    {
        $wrapper = pq($wrapper)->_clone();
        if (!$wrapper->size() || !$this->size()) {
            return $this;
        }

        $wrapper->insertBefore($this->elements[0]);
        $deepest = $wrapper->elements[0];
        while (!empty($deepest->firstChild) && $deepest->firstChild instanceof DOMElement) {
            $deepest = $deepest->firstChild;
        }
        pq($deepest)->append($this);
        return $this;
    }

    /**
     * wrapAll
     *
     * @param string|phpQueryObject $wrapper
     * @return phpQueryObject
     * @TODO test me ...
     */
    public function wrapAll($wrapper)
    {
        if (!$this->size()) {
            return $this;
        }

        return phpQuery::pq($wrapper, $this->getDocumentID())
            ->clone()
            ->insertBefore($this->get(0))
            ->map([$this, '___wrapAllCallback'])
            ->append($this);
    }

    /**
     * ___wrapAllCallback
     *
     * @param DOMElement|DOMNode $node
     * @return DOMElement|DOMNode
     */
    public function ___wrapAllCallback($node)
    {
        $deepest = $node;
        while (!empty($deepest->firstChild) && $deepest->firstChild instanceof DOMElement) {
            $deepest = $deepest->firstChild;
        }
        return $deepest;
    }

    /**
     * wrapAllPHP
     *
     * @param string|phpQuery $codeBefore
     * @param string|phpQuery $codeAfter
     * @return phpQueryObject
     */
    public function wrapAllPHP($codeBefore, $codeAfter)
    {
        return $this
            ->slice(0, 1)
            ->beforePHP($codeBefore)
            ->end()
            ->slice(-1)
            ->afterPHP($codeAfter)
            ->end();
    }

    /**
     * wrap
     *
     * @param string|phpQuery $wrapper
     * @return phpQueryObject
     * @throws Exception
     */
    public function wrap($wrapper)
    {
        foreach ($this->stack() as $node) {
            phpQuery::pq($node, $this->getDocumentID())->wrapAll($wrapper);
        }
        return $this;
    }

    /**
     * wrapPHP
     *
     * @param string|phpQuery $codeBefore
     * @param string|phpQuery $codeAfter
     * @return phpQueryObject
     */
    public function wrapPHP($codeBefore, $codeAfter)
    {
        foreach ($this->stack() as $node) {
            phpQuery::pq($node, $this->getDocumentID())->wrapAllPHP($codeBefore, $codeAfter);
        }
        return $this;
    }

    /**
     * wrapInner
     *
     * @param string|phpQuery $wrapper
     * @return phpQueryObject
     */
    public function wrapInner($wrapper)
    {
        foreach ($this->stack() as $node) {
            phpQuery::pq($node, $this->getDocumentID())->contents()->wrapAll($wrapper);
        }
        return $this;
    }

    /**
     * wrapInnerPHP
     *
     * @param string|phpQuery $codeBefore
     * @param string|phpQuery $codeAfter
     * @return phpQueryObject
     */
    public function wrapInnerPHP($codeBefore, $codeAfter)
    {
        foreach ($this->stack(1) as $node) {
            phpQuery::pq($node, $this->getDocumentID())->contents()
                ->wrapAllPHP($codeBefore, $codeAfter);
        }
        return $this;
    }

    /**
     * contents
     *
     * @return phpQueryObject
     * @TODO: test me Support for text nodes
     */
    public function contents()
    {
        $stack = [];
        $nodes = $this->getListDOMElementFromStack();
        foreach ($nodes as $el) {
            // FIXME (fixed) http://code.google.com/p/phpquery/issues/detail?id=56
            if (!isset($el->childNodes)) {
                continue;
            }

            foreach ($el->childNodes as $node) {
                $stack[] = $node;
            }
        }
        return $this->newInstance($stack);
    }

    /**
     * contentsUnwrap
     *
     * @return phpQueryObject
     */
    public function contentsUnwrap()
    {
        $nodes = $this->getListDOMElementFromStack();
        foreach ($nodes as $node) {
            if (!$node->parentNode) {
                continue;
            }

            $childNodes = [];
            // any modification in DOM tree breaks childNodes iteration, so cache them first
            foreach ($node->childNodes as $chNode) {
                $childNodes[] = $chNode;
            }

            foreach ($childNodes as $chNode) {
                //$node->parentNode->appendChild($chNode);
                $node->parentNode->insertBefore($chNode, $node);
            }
            $node->parentNode->removeChild($node);
        }
        return $this;
    }

    /**
     * switchWith $markup
     *
     * @param mixed $markup
     * @return phpQueryObject
     */
    public function switchWith($markup)
    {
        $markup = pq($markup, $this->getDocumentID());
        $content = null;
        $nodes = $this->getListDOMElementFromStack();
        foreach ($nodes as $node) {
            pq($node)
                ->contents()->toReference($content)->end()
                ->replaceWith($markup->clone()->append($content));
        }
        return $this;
    }

    /**
     * NewInstance included Nodes key = $num
     *
     * @return phpQueryObject
     */
    public function eq($num)
    {
        $oldStack = $this->elements;
        $this->elementsBackup = $this->elements;
        $this->elements = [];
        if (isset($oldStack[$num])) {
            $this->elements[] = $oldStack[$num];
        }
        return $this->newInstance();
    }

    /**
     * Nodes(Elements) size
     *
     * @return int
     */
    public function size()
    {
        return count($this->elements);
    }

    /**
     * Nodes size
     *
     * @return int
     * @deprecated Use length as attribute
     */
    public function length()
    {
        return $this->size();
    }

    /**
     * Count elements of an object
     *
     * @link https://php.net/manual/en/countable.count.php
     * @return int The return value is cast to an integer.
     */
    public function count()
    {
        return $this->size();
    }

    /**
     * Enter description here...
     *
     * @return phpQueryObject
     * @TODO $level
     */
    public function end($level = 1)
    {
        //$this->elements = array_pop( $this->history );
        //return $this;
        //$this->previous->DOM = $this->DOM;
        //$this->previous->XPath = $this->XPath;
        return !empty($this->previous)
            ? $this->previous
            : $this;
    }

    /**
     * Normal use ->clone() .
     *
     * @return phpQueryObject
     */
    public function _clone()
    {
        $newStack = [];
        //pr(array('copy... ', $this->whois()));
        //$this->dumpHistory('copy');
        $this->elementsBackup = $this->elements;
        foreach ($this->elements as $node) {
            $newStack[] = $node->cloneNode(true);
        }
        $this->elements = $newStack;
        return $this->newInstance();
    }

    /**
     * replaceWithPHP $code
     *
     * @param string|mixed $code
     * @return phpQueryObject
     */
    public function replaceWithPHP($code)
    {
        return $this->replaceWith(phpQuery::php($code));
    }

    /**
     * replaceWith $content
     *
     * @link http://docs.jquery.com/Manipulation/replaceWith#content
     * @param String|phpQuery $content
     * @return phpQueryObject
     */
    public function replaceWith($content)
    {
        return $this->after($content)->remove();
    }

    /**
     * replaceAll $selector
     *
     * @param string|mixed $selector
     * @return phpQueryObject
     * @TODO this works ?
     */
    public function replaceAll($selector)
    {
        foreach (phpQuery::pq($selector, $this->getDocumentID()) as $node) {
            phpQuery::pq($node, $this->getDocumentID())
                ->after($this->_clone())
                ->remove();
        }
        return $this;
    }

    /**
     * Remove Node by $selector
     *
     * @param null|mixed $selector
     * @return phpQueryObject
     */
    public function remove($selector = null)
    {
        $loop = $selector
            ? $this->filter($selector)->elements
            : $this->elements;

        foreach ($loop as $node) {
            if (!$node->parentNode) {
                continue;
            }
            if (isset($node->tagName)) {
                $this->debug("Removing '{$node->tagName}'");
            }
            $node->parentNode->removeChild($node);

            // Mutation event
            $event = new DOMEvent([
                'target' => $node,
                'type'   => 'DOMNodeRemoved'
            ]);

            phpQueryEvents::trigger($this->getDocumentID(), $event->type, [$event], $node);
        }
        return $this;
    }

    /**
     * markupEvents
     *
     * @param mixed              $newMarkup
     * @param mixed              $oldMarkup
     * @param DOMElement|DOMNode $node
     * @return void
     */
    protected function markupEvents($newMarkup, $oldMarkup, $node)
    {
        if ($node->tagName == 'textarea' && $newMarkup != $oldMarkup) {
            $event = new DOMEvent([
                'target' => $node,
                'type'   => 'change'
            ]);

            phpQueryEvents::trigger($this->getDocumentID(), $event->type, [$event], $node);
        }
    }

    /**
     * markup
     *
     * @param null|string|mixed    $markup
     * @param null|string|callable $callback1
     * @param null|string|callable $callback2
     * @param null|string|callable $callback3
     * @return string
     * @TODO trigger change event for textarea
     */
    public function markup($markup = null, $callback1 = null, $callback2 = null, $callback3 = null)
    {
        $args = func_get_args();
        if ($this->documentWrapper->isXML) {
            return call_user_func_array([$this, 'xml'], $args);
        }
        else {
            return call_user_func_array([$this, 'html'], $args);
        }
    }

    /**
     * markupOuter
     *
     * @param null|string|callable $callback1
     * @param null|string|callable $callback2
     * @param null|string|callable $callback3
     * @return string
     */
    public function markupOuter($callback1 = null, $callback2 = null, $callback3 = null)
    {
        $args = func_get_args();
        if ($this->documentWrapper->isXML) {
            return call_user_func_array([$this, 'xmlOuter'], $args);
        }
        else {
            return call_user_func_array([$this, 'htmlOuter'], $args);
        }
    }

    /**
     * html
     *
     * @param null|string          $html
     * @param null|string|callable $callback1
     * @param null|string|callable $callback2
     * @param null|string|callable $callback3
     * @return string
     * @TODO force html result
     */
    public function html($html = null, $callback1 = null, $callback2 = null, $callback3 = null)
    {
        if (isset($html)) {
            // INSERT
            $nodes = $this->documentWrapper->import($html);
            $this->empty();
            foreach ($this->getListDOMElementFromStack() as $alreadyAdded => $node) {
                $oldHtml = '';
                // for now, limit events for textarea
                if (($this->isXHTML() || $this->isHTML()) && $node->tagName == 'textarea') {
                    $oldHtml = pq($node, $this->getDocumentID())->markup();
                }

                foreach ($nodes as $newNode) {
                    $node->appendChild($alreadyAdded
                        ? $newNode->cloneNode(true)
                        : $newNode
                    );
                }

                // for now, limit events for textarea
                if (($this->isXHTML() || $this->isHTML()) && $node->tagName == 'textarea') {
                    $this->markupEvents($html, $oldHtml, $node);
                }
            }
            return $this->__toString(); // $this 
        }
        else {
            // FETCH
            $return = $this->documentWrapper->markup($this->elements, true);
            $args = func_get_args();
            foreach (array_slice($args, 1) as $callback) {
                $return = phpQuery::callbackRun($callback, [$return]);
            }
            return $return;
        }
    }

    /**
     * xml
     *
     * @param null|string          $xml
     * @param null|string|callable $callback1
     * @param null|string|callable $callback2
     * @param null|string|callable $callback3
     * @return string
     * @TODO force xml result
     */
    public function xml($xml = null, $callback1 = null, $callback2 = null, $callback3 = null)
    {
        $args = func_get_args();
        return call_user_func_array([$this, 'html'], $args);
    }

    /**
     * htmlOuter
     *
     * @param null|string|callable $callback1
     * @param null|string|callable $callback2
     * @param null|string|callable $callback3
     * @return string
     * @TODO force html result
     */
    public function htmlOuter($callback1 = null, $callback2 = null, $callback3 = null)
    {
        $markup = $this->documentWrapper->markup($this->elements);
        // pass thou callbacks
        $args = func_get_args();
        foreach ($args as $callback) {
            $markup = phpQuery::callbackRun($callback, [$markup]);
        }
        return $markup;
    }

    /**
     * xmlOuter
     *
     * @param null|string|callable $callback1
     * @param null|string|callable $callback2
     * @param null|string|callable $callback3
     * @return string
     * @TODO force xml result
     */
    public function xmlOuter($callback1 = null, $callback2 = null, $callback3 = null)
    {
        $args = func_get_args();
        return call_user_func_array([$this, 'htmlOuter'], $args);
    }

    /**
     * Convert this class to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->markupOuter();
    }

    /**
     * Just like html(), but returns markup with VALID (dangerous) PHP tags.
     *
     * @param null|mixed $code
     * @return string
     * @TODO support returning markup with PHP tags when called without param
     */
    public function php($code = null)
    {
        return $this->markupPHP($code);
    }

    /**
     * Converts document markup containing PHP code
     *
     * @param null|mixed $code
     * @return string
     */
    public function markupPHP($code = null)
    {
        return isset($code)
            ? $this->markup(phpQuery::php($code))
            : phpQuery::markupToPHP($this->markup());
    }

    /**
     * Converts document markup containing PHP code
     *
     * @return string
     */
    public function markupOuterPHP()
    {
        return phpQuery::markupToPHP($this->markupOuter());
    }

    /**
     * NewInstance with children Nodes
     *
     * @param null|mixed $selector
     * @return phpQueryObject
     */
    public function children($selector = null)
    {
        $stack = [];

        $nodes = $this->getListDOMElementFromStack();
        foreach ($nodes as $node) {
            //foreach($node->getElementsByTagName('*') as $newNode) {
            foreach ($node->childNodes as $newNode) {
                if ($newNode->nodeType != 1) {
                    continue;
                }
                if ($selector && !$this->is($selector, $newNode)) {
                    continue;
                }
                if ($this->elementsContainsNode($newNode, $stack)) {
                    continue;
                }
                $stack[] = $newNode;
            }
        }

        $this->elementsBackup = $this->elements;
        $this->elements = $stack;

        return $this->newInstance();
    }

    /**
     * Ancestors NewInstance
     *
     * @param null|mixed $selector
     * @return phpQueryObject
     */
    public function ancestors($selector = null)
    {
        return $this->children($selector);
    }

    /**
     * Internal insert method (append)
     * (with $target is $content)
     *
     * @param mixed $content
     * @return phpQueryObject
     */
    public function append($content)
    {
        return $this->insert($content, __FUNCTION__);
    }

    /**
     * Internal insert method (append)
     * (with $target is $content php tag)
     *
     * @param mixed $content
     * @return phpQueryObject
     */
    public function appendPHP($content)
    {
        return $this->insert("<php><!-- {$content} --></php>", 'append');
    }

    /**
     * Internal insert method (appendTo)
     * (with $target is $selector)
     *
     * @param mixed $selector
     * @return phpQueryObject
     */
    public function appendTo($selector)
    {
        return $this->insert($selector, __FUNCTION__);
    }

    /**
     * Internal insert method (prepend)
     * (with $target is $content)
     *
     * @param mixed $content
     * @return phpQueryObject
     */
    public function prepend($content)
    {
        return $this->insert($content, __FUNCTION__);
    }

    /**
     * Internal insert method (prepend)
     * (with $target is $content php tag)
     *
     * @param mixed $content
     * @return phpQueryObject
     * @TODO accept many arguments, which are joined, arrays maybe also
     */
    public function prependPHP($content)
    {
        return $this->insert("<php><!-- {$content} --></php>", 'prepend');
    }

    /**
     * Internal insert method (prependTo)
     * (with $target is $selector)
     *
     * @param mixed $selector
     * @return phpQueryObject
     */
    public function prependTo($selector)
    {
        return $this->insert($selector, __FUNCTION__);
    }

    /**
     * Internal insert method (before)
     * (with $target is $content)
     *
     * @param mixed $content
     * @return phpQueryObject
     */
    public function before($content)
    {
        return $this->insert($content, __FUNCTION__);
    }

    /**
     * Internal insert method (before)
     * (with $target is $content php tag)
     *
     * @param mixed $content
     * @return phpQueryObject
     */
    public function beforePHP($content)
    {
        return $this->insert("<php><!-- {$content} --></php>", 'before');
    }

    /**
     * Internal insert method (insertBefore)
     * (with $target is $selector)
     *
     * @param mixed $selector
     * @return phpQueryObject
     */
    public function insertBefore($selector)
    {
        return $this->insert($selector, __FUNCTION__);
    }

    /**
     * Internal insert method (after)
     * (with $target is $content)
     *
     * @param mixed $content
     * @return phpQueryObject
     */
    public function after($content)
    {
        return $this->insert($content, __FUNCTION__);
    }

    /**
     * Internal insert method (after)
     * (with $target is $content php tag)
     *
     * @param mixed $content
     * @return phpQueryObject
     */
    public function afterPHP($content)
    {
        return $this->insert("<php><!-- {$content} --></php>", 'after');
    }

    /**
     * Internal insert method (insertAfter)
     * (with $target is $selector)
     *
     * @param mixed $selector
     * @return phpQueryObject
     */
    public function insertAfter($selector)
    {
        return $this->insert($selector, __FUNCTION__);
    }

    /**
     * Internal insert method. Don't use it.
     *
     * @param mixed $target
     * @param mixed $type
     * @return phpQueryObject
     * @throws Exception
     */
    public function insert($target, $type)
    {
        $this->debug("Inserting data with '{$type}'");

        $to = false;
        switch ($type) {
            case 'appendTo':
            case 'prependTo':
            case 'insertBefore':
            case 'insertAfter':
                $to = true;
                break;
        }

        $insertFrom = $insertTo = [];
        switch (gettype($target)) {
            case 'string':
                // INSERT TO
                if ($to) {
                    $insertFrom = $this->elements;
                    if (phpQuery::isMarkup($target)) {
                        // $target is new markup, import it
                        $insertTo = $this->documentWrapper->import($target);
                    }
                    // insert into selected element
                    else {
                        // $target is a selector
                        $thisStack = $this->elements;
                        $this->toRoot();
                        $insertTo = $this->find($target)->elements;
                        $this->elements = $thisStack;
                    }
                }
                // INSERT FROM
                else {
                    $insertTo = $this->elements;
                    $insertFrom = $this->documentWrapper->import($target);
                }
                break;
            case 'object':
                $insertFrom = $insertTo = [];
                // phpQuery
                if ($target instanceof self) {
                    if ($to) {
                        $insertTo = $target->elements;
                        if ($this->documentFragment && $this->stackIsRoot()) {
                            // get all body children
                            //$loop = $this->find('body > *')->elements;
                            // TODO: test it, test it hard...
                            //$loop = $this->newInstance($this->root)->find('> *')->elements;
                            $loop = $this->root->childNodes;
                        }
                        else {
                            $loop = $this->elements;
                        }

                        // import nodes if needed
                        $insertFrom = ($this->getDocumentID() == $target->getDocumentID())
                            ? $loop
                            : $target->documentWrapper->import($loop);
                    }
                    else {
                        $insertTo = $this->elements;
                        if ($target->documentFragment && $target->stackIsRoot()) {
                            // get all body children
                            //$loop = $target->find('body > *')->elements;
                            $loop = $target->root->childNodes;
                        }
                        else {
                            $loop = $target->elements;
                        }

                        // import nodes if needed
                        $insertFrom = ($this->getDocumentID() == $target->getDocumentID())
                            ? $loop
                            : $this->documentWrapper->import($loop);
                    }
                }
                // DOMNode
                elseif ($target instanceof DOMNode) {
                    // import node if needed
                    //if ($target->ownerDocument != $this->DOM) {
                    //    $target = $this->DOM->importNode($target, true);
                    //}
                    if ($to) {
                        $insertTo = [$target];
                        if ($this->documentFragment && $this->stackIsRoot()) {
                            // get all body children
                            $loop = $this->root->childNodes;
                            //$loop = $this->find('body > *')->elements;
                        }
                        else {
                            $loop = $this->elements;
                        }

                        foreach ($loop as $fromNode) {
                            // import nodes if needed
                            $insertFrom[] = !$fromNode->ownerDocument->isSameNode($target->ownerDocument)
                                ? $target->ownerDocument->importNode($fromNode, true)
                                : $fromNode;
                        }
                    }
                    else {
                        // import node if needed
                        if (!$target->ownerDocument->isSameNode($this->document)) {
                            $target = $this->document->importNode($target, true);
                        }
                        $insertTo = $this->elements;
                        $insertFrom[] = $target;
                    }
                }
                break;
        }

        phpQuery::debug("From ".count($insertFrom)."; To ".count($insertTo)." nodes");

        foreach ($insertTo as $insertNumber => $toNode) {
            $firstChild = $nextSibling = null;

            // we need static relative elements in some cases
            switch ($type) {
                case 'prependTo':
                case 'prepend':
                    $firstChild = $toNode->firstChild;
                    break;
                case 'insertAfter':
                case 'after':
                    $nextSibling = $toNode->nextSibling;
                    break;
            }

            foreach ($insertFrom as $fromNode) {
                // clone if inserted already before
                $insert = $insertNumber
                    ? $fromNode->cloneNode(true)
                    : $fromNode;

                switch ($type) {
                    case 'appendTo':
                    case 'append':
                        //$toNode->insertBefore($fromNode, $toNode->lastChild->nextSibling);
                        $toNode->appendChild($insert);
                        break;
                    case 'prependTo':
                    case 'prepend':
                        $toNode->insertBefore($insert, $firstChild);
                        break;
                    case 'insertBefore':
                    case 'before':
                        if (!$toNode->parentNode) {
                            throw new Exception("No parentNode, can't do {$type}()");
                        }
                        else {
                            $toNode->parentNode->insertBefore($insert, $toNode);
                        }
                        break;
                    case 'insertAfter':
                    case 'after':
                        if (!$toNode->parentNode) {
                            throw new Exception("No parentNode, can't do {$type}()");
                        }
                        else {
                            $toNode->parentNode->insertBefore($insert, $nextSibling);
                        }
                        break;
                }

                // Mutation event
                $event = new DOMEvent([
                    'target' => $insert,
                    'type'   => 'DOMNodeInserted'
                ]);

                phpQueryEvents::trigger($this->getDocumentID(), $event->type, [$event], $insert);
            }
        }
        return $this;
    }

    /**
     * Get index of Node from Nodes
     *
     * @param DOMNode|phpQueryObject $subject
     * @return int -1: not found
     */
    public function index($subject)
    {
        $index = -1;
        $subject = $subject instanceof phpQueryObject
            ? $subject->elements[0]
            : $subject;
        foreach ($this->newInstance() as $k => $node) {
            if ($node->isSameNode($subject)) {
                $index = $k;
            }
        }
        return $index;
    }

    /**
     * NewInstance with slice Nodes from $start to $end
     *
     * @param int      $start
     * @param null|int $end
     * @return phpQueryObject
     * @TODO test me
     */
    public function slice($start, $end = null)
    {
        //$last = count($this->elements) - 1;
        //$end = $end
        //    ? min($end, $last)
        //    : $last;
        //if ($start < 0) {
        //    $start = $last + $start;
        //}
        //if ($start > $last) {
        //    return [];
        //}

        if ($end > 0) {
            $end = $end - $start;
        }
        return $this->newInstance(
            array_slice($this->elements, $start, $end)
        );
    }

    /**
     * NewInstance with Nodes reverse
     *
     * @return phpQueryObject
     */
    public function reverse()
    {
        $this->elementsBackup = $this->elements;
        $this->elements = array_reverse($this->elements);
        return $this->newInstance();
    }

    /**
     * Return joined text content.
     *
     * @return string
     */
    public function text($text = null, $callback1 = null, $callback2 = null, $callback3 = null)
    {
        if (isset($text)) {
            return $this->html(htmlspecialchars($text));
        }

        $args = func_get_args();
        $args = array_slice($args, 1);
        $return = '';
        foreach ($this->elements as $node) {
            $text = $node->textContent;
            if (count($this->elements) > 1 && $text) {
                $text .= "\n";
            }
            foreach ($args as $callback) {
                $text = phpQuery::callbackRun($callback, [$text]);
            }
            $return .= $text;
        }
        return $return;
    }

    /**
     * Deprecated, use $pq->plugin() instead.
     *
     * @return bool
     * @deprecated
     */
    public static function plugin($class, $file = null)
    {
        return phpQuery::plugin($class, $file);
    }

    /**
     * Deprecated, use $pq->plugin() instead.
     *
     * @return bool
     * @deprecated
     */
    public static function extend($class, $file = null)
    {
        return self::plugin($class, $file);
    }

    /**
     * __call
     *
     * @param string $method
     * @param mixed  $args
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $args)
    {
        $aliasMethods = ['clone', 'empty'];
        if (isset(phpQuery::$extendMethods[$method])) {
            array_unshift($args, $this);

            return phpQuery::callbackRun(phpQuery::$extendMethods[$method], $args);
        }
        elseif (isset(phpQuery::$pluginsMethods[$method])) {
            array_unshift($args, $this);
            $class = phpQuery::$pluginsMethods[$method];
            $realClass = "phpQueryObjectPlugin_$class";
            $return = call_user_func_array([$realClass, $method], $args);

            // XXX deprecate ?
            return is_null($return)
                ? $this
                : $return;
        }
        elseif (in_array($method, $aliasMethods)) {
            return call_user_func_array([$this, '_'.$method], $args);
        }
        else {
            throw new Exception("Method '{$method}' doesnt exist");
        }
    }

    /**
     * Safe rename of next().
     * Use it ONLY when need to call next() on an iterated object (in same time).
     * Normally there is no need to do such thing ;)
     *
     * @param null|mixed $selector
     * @return phpQueryObject
     */
    public function _next($selector = null)
    {
        return $this->newInstance(
            $this->getElementSiblings('nextSibling', $selector, true)
        );
    }

    /**
     * Use prev() and next().
     *
     * @param null|mixed $selector
     * @return phpQueryObject
     * @deprecated
     */
    public function _prev($selector = null)
    {
        return $this->prev($selector);
    }

    /**
     * Move forward to next element
     * (Double-function method.)
     * First: main iterator interface method.
     * Second: Returning next sibling, alias for _next().
     * Proper functionality is choosed automagically.
     *
     * @link https://php.net/manual/en/iterator.next.php
     * @param null|mixed $cssSelector
     * @return phpQueryObject|void
     * @see  phpQueryObject::_next()
     */
    public function next($cssSelector = null)
    {
        //if ($cssSelector || $this->valid) {
        //    return $this->_next($cssSelector);
        //}

        $this->valid = isset($this->elementsInterator[$this->current + 1]);
        if (!$this->valid && $this->elementsInterator) {
            $this->elementsInterator = null;
        }
        elseif ($this->valid) {
            $this->current++;
        }
        else {
            return $this->_next($cssSelector);
        }
    }

    /**
     * Move forward to previous element
     *
     * @param null|mixed $selector
     * @return phpQueryObject
     */
    public function prev($selector = null)
    {
        return $this->newInstance(
            $this->getElementSiblings('previousSibling', $selector, true)
        );
    }

    /**
     * NewInstance included all previousSibling $selector
     *
     * @param null|mixed $selector
     * @return phpQueryObject
     * @TODO FIXME: returns source elements instead of previous siblings
     */
    public function prevAll($selector = null)
    {
        return $this->newInstance(
            $this->getElementSiblings('previousSibling', $selector)
        );
    }

    /**
     * NewInstance included all nextSibling $selector
     *
     * @param null|mixed $selector
     * @return phpQueryObject
     * @TODO FIXME: returns source elements instead of next siblings
     */
    public function nextAll($selector = null)
    {
        return $this->newInstance(
            $this->getElementSiblings('nextSibling', $selector)
        );
    }

    /**
     * Get list Element Siblings
     *
     * @param string     $direction
     * @param null|mixed $selector
     * @param bool       $limitToOne
     * @return array<DOMNode>
     */
    protected function getElementSiblings($direction, $selector = null, $limitToOne = false)
    {
        $stack = [];
        foreach ($this->stack() as $node) {
            $test = $node;
            while (isset($test->{$direction}) && $test->{$direction}) {
                $test = $test->{$direction};
                if (!$test instanceof DOMElement) {
                    continue;
                }
                $stack[] = $test;
                if ($limitToOne) {
                    break;
                }
            }
        }
        if ($selector) {
            $stackOld = $this->elements;
            $this->elements = $stack;
            $stack = $this->filter($selector, true)->stack();
            $this->elements = $stackOld;
        }
        return $stack;
    }

    /**
     * NewInstance included $selector is siblings
     *
     * @param null|mixed $selector
     * @return phpQueryObject
     */
    public function siblings($selector = null)
    {
        $stack = [];
        $siblings = array_merge(
            $this->getElementSiblings('previousSibling', $selector),
            $this->getElementSiblings('nextSibling', $selector)
        );
        foreach ($siblings as $node) {
            if (!$this->elementsContainsNode($node, $stack)) {
                $stack[] = $node;
            }
        }
        return $this->newInstance($stack);
    }

    /**
     * NewInstance not included $selector
     *
     * @param null|mixed $selector
     * @return phpQueryObject
     */
    public function not($selector = null)
    {
        if (is_string($selector)) {
            $this->debug(['not', $selector]);
        }
        else {
            $this->debug('not');
        }

        $stack = [];
        if ($selector instanceof self || $selector instanceof DOMNode) {
            foreach ($this->stack() as $node) {
                if ($selector instanceof self) {
                    $matchFound = false;
                    foreach ($selector->stack() as $notNode) {
                        if ($notNode->isSameNode($node)) {
                            $matchFound = true;
                        }
                    }
                    if (!$matchFound) {
                        $stack[] = $node;
                    }
                }
                elseif ($selector instanceof DOMNode) {
                    if (!$selector->isSameNode($node)) {
                        $stack[] = $node;
                    }
                }
                else {
                    if (!$this->is($selector)) {
                        $stack[] = $node;
                    }
                }
            }
        }
        else {
            $orgStack = $this->stack();
            $matched = $this->filter($selector, true)->stack();
            //$matched = array();
            //// simulate OR in filter() instead of AND 5y
            //foreach($this->parseSelector($selector) as $s) {
            //    $matched = array_merge($matched,
            //        $this->filter(array($s))->stack()
            //    );
            //}
            foreach ($orgStack as $node) {
                if (!$this->elementsContainsNode($node, $matched)) {
                    $stack[] = $node;
                }
            }
        }
        return $this->newInstance($stack);
    }

    /**
     * NewInstance included $selector
     *
     * @param null|mixed $selector
     * @return phpQueryObject
     */
    public function add($selector = null)
    {
        if (!$selector) {
            return $this;
        }

        $this->elementsBackup = $this->elements;
        $found = phpQuery::pq($selector, $this->getDocumentID());
        $this->merge($found->elements);
        return $this->newInstance();
    }

    /**
     * Merge Nodes to list Nodes
     */
    protected function merge()
    {
        foreach (func_get_args() as $nodes) {
            foreach ($nodes as $newNode) {
                if (!$this->elementsContainsNode($newNode)) {
                    $this->elements[] = $newNode;
                }
            }
        }
    }

    /**
     * Check Node($nodeToCheck) is in StackNode
     *
     * @param DOMNode    $nodeToCheck
     * @param null|array $elementsStack null: check in $this->elements
     * @return bool
     * @TODO refactor to stackContainsNode
     */
    protected function elementsContainsNode($nodeToCheck, $elementsStack = null)
    {
        $loop = !is_null($elementsStack)
            ? $elementsStack
            : $this->elements;
        foreach ($loop as $node) {
            if ($node->isSameNode($nodeToCheck)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Parent
     *
     * @param null|mixed $selector
     * @return phpQueryObject
     */
    public function parent($selector = null)
    {
        $stack = [];
        foreach ($this->elements as $node) {
            if ($node->parentNode && !$this->elementsContainsNode($node->parentNode, $stack)) {
                $stack[] = $node->parentNode;
            }
        }

        $this->elementsBackup = $this->elements;
        $this->elements = $stack;

        if ($selector) {
            return $this->filter($selector, true);
        }
        return $this->newInstance();
    }

    /**
     * Parents
     *
     * @param null|mixed $selector
     * @return phpQueryObject
     */
    public function parents($selector = null)
    {
        $stack = [];
        if (empty($this->elements)) {
            $this->debug('parents() - stack empty');
        }

        foreach ($this->elements as $node) {
            $test = $node;
            while ($test->parentNode) {
                $test = $test->parentNode;
                if ($this->isRoot($test)) {
                    break;
                }
                if (!$this->elementsContainsNode($test, $stack)) {
                    $stack[] = $test;
                    continue;
                }
            }
        }

        $this->elementsBackup = $this->elements;
        $this->elements = $stack;

        if ($selector) {
            return $this->filter($selector, true);
        }
        return $this->newInstance();
    }

    /**
     * Get list Node on Stack by list $nodeTypes
     * Internal stack iterator.
     *
     * @link https://www.php.net/manual/en/dom.constants.php nodeType is One of the predefined
     * @param null|int|array $nodeTypes null: return all nodes
     */
    public function stack($nodeTypes = null)
    {
        if (!isset($nodeTypes)) {
            return $this->elements;
        }

        if (!is_array($nodeTypes)) {
            $nodeTypes = [$nodeTypes];
        }

        $return = [];
        foreach ($this->elements as $node) {
            if (in_array($node->nodeType, $nodeTypes)) {
                $return[] = $node;
            }
        }
        return $return;
    }

    /**
     * attrEvents
     *
     * @param string     $attr
     * @param mixed      $oldAttr
     * @param mixed      $oldValue
     * @param DOMElement $node
     * @return void
     * @TODO phpdoc; $oldAttr is result of hasAttribute, before any changes
     */
    protected function attrEvents($attr, $oldAttr, $oldValue, $node)
    {
        // skip events for XML documents
        if (!$this->isXHTML() && !$this->isHTML()) {
            return;
        }

        $event = null;
        // identify
        $isInputValue = ($node->tagName == 'input'
            && (
                in_array($node->getAttribute('type'), ['text', 'password', 'hidden'])
                || !$node->getAttribute('type')
            )
        );
        $isRadio = $node->tagName == 'input' && $node->getAttribute('type') == 'radio';
        $isCheckbox = $node->tagName == 'input' && $node->getAttribute('type') == 'checkbox';
        $isOption = $node->tagName == 'option';

        if ($isInputValue && $attr == 'value' && $oldValue != $node->getAttribute($attr)) {
            $event = new DOMEvent([
                'target' => $node,
                'type'   => 'change'
            ]);
        }
        elseif (
            ($isRadio || $isCheckbox) && $attr == 'checked'
            && (
                // check
                (!$oldAttr && $node->hasAttribute($attr))
                // un-check
                || (!$node->hasAttribute($attr) && $oldAttr)
            )
        ) {
            $event = new DOMEvent([
                'target' => $node,
                'type'   => 'change'
            ]);
        }
        elseif (
            $isOption && $node->parentNode && $attr == 'selected'
            && (
                // select
                (!$oldAttr && $node->hasAttribute($attr))
                // un-select
                || (!$node->hasAttribute($attr) && $oldAttr)
            )) {
            $event = new DOMEvent([
                'target' => $node->parentNode,
                'type'   => 'change'
            ]);
        }
        if ($event) {
            phpQueryEvents::trigger($this->getDocumentID(),
                $event->type, [$event], $node
            );
        }
    }

    /**
     * Get value of attribute
     *
     * @param null|string $attr
     * @param null|mixed  $value
     * @return null|array|string|phpQueryObject
     */
    public function attr($attr = null, $value = null)
    {
        foreach ($this->getListDOMElementFromStack() as $node) {
            if (!is_null($value)) {
                $loop = $attr == '*'
                    ? $this->getNodeAttrs($node)
                    : [$attr];
                foreach ($loop as $a) {
                    $oldValue = $node->getAttribute($a);
                    $oldAttr = $node->hasAttribute($a);
                    // TODO raises an error when charset other than UTF-8
                    // while document's charset is also not UTF-8
                    @$node->setAttribute($a, $value);
                    $this->attrEvents($a, $oldAttr, $oldValue, $node);
                }
            }
            elseif ($attr == '*') {
                // jQuery difference
                $return = [];
                foreach ($node->attributes as $n => $v) {
                    $return[$n] = $v->value;
                }
                return $return;
            }
            else {
                return $node->hasAttribute($attr)
                    ? $node->getAttribute($attr)
                    : null;
            }
        }

        return is_null($value) ? '' : $this;
    }

    /**
     * Get list Attribute of Node ($node)
     *
     * @param DOMNode $node
     * @return array
     */
    protected function getNodeAttrs($node)
    {
        $return = [];
        foreach ($node->attributes as $n => $o) {
            $return[] = $n;
        }
        return $return;
    }

    /**
     * Get value of attribute with PHP tag
     *
     * @param string $attr
     * @param string $code
     * @return array|string|phpQueryObject
     * @TODO check CDATA ???
     */
    public function attrPHP($attr, $code)
    {
        if (!is_null($code)) {
            $value = '<'.'?php '.$code.' ?'.'>';
            // TODO: temporary solution
            // http://code.google.com/p/phpquery/issues/detail?id=17
            //if (function_exists('mb_detect_encoding') && mb_detect_encoding($value) == 'ASCII') {
            //    $value = mb_convert_encoding($value, 'UTF-8', 'HTML-ENTITIES');
            //}
        }

        foreach ($this->getListDOMElementFromStack() as $node) {
            if (!is_null($code)) {
                //$attrNode = $this->DOM->createAttribute($attr);
                $node->setAttribute($attr, $value);
                //$attrNode->value = $value;
                //$node->appendChild($attrNode);
            }
            elseif ($attr == '*') {
                // jQuery diff
                $return = [];
                foreach ($node->attributes as $n => $v) {
                    $return[$n] = $v->value;
                }
                return $return;
            }
            else {
                return $node->getAttribute($attr);
            }
        }
        return $this;
    }

    /**
     * Remove Attribute from Node
     *
     * @param string $attr Attribute name
     * @return phpQueryObject
     */
    public function removeAttr($attr)
    {
        if (!empty($attr)) {
            foreach ($this->getListDOMElementFromStack() as $node) {
                $loop = $attr == '*'
                    ? $this->getNodeAttrs($node)
                    : [$attr];
                foreach ($loop as $a) {
                    $oldValue = $node->getAttribute($a);
                    $node->removeAttribute($a);
                    $this->attrEvents($a, $oldValue, null, $node);
                }
            }
        }
        return $this;
    }

    /**
     * Return form element value.
     *
     * @param null|mixed $val Value of Node
     * @return string|phpQueryObject Fields value.
     */
    public function val($val = null)
    {
        if (!isset($val)) {
            if ($this->eq(0)->is('select')) {
                $selected = $this->eq(0)->find('option[selected=selected]');
                if ($selected->is('[value]')) {
                    return $selected->attr('value');
                }
                else {
                    return $selected->text();
                }
            }
            elseif ($this->eq(0)->is('textarea')) {
                return $this->eq(0)->markup();
            }
            else {
                return $this->eq(0)->attr('value');
            }
        }
        else {
            $_val = null;
            foreach ($this->getListDOMElementFromStack() as $node) {
                $node = pq($node, $this->getDocumentID());
                if (is_array($val) && in_array($node->attr('type'), ['checkbox', 'radio'])) {
                    $isChecked = in_array($node->attr('value'), $val) || in_array($node->attr('name'), $val);
                    if ($isChecked) {
                        $node->attr('checked', 'checked');
                    }
                    else {
                        $node->removeAttr('checked');
                    }
                }
                elseif ($node->get(0)->tagName == 'select') {
                    if (!isset($_val)) {
                        $_val = [];
                        if (!is_array($val)) {
                            $_val = [(string)$val];
                        }
                        else {
                            foreach ($val as $v) {
                                $_val[] = $v;
                            }
                        }
                    }

                    $optionNodes = $node['option']->getListDOMElementFromStack();
                    foreach ($optionNodes as $option) {
                        $option = pq($option, $this->getDocumentID());

                        // XXX: workaround for string comparison, see issue #96
                        // http://code.google.com/p/phpquery/issues/detail?id=96
                        $selected = is_null($option->attr('value'))
                            ? in_array($option->markup(), $_val)
                            : in_array($option->attr('value'), $_val);

                        //$optionValue = $option->attr('value');
                        //$optionText = $option->text();
                        //$optionTextLenght = mb_strlen($optionText);
                        //foreach ($_val as $v) {
                        //    if ($optionValue == $v) {
                        //        $selected = true;
                        //    }
                        //    elseif ($optionText == $v && $optionTextLenght == mb_strlen($v)) {
                        //        $selected = true;
                        //    }
                        //}

                        if ($selected) {
                            $option->attr('selected', 'selected');
                        }
                        else {
                            $option->removeAttr('selected');
                        }
                    }
                }
                elseif ($node->get(0)->tagName == 'textarea') {
                    $node->markup($val);
                }
                else {
                    $node->attr('value', $val);
                }
            }
        }
        return $this;
    }

    /**
     * Merge Node: $this->elements and $this->previous->elements
     *
     * @return phpQueryObject
     */
    public function andSelf()
    {
        if (!empty($this->previous)) {
            $this->elements = array_merge($this->elements, $this->previous->elements);
        }
        return $this;
    }

    /**
     * Add CSS ClassName to the Node's Attribute
     *
     * @param string $className CSS class name
     * @return phpQueryObject
     */
    public function addClass($className)
    {
        if (!empty($className)) {
            foreach ($this->getListDOMElementFromStack() as $node) {
                if (!$this->is(".$className", $node)) {
                    $node->setAttribute('class', trim($node->getAttribute('class').' '.$className));
                }
            }
        }
        return $this;
    }

    /**
     * Add CSS ClassName by PHP to the Node's Attribute
     *
     * @param string $className CSS class name
     * @return phpQueryObject
     */
    public function addClassPHP($className)
    {
        if (!empty($className)) {
            foreach ($this->getListDOMElementFromStack() as $node) {
                $classes = $node->getAttribute('class');
                $newValue = $classes
                    ? $classes.' <'.'?php '.$className.' ?'.'>'
                    : '<'.'?php '.$className.' ?'.'>';
                $node->setAttribute('class', $newValue);
            }
        }
        return $this;
    }

    /**
     * Check CSS ClassName exist in the Node's Attribute
     *
     * @param string $className CSS class name
     * @return bool
     */
    public function hasClass($className)
    {
        if (!empty($className)) {
            foreach ($this->getListDOMElementFromStack() as $node) {
                if ($this->is(".$className", $node)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Remove CSS Class in the Node's Attribute
     *
     * @param string $className CSS class name
     * @return phpQueryObject
     */
    public function removeClass($className)
    {
        if (!empty($className)) {
            foreach ($this->getListDOMElementFromStack() as $node) {
                $classes = explode(' ', $node->getAttribute('class'));
                if (in_array($className, $classes)) {
                    $classes = array_diff($classes, [$className]);
                    if ($classes) {
                        $node->setAttribute('class', implode(' ', $classes));
                    }
                    else {
                        $node->removeAttribute('class');
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Toggle Class
     *
     * @param string $className CSS class name
     * @return phpQueryObject
     */
    public function toggleClass($className)
    {
        if (!empty($className)) {
            foreach ($this->getListDOMElementFromStack() as $node) {
                if ($this->is($node, '.'.$className)) {
                    $this->removeClass($className);
                }
                else {
                    $this->addClass($className);
                }
            }
        }
        return $this;
    }

    /**
     * Get list Node<DOMElement> from Stack
     *
     * @return array<DOMElement>
     */
    protected function getListDOMElementFromStack()
    {
        // XML_ELEMENT_NODE: Node is a DOMElement
        return $this->stack(XML_ELEMENT_NODE);
    }

    /**
     * Proper name without underscore (just ->empty()) also works.
     * Removes all child nodes from the set of matched elements.
     * Example:
     * pq("p")._empty()
     * HTML:
     * <p>Hello, <span>Person</span> <a href="#">and person</a></p>
     * Result:
     * [ <p></p> ]
     *
     * @return phpQueryObject
     */
    public function _empty()
    {
        foreach ($this->stack(1) as $node) {
            // thx to 'dave at dgx dot cz'
            $node->nodeValue = '';
        }
        return $this;
    }

    /**
     * Run callbacks on actual object.
     *
     * @param array|string $callback Expects $node as first param, $index as second
     * @param mixed        $param1   Will ba passed as third and further args to callback.
     * @param mixed        $param2   Will ba passed as fourth and further args to callback, and so on...
     * @param mixed        $param3   Will ba passed as fourth and further args to callback, and so on...
     * @return phpQueryObject
     */
    public function each($callback, $param1 = null, $param2 = null, $param3 = null)
    {
        $paramStructure = null;
        if (func_num_args() > 1) {
            $paramStructure = func_get_args();
            $paramStructure = array_slice($paramStructure, 1);
        }
        foreach ($this->elements as $v) {
            phpQuery::callbackRun($callback, [$v], $paramStructure);
        }
        return $this;
    }

    /**
     * Run callback on actual object.
     *
     * @return phpQueryObject
     */
    public function callback($callback, $param1 = null, $param2 = null, $param3 = null)
    {
        $params = func_get_args();
        $params[0] = $this;
        phpQuery::callbackRun($callback, $params);
        return $this;
    }

    /**
     * Implement Callback map: Create new Instance
     *
     * @param string|callable $callback
     * @param null|mixed      $param1
     * @param null|mixed      $param2
     * @param null|mixed      $param3
     * @return phpQueryObject
     * @TODO add $scope and $args as in each() ???
     */
    public function map($callback, $param1 = null, $param2 = null, $param3 = null)
    {
        //$stack = [];
        //foreach ($this->newInstance() as $node) {
        //    $result = call_user_func($callback, $node);
        //    if ($result) {
        //        $stack[] = $result;
        //    }
        //}

        $params = func_get_args();
        array_unshift($params, $this->elements);
        return $this->newInstance(
            call_user_func_array(['phpQuery', 'map'], $params)
        //phpQuery::map($this->elements, $callback);
        );
    }

    /**
     * Add Data to Node
     *
     * @param mixed      $key
     * @param null|mixed $value
     * @return phpQueryObject
     */
    public function data($key, $value = null)
    {
        if (!isset($value)) {
            // TODO: implement specific jQuery behavior od returning parent values
            // is child which we look up doesn't exist
            phpQuery::data($this->get(0), $key, $value, $this->getDocumentID());
        }
        else {
            foreach ($this as $node) {
                phpQuery::data($node, $key, $value, $this->getDocumentID());
            }
        }
        return $this;
    }

    /**
     * Remove Data of Node by $name
     *
     * @param mixed $key
     * @return phpQueryObject
     */
    public function removeData($key)
    {
        foreach ($this as $node) {
            phpQuery::removeData($node, $key, $this->getDocumentID());
        }
        return $this;
    }

    // INTERFACE IMPLEMENTATIONS

    // ITERATOR INTERFACE IMPLEMENT
    /**
     * Rewind the Iterator to the first element
     *
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->debug('iterating foreach');

        //phpQuery::selectDocument($this->getDocumentID());
        $this->elementsBackup = $this->elements;
        $this->elementsInterator = $this->elements;
        $this->valid = isset($this->elements[0]);
        //$this->elements = $this->valid ? [$this->elements[0]] : [];
        $this->current = 0;
    }

    /**
     * Return the current element
     *
     * @link https://php.net/manual/en/iterator.current.php
     * @return DOMNode
     */
    public function current()
    {
        return $this->elementsInterator[$this->current];
    }

    /**
     * Return the key of the current element
     *
     * @link https://php.net/manual/en/iterator.key.php
     * @return null|int int on success, or null on failure.
     */
    public function key()
    {
        return $this->current;
    }

    /**
     * Checks if current position is valid
     *
     * @link https://php.net/manual/en/iterator.valid.php
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return $this->valid;
    }
    // ITERATOR INTERFACE IMPLEMENT END

    // ARRAYACCESS INTERFACE IMPLEMENT
    /**
     * Whether an offset exists
     *
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset The offset to retrieve.
     * @return bool true on success or false on failure.
     */
    public function offsetExists($offset)
    {
        return $this->find($offset)->size() > 0;
    }

    /**
     * Offset to retrieve
     *
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.
     * @return phpQueryObject
     */
    public function offsetGet($offset)
    {
        return $this->find($offset);
    }

    /**
     * Offset to set
     *
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value  The value to set.
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        //$this->find($offset)->replaceWith($value);
        $this->find($offset)->html($value);
    }

    /**
     * Offset to unset
     *
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset.
     * @return void
     * @throws Exception
     */
    public function offsetUnset($offset)
    {
        // empty
        throw new Exception("Can't do unset, use array interface only for calling queries and replacing HTML.");
    }
    // ARRAYACCESS INTERFACE IMPLEMENT END

    /**
     * Returns node's XPath.
     *
     * @param null|DOMNode $oneNode
     * @param null|string  $namespace
     * @return string|array isEmpty $oneNode -> return string, where else returns array of strings
     * @TODO use native getNodePath is available
     */
    protected function getNodeXpath($oneNode = null, $namespace = null)
    {
        $return = [];

        //if ($namespace) {
        //    $namespace .= ':';
        //}

        $loop = !empty($oneNode)
            ? [$oneNode]
            : $this->elements;

        foreach ($loop as $node) {
            if ($node instanceof DOMDocument) {
                $return[] = '';
                continue;
            }

            $xpath = [];
            while (!($node instanceof DOMDocument)) {
                $i = 1;
                $sibling = $node;
                while ($sibling->previousSibling) {
                    $sibling = $sibling->previousSibling;
                    if ($sibling instanceof DOMElement && $node instanceof DOMElement && $sibling->tagName == $node->tagName) {
                        $i++;
                    }
                }

                $xpath[] = $this->isXML()
                    ? "*[local-name()='{$node->tagName}'][{$i}]"
                    : "{$node->tagName}[{$i}]";

                $node = $node->parentNode;
            }

            $xpath = join('/', array_reverse($xpath));
            $return[] = '/'.$xpath;
        }

        return $oneNode
            ? $return[0]
            : $return;
    }

    // HELPERS

    /**
     * Whois
     *
     * @param null|DOMElement|DOMNode $oneNode
     * @return array|string isEmpty $oneNode -> return string, where else returns array of strings
     */
    public function whois($oneNode = null)
    {
        $return = [];

        $loop = $oneNode ? [$oneNode] : $this->elements;
        foreach ($loop as $node) {
            if (isset($node->tagName)) {
                $tag = in_array($node->tagName, ['php', 'js'])
                    ? strtoupper($node->tagName)
                    : $node->tagName;

                $return[] = $tag
                    .($node->getAttribute('id')
                        ? '#'.$node->getAttribute('id') : '')
                    .($node->getAttribute('class')
                        ? '.'.join('.', preg_split(' ', $node->getAttribute('class'))) : '')
                    .($node->getAttribute('name')
                        ? '[name="'.$node->getAttribute('name').'"]' : '')
                    .($node->getAttribute('value') && strpos($node->getAttribute('value'), '<'.'?php') === false
                        ? '[value="'.substr(str_replace("\n", '', $node->getAttribute('value')), 0, 15).'"]' : '')
                    .($node->getAttribute('value') && strpos($node->getAttribute('value'), '<'.'?php') !== false
                        ? '[value=PHP]' : '')
                    .($node->getAttribute('selected')
                        ? '[selected]' : '')
                    .($node->getAttribute('checked')
                        ? '[checked]' : '');
            }
            elseif ($node instanceof DOMText) {
                if (trim($node->textContent)) {
                    $return[] = 'Text:'.substr(str_replace("\n", ' ', $node->textContent), 0, 15);
                }
            }
        }

        return !empty($oneNode) && isset($return[0])
            ? $return[0]
            : $return;
    }

    /**
     * Dump htmlOuter and preserve chain. Usefully for debugging.
     *
     * @return void
     */
    public function dump()
    {
        print 'DUMP #'.(phpQuery::$dumpCount++).' ';

        $debug = $this->getDebug();
        $this->setDebug(false);
        //print __FILE__.':'.__LINE__."\n";
        var_dump($this->htmlOuter());
        $this->setDebug($debug);
    }

    /**
     * Dump Whois
     *
     * @return void
     */
    public function dumpWhois()
    {
        print 'DUMP #'.(phpQuery::$dumpCount++).' ';

        $debug = $this->getDebug();
        $this->setDebug(false);
        //print __FILE__.':'.__LINE__."\n";
        var_dump('whois', $this->whois());
        $this->setDebug($debug);
    }

    /**
     * Dump Size (Count Total Node of Document)
     *
     * @return void
     */
    public function dumpSize()
    {
        print 'DUMP #'.(phpQuery::$dumpCount++).' ';

        $debug = $this->getDebug();
        $this->setDebug(false);
        //print __FILE__.':'.__LINE__."\n";
        var_dump('size', $this->size());
        $this->setDebug($debug);
    }

    /**
     * Dump Tree
     *
     * @param bool $html  is dump HTML line breaks before all newlines in a string
     * @param bool $title is dump title text
     * @return void
     */
    public function dumpTree($html = true, $title = true)
    {
        $output = $title ? 'DUMP #'.(phpQuery::$dumpCount++)." \n" : '';

        $debug = $this->getDebug();
        $this->setDebug(false);
        foreach ($this->stack() as $node) {
            $output .= $this->__dumpTree($node);
        }
        $this->setDebug($debug);

        print $html
            ? nl2br(str_replace(' ', '&nbsp;', $output))
            : $output;
    }

    /**
     * Handler gen Node to string output of Dump
     *
     * @param DOMNode $node
     * @param int     $intend
     * @return string
     */
    protected function __dumpTree($node, $intend = 0)
    {
        $return = '';

        $whois = $this->whois($node);
        if ($whois) {
            $return .= str_repeat(' - ', $intend).$whois."\n";
        }
        if (isset($node->childNodes)) {
            foreach ($node->childNodes as $chNode) {
                $return .= $this->__dumpTree($chNode, $intend + 1);
            }
        }

        return $return;
    }

    /**
     * Dump htmlOuter and stop script execution. Usefully for debugging.
     */
    protected function dd()
    {
        print __FILE__.':'.__LINE__;
        echo "<pre>";
        var_dump($this->htmlOuter());
        echo "</pre>";
        die();
    }

    /**
     * Get Debug status
     *
     * @return bool|int
     */
    protected function getDebug()
    {
        return phpQuery::$debug;
    }

    /**
     * Set Debug status
     *
     * @param int|bool $debug
     * @return void
     */
    protected function setDebug($debug)
    {
        phpQuery::$debug = $debug;
    }

    /**
     * Log Debug
     *
     * @param mixed $data
     */
    protected function debug($data)
    {
        if ($this->getDebug()) {
            print('<pre>');
            print_r($data);

            // file debug
            //file_put_contents(dirname(__FILE__).'/phpQuery.log', print_r($data, true)."\n", FILE_APPEND);

            // quite handy debug trace
            //if (is_array($data)) {
            //    print_r(array_slice(debug_backtrace(), 3));
            //}

            print("</pre>\n");
        }
    }
}

/**
 * Static namespace for phpQuery functions.
 *
 * @author  Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 */
abstract class phpQuery
{
    /**
     * XXX: Workaround for mbstring problems
     *
     * @var bool
     */
    public static $mbstringSupport = true;
    /**
     * @var bool|int
     */
    public static $debug = false;
    /**
     * @var array
     */
    public static $documents = [];
    /**
     * @var string
     */
    public static $defaultDocumentID = null;
    /**
     * Applies only to HTML.
     *
     * @var string
     */
    public static $defaultDoctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
    //public static $defaultDoctype = 'html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"';
    /**
     * @var string
     */
    public static $defaultCharset = 'UTF-8';
    /**
     * Static namespace for plugins.
     *
     * @var object|array
     */
    public static $plugins = [];
    /**
     * List of loaded plugins.
     *
     * @var array
     */
    public static $pluginsLoaded = [];
    public static $pluginsMethods = [];
    public static $pluginsStaticMethods = [];
    public static $extendMethods = [];
    /**
     * @TODO implement
     */
    public static $extendStaticMethods = [];
    /**
     * Hosts allowed for AJAX connections.
     * Dot '.' means $_SERVER['HTTP_HOST'] (if any).
     *
     * @var array
     */
    public static $ajaxAllowedHosts = ['.'];
    /**
     * AJAX settings.
     *
     * @var array
     * XXX should it be static or not ?
     */
    public static $ajaxSettings = [
        'url'         => '', //TODO
        'global'      => true,
        'type'        => "GET",
        'timeout'     => null,
        'contentType' => "application/x-www-form-urlencoded",
        'processData' => true,
        //'async'     => true,
        'data'        => null,
        'username'    => null,
        'password'    => null,
        'accepts'     => [
            'xml'      => "application/xml, text/xml",
            'html'     => "text/html",
            'script'   => "text/javascript, application/javascript",
            'json'     => "application/json, text/javascript",
            'text'     => "text/plain",
            '_default' => "*/*"
        ]
    ];
    public static $lastModified = null;
    public static $active = 0;
    public static $dumpCount = 0;

    /**
     * Multipurpose function.
     * Use pq() as shortcut.
     * In below examples, $pq is any result of pq(); function.
     * 1. Import markup into existing document (without any attaching):
     * - Import into selected document:
     *      pq('<div/>') // DOES NOT accept text nodes at beginning of input string !
     * - Import into document with ID from $pq->getDocumentID():
     *      pq('<div/>', $pq->getDocumentID())
     * - Import into same document as DOMNode belongs to:
     *      pq('<div/>', DOMNode)
     * - Import into document from phpQuery object:
     *      pq('<div/>', $pq)
     * 2. Run query:
     * - Run query on last selected document:
     *      pq('div.myClass')
     * - Run query on document with ID from $pq->getDocumentID():
     *      pq('div.myClass', $pq->getDocumentID())
     * - Run query on same document as DOMNode belongs to and use node(s)as root for query:
     *      pq('div.myClass', DOMNode)
     * - Run query on document from phpQuery object and use object's stack as root node(s) for query:
     *      pq('div.myClass', $pq)
     *
     * @param string|array|DOMNode|DOMNodeList   $arg1    HTML markup, CSS Selector, DOMNode or array of DOMNodes
     * @param null|string|phpQueryObject|DOMNode $context DOM ID from $pq->getDocumentID(), phpQuery object (determines also query root) or DOMNode (determines also query root)
     * @return phpQueryObject                             phpQuery object or false in case of error.
     * @throws Exception
     */
    public static function pq($arg1, $context = null)
    {
        if ($arg1 instanceof DOMNode && !isset($context)) {
            foreach (phpQuery::$documents as $documentWrapper) {
                $compare = ($arg1 instanceof DOMDocument) ? $arg1 : $arg1->ownerDocument;
                if ($documentWrapper->document->isSameNode($compare)) {
                    $context = $documentWrapper->id;
                }
            }
        }

        if (!$context) {
            $domId = phpQuery::$defaultDocumentID;
            if (!$domId) {
                throw new Exception("Can't use last created DOM, because there isn't any. Use phpQuery::newDocument() first.");
            }
        }
        //else if (is_object($context) && ($context instanceof phpQuery || is_subclass_of($context, 'phpQueryObject'))) {
        elseif ($context instanceof phpQueryObject) {
            $domId = $context->getDocumentID();
        }
        elseif ($context instanceof DOMDocument) {
            $domId = phpQuery::getDocumentID($context);
            if (!$domId) {
                $domId = phpQuery::newDocument($context)->getDocumentID();
            }
        }
        elseif ($context instanceof DOMNode) {
            $domId = phpQuery::getDocumentID($context);
            if (!$domId) {
                throw new Exception('Orphaned DOMNode');
            }
        }
        else {
            $domId = $context;
        }

        if ($arg1 instanceof phpQueryObject) {
            /**
             * Return $arg1 or import $arg1 stack if document differs:
             * pq(pq('<div/>'))
             */
            if ($arg1->getDocumentID() == $domId) {
                return $arg1;
            }

            $class = get_class($arg1);

            // support inheritance by passing old object to overloaded constructor
            $phpQuery = $class != 'phpQuery'
                ? new $class($arg1, $domId)
                : new phpQueryObject($domId);

            $phpQuery->elements = [];
            foreach ($arg1->elements as $node) {
                $phpQuery->elements[] = $phpQuery->document->importNode($node, true);
            }
            return $phpQuery;
        }
        elseif ($arg1 instanceof DOMNode || (is_array($arg1) && isset($arg1[0]) && $arg1[0] instanceof DOMNode)) {
            /*
             * Wrap DOM nodes with phpQuery object, import into document when needed:
             * pq(array($domNode1, $domNode2))
             */
            $phpQuery = new phpQueryObject($domId);
            if (!($arg1 instanceof DOMNodeList) && !is_array($arg1)) {
                $arg1 = [$arg1];
            }
            $phpQuery->elements = [];
            foreach ($arg1 as $node) {
                $sameDocument = ($node->ownerDocument instanceof DOMDocument && !$node->ownerDocument->isSameNode($phpQuery->document));
                $phpQuery->elements[] = $sameDocument
                    ? $phpQuery->document->importNode($node, true)
                    : $node;
            }
            return $phpQuery;
        }
        elseif (phpQuery::isMarkup($arg1)) {
            /**
             * Import HTML:
             * pq('<div/>')
             */
            $phpQuery = new phpQueryObject($domId);
            return $phpQuery->newInstance(
                $phpQuery->documentWrapper->import($arg1)
            );
        }
        else {
            /**
             * Run CSS query:
             * pq('div.myClass')
             */
            $phpQuery = new phpQueryObject($domId);
            if (!empty($context)) {
                if ($context instanceof phpQueryObject) {
                    $phpQuery->elements = $context->elements;
                }
                elseif ($context instanceof DOMNodeList) {
                    $phpQuery->elements = [];
                    foreach ($context as $node) {
                        $phpQuery->elements[] = $node;
                    }
                }
                elseif ($context instanceof DOMNode) {
                    $phpQuery->elements = [$context];
                }
            }

            return $phpQuery->find($arg1);
        }
    }

    /**
     * Sets default document to $id. Document has to be loaded prior
     * to using this method.
     * $id can be retried via getDocumentID() or getDocumentIDRef().
     *
     * @param string $id
     * @return void
     */
    public static function selectDocument($id)
    {
        $id = phpQuery::getDocumentID($id);
        phpQuery::debug("Selecting document '$id' as default one");
        phpQuery::$defaultDocumentID = phpQuery::getDocumentID($id);
    }

    /**
     * Returns document with id $id or last used as phpQueryObject.
     * $id can be retried via getDocumentID() or getDocumentIDRef().
     * Chainable.
     *
     * @param null|string $id
     * @return phpQueryObject
     * @see phpQuery::selectDocument()
     */
    public static function getDocument($id = null)
    {
        if (!empty($id)) {
            phpQuery::selectDocument($id);
        }
        else {
            $id = phpQuery::$defaultDocumentID;
        }
        return new phpQueryObject($id);
    }

    /**
     * Creates new document from markup.
     *
     * @param null|string|DOMDocument $markup
     * @param null|string             $contentType
     * @return phpQueryObject
     */
    public static function newDocument($markup = null, $contentType = null)
    {
        if (empty($markup)) {
            $markup = '';
        }
        $documentID = phpQuery::createDocumentWrapper($markup, $contentType);

        return new phpQueryObject($documentID);
    }

    /**
     * Create new Document HTML from markup.
     *
     * @param null|string|DOMDocument $markup
     * @param string                  $charset
     * @return phpQueryObject
     */
    public static function newDocumentHTML($markup = null, $charset = '')
    {
        $charset = !empty($charset) ? $charset : strtolower(phpQuery::$defaultCharset);
        $contentType = "text/html;charset={$charset}";
        return phpQuery::newDocument($markup, $contentType);
    }

    /**
     * Create new Document XML from markup.
     *
     * @param null|string|DOMDocument $markup
     * @param string                  $charset
     * @return phpQueryObject
     */
    public static function newDocumentXML($markup = null, $charset = '')
    {
        $charset = !empty($charset) ? $charset : strtolower(phpQuery::$defaultCharset);
        $contentType = "text/xml;charset={$charset}";
        return phpQuery::newDocument($markup, $contentType);
    }

    /**
     * Create new Document XHTML from markup.
     *
     * @param null|string|DOMDocument $markup
     * @param string                  $charset
     * @return phpQueryObject
     */
    public static function newDocumentXHTML($markup = null, $charset = '')
    {
        $charset = !empty($charset) ? $charset : strtolower(phpQuery::$defaultCharset);
        $contentType = "application/xhtml+xml;charset={$charset}";
        return phpQuery::newDocument($markup, $contentType);
    }

    /**
     * Create new Document PHP from markup.
     *
     * @param null|string|DOMDocument $markup
     * @param string                  $contentType
     * @return phpQueryObject
     */
    public static function newDocumentPHP($markup = null, $contentType = "text/html")
    {
        $markup = !empty($markup) ? $markup : '';
        // TODO: pass charset to phpToMarkup if possible (use DOMDocumentWrapper function)
        $markup = phpQuery::phpToMarkup($markup, strtolower(phpQuery::$defaultCharset));
        return phpQuery::newDocument($markup, $contentType);
    }

    /**
     * Convert PHP code to Document Markup.
     *
     * @param string $php PHP code content
     * @param string $charset
     * @return string
     */
    public static function phpToMarkup($php, $charset = '')
    {
        $charset = !empty($charset) ? $charset : strtolower(phpQuery::$defaultCharset);

        $regexes = [
            '@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(\')([^\']*)<'.'?php?(.*?)(?:\\?>)([^\']*)\'@s',
            '@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(")([^"]*)<'.'?php?(.*?)(?:\\?>)([^"]*)"@s',
        ];
        foreach ($regexes as $regex) {
            while (preg_match($regex, $php)) {
                $php = preg_replace_callback(
                    $regex,
                    create_function(
                        '$matched, $charset = "'.$charset.'"',
                        'return $matched[1].$matched[2]
                            .htmlspecialchars("<?"."php".$matched[4]."?".">", ENT_QUOTES|ENT_NOQUOTES, $charset)
                            .$matched[5].$matched[2];'
                    ),
                    $php
                );
            }
        }
        $regex = '@(^|>[^<]*)+?(<\?php(.*?)(\?>))@s';
        $php = preg_replace($regex, '\\1<php><!-- \\3 --></php>', $php);
        return $php;
    }

    /**
     * Convert Document Markup containing PHP code generated by phpQuery::php()
     * into valid (executable) PHP code syntax.
     *
     * @param string|phpQueryObject $content
     * @return string PHP code.
     */
    public static function markupToPHP($content)
    {
        if ($content instanceof phpQueryObject) {
            $content = $content->markupOuter();
        }

        /* <php>...</php> to <?php...? > */
        $regex = '@<php>\s*<!--(.*?)-->\s*</php>@s';
        $content = preg_replace_callback(
            $regex,
            create_function(
                '$matched',
                'return "<"."?php ".htmlspecialchars_decode($matched[1])." ?".">";'
            ),
            $content
        );

        /* <node attr='< ?php ? >'> extra space added to save highlighters */
        $regexes = [
            '@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(\')([^\']*)(?:&lt;|%3C)\\?(?:php)?(.*?)(?:\\?(?:&gt;|%3E))([^\']*)\'@s',
            '@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(")([^"]*)(?:&lt;|%3C)\\?(?:php)?(.*?)(?:\\?(?:&gt;|%3E))([^"]*)"@s',
        ];
        foreach ($regexes as $regex) {
            while (preg_match($regex, $content)) {
                $content = preg_replace_callback(
                    $regex,
                    create_function(
                        '$matched',
                        'return $matched[1].$matched[2].$matched[3]
                        ."<?php "
                        .str_replace(
                            ["%20", "%3E", "%09", "&#10;", "&#9;", "%7B", "%24", "%7D", "%22", "%5B", "%5D"],
                            [" ", ">", "    ", "\n", "    ", "{", "$", "}", \'"\', "[", "]"],
                            htmlspecialchars_decode($matched[4])
                        )
                        ." ?>"
                        .$matched[5].$matched[2];'
                    ),
                    $content
                );
            }
        }
        return $content;
    }

    /**
     * Create new Document from file $file.
     *
     * @param string $file URLs allowed. See File wrapper page at php.net for more supported sources.
     * @param string $contentType
     * @return phpQueryObject
     */
    public static function newDocumentFile($file, $contentType = '')
    {
        $content = file_get_contents($file);
        $content = !empty($content) ? $content : '';
        $documentID = phpQuery::createDocumentWrapper($content, $contentType);
        return new phpQueryObject($documentID);
    }

    /**
     * Creates new Document HTML from file $file.
     *
     * @param string $file
     * @param string $charset
     * @return phpQueryObject
     */
    public static function newDocumentFileHTML($file, $charset = '')
    {
        $charset = !empty($charset) ? $charset : strtolower(phpQuery::$defaultCharset);
        $contentType = "text/html;charset={$charset}";
        return phpQuery::newDocumentFile($file, $contentType);
    }

    /**
     * Create new Document XML from file $file.
     *
     * @param string $file
     * @param string $charset
     * @return phpQueryObject
     */
    public static function newDocumentFileXML($file, $charset = '')
    {
        $charset = !empty($charset) ? $charset : strtolower(phpQuery::$defaultCharset);
        $contentType = "text/xml;charset={$charset}";
        return phpQuery::newDocumentFile($file, $contentType);
    }

    /**
     * Create new Document XHTML from file $file.
     *
     * @param string $file
     * @param string $charset
     * @return phpQueryObject
     */
    public static function newDocumentFileXHTML($file, $charset = '')
    {
        $charset = !empty($charset) ? $charset : strtolower(phpQuery::$defaultCharset);
        $contentType = "application/xhtml+xml;charset={$charset}";
        return phpQuery::newDocumentFile($file, $contentType);
    }

    /**
     * Create new Document PHP from file $file.
     *
     * @param string $file
     * @param string $contentType
     * @return phpQueryObject
     */
    public static function newDocumentFilePHP($file, $contentType = '')
    {
        $content = file_get_contents($file);
        $content = !empty($content) ? $content : '';
        return phpQuery::newDocumentPHP($content, $contentType);
    }

    /**
     * createDocumentWrapper
     *
     * @param mixed       $html
     * @param null|string $contentType
     * @param null|string $documentID
     * @return string New DOM ID
     * @throws Exception
     * @TODO support PHP tags in input
     * @TODO support passing DOMDocument object from phpQuery::loadDocument
     */
    protected static function createDocumentWrapper($html, $contentType = null, $documentID = null)
    {
        if (function_exists('domxml_open_mem')) {
            throw new Exception("Old PHP4 DOM XML extension detected. phpQuery won't work until this extension is enabled.");
        }

        //$id = !empty($documentID) ? $documentID : md5(microtime());

        $wrapper = null;
        if ($html instanceof DOMDocument) {
            if (phpQuery::getDocumentID($html)) {
                // document already exists in phpQuery::$documents, make a copy
                $wrapper = clone $html;
            }
            else {
                // new document, add it to phpQuery::$documents
                $wrapper = new DOMDocumentWrapper($html, $contentType, $documentID);
            }
        }
        else {
            $wrapper = new DOMDocumentWrapper($html, $contentType, $documentID);
        }

        //$wrapper->id = $id;

        // bind document
        phpQuery::$documents[$wrapper->id] = $wrapper;

        // remember last loaded document
        phpQuery::selectDocument($wrapper->id);
        return $wrapper->id;
    }

    /**
     * Extend class namespace.
     *
     * @param string|array $target
     * @param string|array $source
     * @return bool
     * @throws Exception
     */
    public static function extend($target, $source)
    {
        switch ($target) {
            case 'phpQueryObject':
                $targetRef = &phpQuery::$extendMethods;
                $targetRef2 = &phpQuery::$pluginsMethods;
                break;
            case 'phpQuery':
                $targetRef = &phpQuery::$extendStaticMethods;
                $targetRef2 = &phpQuery::$pluginsStaticMethods;
                break;
            default:
                throw new Exception("Unsupported {$target} type");
        }

        if (is_string($source)) {
            $source = [$source => $source];
        }

        foreach ($source as $method => $callback) {
            if (isset($targetRef[$method])) {
                //throw new Exception();
                phpQuery::debug("Duplicate method '{$method}', can't extend '{$target}'");
                continue;
            }
            if (isset($targetRef2[$method])) {
                //throw new Exception();
                phpQuery::debug("Duplicate method '{$method}' from plugin '{$targetRef2[$method]}', can't extend '{$target}'");
                continue;
            }
            $targetRef[$method] = $callback;
        }
        return true;
    }

    /**
     * Extend phpQuery with $class from $file.
     *
     * @param string      $class Extending class name. Real class name can be prepended phpQuery_.
     * @param null|string $file  Filename to include. Defaults to "{$class}.php".
     * @return bool
     * @throws Exception
     */
    public static function plugin($class, $file = null)
    {
        // TODO: $class checked against phpQuery_$class
        //if (strpos($class, 'phpQuery') === 0) {
        //    $class = substr($class, 8);
        //}

        if (in_array($class, phpQuery::$pluginsLoaded)) {
            return true;
        }

        if (empty($file)) {
            $file = $class.'.php';
        }

        $realStaticClass = 'phpQueryPlugin_'.$class;
        $realObjectClass = 'phpQueryObjectPlugin_'.$class;

        $staticClassExists = class_exists($realStaticClass);
        $objectClassExists = class_exists($realObjectClass);
        if (!$objectClassExists || !$staticClassExists) {
            if (file_exists($file)) {
                require_once($file);
            }
            else {
                throw new Exception("Plugin {$class} does not exist");
                //return false;
            }
        }
        phpQuery::$pluginsLoaded[] = $class;

        // static methods
        if ($staticClassExists) {
            $vars = get_class_vars($realStaticClass);
            $loop = !empty($vars['phpQueryMethods'])
                ? $vars['phpQueryMethods']
                : get_class_methods($realStaticClass);

            $initializeMethod = '__initialize';
            foreach ($loop as $method) {
                if (
                    $method == $initializeMethod
                    || !is_callable([$realStaticClass, $method])
                ) {
                    continue;
                }

                if (isset(phpQuery::$pluginsStaticMethods[$method])) {
                    throw new Exception("Duplicate method '{$method}' from plugin '{$class}' conflicts with same method from plugin '".phpQuery::$pluginsStaticMethods[$method]."'");
                    //return false;
                }

                phpQuery::$pluginsStaticMethods[$method] = $class;
            }

            if (method_exists($realStaticClass, $initializeMethod)) {
                call_user_func_array([$realStaticClass, $initializeMethod], []);
            }
        }

        // object methods
        if ($objectClassExists) {
            $vars = get_class_vars($realObjectClass);
            $loop = !empty($vars['phpQueryMethods'])
                ? $vars['phpQueryMethods']
                : get_class_methods($realObjectClass);

            foreach ($loop as $method) {
                if (!is_callable([$realObjectClass, $method])) {
                    continue;
                }

                if (isset(phpQuery::$pluginsMethods[$method])) {
                    throw new Exception("Duplicate method '{$method}' from plugin '{$class}' conflicts with same method from plugin '".phpQuery::$pluginsMethods[$method]."'");
                    //continue;
                }

                phpQuery::$pluginsMethods[$method] = $class;
            }
        }

        return true;
    }

    /**
     * Unload all or specified document from memory.
     *
     * @param null|mixed $id
     * @return void
     * @see phpQuery::getDocumentID() for supported types.
     */
    public static function unloadDocuments($id = null)
    {
        if (isset($id)) {
            if ($id = phpQuery::getDocumentID($id)) {
                unset(phpQuery::$documents[$id]);
            }
        }
        else {
            foreach (phpQuery::$documents as $k => $v) {
                unset(phpQuery::$documents[$k]);
            }
        }
    }

    /**
     * Parses phpQuery object or HTML result against PHP tags and makes them active.
     *
     * @param string|phpQueryObject $content
     * @return string
     */
    public static function unsafePHPTags($content)
    {
        return phpQuery::markupToPHP($content);
    }

    /**
     * Convert DOMNodeList to Array
     *
     * @param DOMNodeList $DOMNodeList
     * @return array
     */
    public static function DOMNodeListToArray($DOMNodeList)
    {
        $array = [];
        if (!$DOMNodeList) {
            return $array;
        }
        foreach ($DOMNodeList as $node) {
            $array[] = $node;
        }
        return $array;
    }

    /**
     * Checks if $input is HTML string, which has to start with '<'.
     *
     * @param string $input
     * @return Bool
     * @TODO still used ?
     */
    public static function isMarkup($input)
    {
        return !is_array($input) && substr(trim($input), 0, 1) == '<';
    }

    /**
     * Make an AJAX request.
     *
     * @link http://docs.jquery.com/Ajax/jQuery.ajax
     * @param array      $options See: http://api.jquery.com/jQuery.ajax/#jQuery-ajax-settings
     *                            Additional options are:
     *                            'document' - document for global events, @see phpQuery::getDocumentID()
     *                            'referer' - implemented
     *                            'requested_with' - TODO: not implemented (X-Requested-With)
     * @param null|mixed $xhr
     * @return Zend_Http_Client ?
     * @throws Exception
     * @TODO $options['cache']
     * @TODO $options['processData']
     * @TODO $options['xhr']
     * @TODO $options['data'] as string
     * @TODO XHR interface
     */
    public static function ajax($options = [], $xhr = null)
    {
        $options = array_merge(
            phpQuery::$ajaxSettings, $options
        );

        $documentID = isset($options['document'])
            ? phpQuery::getDocumentID($options['document'])
            : null;

        if ($xhr) {
            // reuse existing XHR object, but clean it up
            $client = $xhr;
            //$client->setParameterPost(null);
            //$client->setParameterGet(null);
            $client->setAuth(false);
            $client->setHeaders("If-Modified-Since", null);
            $client->setHeaders("Referer", null);
            $client->resetParameters();
        }
        else {
            // create new XHR object
            if (file_exists(__DIR__.'/Zend/Http/Client.php')) {
                require_once(__DIR__.'/Zend/Http/Client.php');
                $client = new Zend_Http_Client();
                $client->setCookieJar();
            }
            else {
                $client = null;
            }
        }

        if (empty($client)) {
            throw new Exception("XHR client not exist");
        }

        if (isset($options['timeout'])) {
            $client->setConfig([
                'timeout' => $options['timeout'],
                //'maxredirects' => 0,
            ]);
        }

        foreach (phpQuery::$ajaxAllowedHosts as $k => $host) {
            if ($host == '.' && isset($_SERVER['HTTP_HOST'])) {
                phpQuery::$ajaxAllowedHosts[$k] = $_SERVER['HTTP_HOST'];
            }
        }

        $host = parse_url($options['url'], PHP_URL_HOST);
        if (!in_array($host, phpQuery::$ajaxAllowedHosts)) {
            throw new Exception("Request not permitted, host '$host' not present in phpQuery::\$ajaxAllowedHosts");
        }

        // JSONP
        $jsre = "/=\\?(&|$)/";
        if (isset($options['dataType']) && $options['dataType'] == 'jsonp') {
            $jsonpCallbackParam = !empty($options['jsonp']) ? $options['jsonp'] : 'callback';
            if (strtolower($options['type']) == 'get') {
                if (!preg_match($jsre, $options['url'])) {
                    $sep = strpos($options['url'], '?') ? '&' : '?';
                    $options['url'] .= "$sep$jsonpCallbackParam=?";
                }
            }
            elseif ($options['data']) {
                $jsonp = false;
                foreach ($options['data'] as $n => $v) {
                    if ($v == '?') {
                        $jsonp = true;
                    }
                }
                if (!$jsonp) {
                    $options['data'][$jsonpCallbackParam] = '?';
                }
            }
            $options['dataType'] = 'json';
        }

        if (isset($options['dataType']) && $options['dataType'] == 'json') {
            $jsonpCallback = 'json_'.md5(microtime());
            $jsonpData = $jsonpUrl = false;
            if ($options['data']) {
                foreach ($options['data'] as $n => $v) {
                    if ($v == '?') {
                        $jsonpData = $n;
                    }
                }
            }
            if (preg_match($jsre, $options['url'])) {
                $jsonpUrl = true;
            }
            if ($jsonpData !== false || $jsonpUrl) {
                // remember callback name for httpData()
                $options['_jsonp'] = $jsonpCallback;
                if ($jsonpData !== false) {
                    $options['data'][$jsonpData] = $jsonpCallback;
                }
                if ($jsonpUrl) {
                    $options['url'] = preg_replace($jsre, "=$jsonpCallback\\1", $options['url']);
                }
            }
        }

        $client->setUri($options['url']);
        $client->setMethod(strtoupper($options['type']));

        if (isset($options['referer']) && $options['referer']) {
            $client->setHeaders('Referer', $options['referer']);
        }

        $client->setHeaders([
            //'content-type'  => $options['contentType'],
            'User-Agent'      => 'Mozilla/5.0 (X11; U; Linux x86; en-US; rv:1.9.0.5) Gecko/2008122010 Firefox/3.0.5',
            // TODO custom charset
            'Accept-Charset'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            //'Connection'    => 'keep-alive',
            //'Accept'        => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-us,en;q=0.5',
        ]);

        if ($options['username']) {
            $client->setAuth($options['username'], (!empty($options['password']) ? $options['password'] : ''));
        }

        if (isset($options['ifModified']) && $options['ifModified']) {
            $client->setHeaders(
                "If-Modified-Since",
                (!empty(phpQuery::$lastModified)
                    ? phpQuery::$lastModified :
                    "Thu, 01 Jan 1970 00:00:00 GMT"
                )
            );
        }

        $client->setHeaders(
            "Accept",
            (isset($options['dataType']) && isset(phpQuery::$ajaxSettings['accepts'][$options['dataType']])
                ? phpQuery::$ajaxSettings['accepts'][$options['dataType']].", */*"
                : phpQuery::$ajaxSettings['accepts']['_default']
            )
        );

        // TODO $options['processData']
        if ($options['data'] instanceof phpQueryObject) {
            $serialized = $options['data']->serializeArray($options['data']);
            $options['data'] = [];
            foreach ($serialized as $r) {
                $options['data'][$r['name']] = $r['value'];
            }
        }

        if (strtolower($options['type']) == 'get') {
            $client->setParameterGet($options['data']);
        }
        elseif (strtolower($options['type']) == 'post') {
            $client->setEncType($options['contentType']);
            $client->setParameterPost($options['data']);
        }

        if (phpQuery::$active == 0 && $options['global']) {
            phpQueryEvents::trigger($documentID, 'ajaxStart');
        }
        phpQuery::$active++;

        // beforeSend callback
        if (isset($options['beforeSend']) && $options['beforeSend']) {
            phpQuery::callbackRun($options['beforeSend'], [$client]);
        }

        // ajaxSend event
        if ($options['global']) {
            phpQueryEvents::trigger($documentID, 'ajaxSend', [$client, $options]);
        }

        // debug
        phpQuery::debug("{$options['type']}: {$options['url']}\n");
        phpQuery::debug("Options: <pre>".var_export($options, true)."</pre>\n");
        //if ($client->getCookieJar()) {
        //    phpQuery::debug("Cookies: <pre>".var_export($client->getCookieJar()->getMatchingCookies($options['url']), true)."</pre>\n");
        //}

        // response
        $response = $client->request();

        // debug
        phpQuery::debug('Status: '.$response->getStatus().' / '.$response->getMessage());
        phpQuery::debug($client->getLastRequest());
        phpQuery::debug($response->getHeaders());

        if ($response->isSuccessful()) {
            // XXX temporary
            phpQuery::$lastModified = $response->getHeader('Last-Modified');
            $data = phpQuery::httpData($response->getBody(), $options['dataType'], $options);

            if (isset($options['success']) && $options['success']) {
                phpQuery::callbackRun($options['success'], [$data, $response->getStatus(), $options]);
            }

            if ($options['global']) {
                phpQueryEvents::trigger($documentID, 'ajaxSuccess', [$client, $options]);
            }
        }
        else {
            if (isset($options['error']) && $options['error']) {
                phpQuery::callbackRun($options['error'], [$client, $response->getStatus(), $response->getMessage()]);
            }

            if ($options['global']) {
                phpQueryEvents::trigger($documentID, 'ajaxError', [$client, $response->getStatus(), $response->getMessage(), $options]);
            }
        }

        if (isset($options['complete']) && $options['complete']) {
            phpQuery::callbackRun($options['complete'], [$client, $response->getStatus()]);
        }

        if ($options['global']) {
            phpQueryEvents::trigger($documentID, 'ajaxComplete', [$client, $options]);
        }

        if ($options['global'] && !--phpQuery::$active) {
            phpQueryEvents::trigger($documentID, 'ajaxStop');
        }

        return $client;
    }

    /**
     * httpData function parse $data
     *
     * @param mixed  $data
     * @param string $type
     * @param array  $options
     * @return null|mixed
     */
    protected static function httpData($data, $type, $options)
    {
        if (isset($options['dataFilter']) && $options['dataFilter']) {
            $data = phpQuery::callbackRun($options['dataFilter'], [$data, $type]);
        }
        if (is_string($data)) {
            if ($type == "json") {
                if (isset($options['_jsonp']) && $options['_jsonp']) {
                    $data = preg_replace('/^\s*\w+\((.*)\)\s*$/s', '$1', $data);
                }
                $data = phpQuery::parseJSON($data);
            }
        }
        return $data;
    }

    /**
     * Generate URL-encoded query string
     *
     * @param array|phpQuery $data
     * @return string
     */
    public static function param($data)
    {
        return http_build_query($data, null, '&');
    }

    /**
     * get function using Ajax
     *
     * @param string               $url
     * @param null|mixed           $data
     * @param null|string|callable $callback
     * @param null|string          $type
     * @return Zend_Http_Client
     */
    public static function get($url, $data = null, $callback = null, $type = null)
    {
        if (!is_array($data)) {
            $callback = $data;
            $data = null;
        }

        // TODO some array_values on this shit
        return phpQuery::ajax([
            'type'     => 'GET',
            'url'      => $url,
            'data'     => $data,
            'success'  => $callback,
            'dataType' => $type,
        ]);
    }

    /**
     * post function using Ajax
     *
     * @param string               $url
     * @param null|mixed           $data
     * @param null|string|callable $callback
     * @param null|string          $type
     * @return Zend_Http_Client
     */
    public static function post($url, $data = null, $callback = null, $type = null)
    {
        if (!is_array($data)) {
            $callback = $data;
            $data = null;
        }

        return phpQuery::ajax([
            'type'     => 'POST',
            'url'      => $url,
            'data'     => $data,
            'success'  => $callback,
            'dataType' => $type,
        ]);
    }

    /**
     * getJSON function using Ajax
     *
     * @param string               $url
     * @param null|mixed           $data
     * @param null|string|callable $callback
     * @return Zend_Http_Client
     */
    public static function getJSON($url, $data = null, $callback = null)
    {
        if (!is_array($data)) {
            $callback = $data;
            $data = null;
        }

        // TODO some array_values on this shit
        return phpQuery::ajax([
            'type'     => 'GET',
            'url'      => $url,
            'data'     => $data,
            'success'  => $callback,
            'dataType' => 'json',
        ]);
    }

    /**
     * ajaxSetup function
     *
     * @param array $options
     * @return void
     */
    public static function ajaxSetup($options)
    {
        phpQuery::$ajaxSettings = array_merge(
            phpQuery::$ajaxSettings,
            $options
        );
    }

    /**
     * ajaxAllowHost function
     *
     * @param string      $host1
     * @param null|string $host2
     * @param null|string $host3
     */
    public static function ajaxAllowHost($host1, $host2 = null, $host3 = null)
    {
        $loop = is_array($host1)
            ? $host1
            : func_get_args();

        foreach ($loop as $host) {
            if ($host && !in_array($host, phpQuery::$ajaxAllowedHosts)) {
                phpQuery::$ajaxAllowedHosts[] = $host;
            }
        }
    }

    /**
     * ajaxAllowURL function
     *
     * @param string      $url1
     * @param null|string $url2
     * @param null|string $url3
     * @return void
     */
    public static function ajaxAllowURL($url1, $url2 = null, $url3 = null)
    {
        $loop = is_array($url1)
            ? $url1
            : func_get_args();

        foreach ($loop as $url) {
            phpQuery::ajaxAllowHost(parse_url($url, PHP_URL_HOST));
        }
    }

    /**
     * Returns JSON representation of $data.
     *
     * @param mixed $data
     * @return string
     */
    public static function toJSON($data)
    {
        if (function_exists('json_encode')) {
            return json_encode($data);
        }

        if (file_exists(__DIR__.'/Zend/Json/Encoder.php')) {
            require_once(__DIR__.'/Zend/Json/Encoder.php');
            if (class_exists('Zend_Json_Encoder')) {
                return Zend_Json_Encoder::encode($data);
            }
        }

        return '{}';
    }

    /**
     * Parses JSON into proper PHP type.
     *
     * @param string $json
     * @return mixed
     */
    public static function parseJSON($json)
    {
        if (function_exists('json_decode')) {
            $return = json_decode(trim($json), true);
            // json_decode and UTF8 issues
            if (isset($return)) {
                return $return;
            }
        }
        if (file_exists(__DIR__.'/Zend/Json/Decoder.php')) {
            require_once(__DIR__.'/Zend/Json/Decoder.php');
            if (class_exists('Zend_Json_Decoder')) {
                return Zend_Json_Decoder::decode($json);
            }
        }
        return null;
    }

    /**
     * Returns source's document ID.
     *
     * @param string|DOMDocument|DOMNode|phpQueryObject $source
     * @return string '' is not found
     */
    public static function getDocumentID($source)
    {
        if ($source instanceof DOMDocument) {
            foreach (phpQuery::$documents as $id => $document) {
                if ($source->isSameNode($document->document)) {
                    return $id;
                }
            }
        }
        elseif ($source instanceof DOMNode) {
            foreach (phpQuery::$documents as $id => $document) {
                if ($source->ownerDocument->isSameNode($document->document)) {
                    return $id;
                }
            }
        }
        elseif ($source instanceof phpQueryObject) {
            return $source->getDocumentID();
        }
        elseif (is_string($source) && isset(phpQuery::$documents[$source])) {
            return $source;
        }

        return '';
    }

    /**
     * Get DOMDocument object related to $source.
     * Returns null if such document doesn't exist.
     *
     * @param string|DOMDocument|DOMNode|phpQueryObject $source
     * @return null|DOMDocument
     */
    public static function getDOMDocument($source)
    {
        if ($source instanceof DOMDocument) {
            return $source;
        }

        $source = phpQuery::getDocumentID($source);
        return $source
            ? phpQuery::$documents[$source]['document']
            : null;
    }

    // UTILITIES
    // https://api.jquery.com/category/utilities

    /**
     * Convert an array-like object into a true JavaScript array.
     *
     * @link http://docs.jquery.com/Utilities/jQuery.makeArray
     * @param object|array $object
     * @return array
     */
    public static function makeArray($object)
    {
        $array = [];
        if (is_object($object) && $object instanceof DOMNodeList) {
            foreach ($object as $value) {
                $array[] = $value;
            }
        }
        elseif (is_object($object) && !($object instanceof Iterator)) {
            foreach (get_object_vars($object) as $name => $value) {
                $array[0][$name] = $value;
            }
        }
        else {
            foreach ($object as $name => $value) {
                $array[0][$name] = $value;
            }
        }
        return $array;
    }

    /**
     * A generic iterator function, which can be used to seamlessly iterate over both objects and arrays.
     * Arrays and array-like objects with a length property (such as a function's arguments object)
     * are iterated by numeric index, from 0 to length-1.
     * Other objects are iterated via their named properties.
     *
     * @link https://api.jquery.com/jQuery.each
     * @param object|array    $object
     * @param string|callable $callback
     * @param null|mixed      $param1
     * @param null|mixed      $param2
     * @param null|mixed      $param3
     * @return void
     */
    public static function each($object, $callback, $param1 = null, $param2 = null, $param3 = null)
    {
        $paramStructure = null;
        if (func_num_args() > 2) {
            $paramStructure = func_get_args();
            $paramStructure = array_slice($paramStructure, 2);
        }

        if (is_object($object) && !($object instanceof Iterator)) {
            foreach (get_object_vars($object) as $name => $value) {
                phpQuery::callbackRun($callback, [$name, $value], $paramStructure);
            }
        }
        else {
            foreach ($object as $name => $value) {
                phpQuery::callbackRun($callback, [$name, $value], $paramStructure);
            }
        }
    }

    /**
     * Translate all items in an array or object to new array of items.
     *
     * @link https://api.jquery.com/jQuery.map
     * @param array           $array
     * @param string|callable $callback
     * @param null|mixed      $param1
     * @param null|mixed      $param2
     * @param null|mixed      $param3
     * @return array
     */
    public static function map($array, $callback, $param1 = null, $param2 = null, $param3 = null)
    {
        $result = [];
        $paramStructure = null;
        if (func_num_args() > 2) {
            $paramStructure = func_get_args();
            $paramStructure = array_slice($paramStructure, 2);
        }

        foreach ($array as $v) {
            $vv = phpQuery::callbackRun($callback, [$v], $paramStructure);
            //$callbackArgs = $args;
            //foreach($args as $i => $arg) {
            //    $callbackArgs[$i] = ($arg instanceof CallbackParam)
            //        ? $v
            //        : $arg;
            //}
            //$vv = call_user_func_array($callback, $callbackArgs);
            if (is_array($vv)) {
                foreach ($vv as $vvv) {
                    $result[] = $vvv;
                }
            }
            elseif ($vv !== null) {
                $result[] = $vv;
            }
        }
        return $result;
    }

    /**
     * callbackRun
     *
     * @param string|Callback $callback
     * @param array           $params
     * @param null|mixed      $paramStructure
     * @return mixed
     */
    public static function callbackRun($callback, $params = [], $paramStructure = null)
    {
        if (empty($callback)) {
            return false;
        }

        if ($callback instanceof CallbackParameterToReference) {
            // TODO support ParamStructure to select which $param push to reference
            if (isset($params[0])) {
                $callback->callback = $params[0];
            }

            return true;
        }

        if ($callback instanceof Callback) {
            $paramStructure = $callback->params;
            $callback = $callback->callback;
        }

        if (!$paramStructure) {
            return call_user_func_array($callback, $params);
        }

        $p = 0;
        foreach ($paramStructure as $i => $v) {
            $paramStructure[$i] = ($v instanceof CallbackParam)
                ? $params[$p++]
                : $v;
        }

        return call_user_func_array($callback, $paramStructure);
    }

    /**
     * Merge 2 phpQuery objects.
     *
     * @param phpQueryObject $one
     * @param phpQueryObject $two
     * @TODO node lists, phpQueryObject
     */
    public static function merge($one, $two)
    {
        $elements = $one->elements;
        foreach ($two->elements as $node) {
            $exists = false;
            foreach ($elements as $node2) {
                if ($node2->isSameNode($node)) {
                    $exists = true;
                }
            }
            if (!$exists) {
                $elements[] = $node;
            }
        }
        return $elements;
        //$one = $one->newInstance();
        //$one->elements = $elements;
        //return $one;
    }

    /**
     * grep function
     *
     * @link http://docs.jquery.com/Utilities/jQuery.grep
     * @param array           $array
     * @param string|callable $callback
     * @param bool            $invert
     * @return array
     */
    public static function grep($array, $callback, $invert = false)
    {
        $result = [];
        foreach ($array as $k => $v) {
            $r = call_user_func_array($callback, [$v, $k]);
            if ($r === !$invert) {
                $result[] = $v;
            }
        }
        return $result;
    }

    /**
     * Removes duplicate values from an array
     *
     * @param array $array
     * @return array
     */
    public static function unique($array)
    {
        return array_unique($array);
    }

    /**
     * Verify that the contents of a variable can be called as a function
     *
     * @param string $function
     * @return bool
     * @TODO there are problems with non-static methods, second parameter pass it
     *     but does not verify is method is really callable
     */
    public static function isFunction($function)
    {
        return is_callable($function);
    }

    /**
     * Trim string
     *
     * @param string $str
     * @return string
     */
    public static function trim($str)
    {
        return trim($str);
    }

    /* PLUGINS NAMESPACE */
    /**
     * call WebBrowser.browserGet function in WebBrowser Plugin
     *
     * @param string          $url
     * @param string|callable $callback
     * @param null|mixed      $param1
     * @param null|mixed      $param2
     * @param null|mixed      $param3
     * @return phpQueryObject
     * @throws Exception
     */
    public static function browserGet($url, $callback, $param1 = null, $param2 = null, $param3 = null)
    {
        if (phpQuery::plugin('WebBrowser')) {
            $params = func_get_args();
            return phpQuery::callbackRun([phpQuery::$plugins, 'browserGet'], $params);
        }

        phpQuery::debug('WebBrowser plugin not available...');
        throw new Exception('WebBrowser plugin not available...');
    }

    /**
     * call WebBrowser.browserPost function in WebBrowser Plugin
     *
     * @param string          $url
     * @param mixed           $data
     * @param string|callable $callback
     * @param null|mixed      $param1
     * @param null|mixed      $param2
     * @param null|mixed      $param3
     * @return phpQueryObject
     * @throws Exception
     */
    public static function browserPost($url, $data, $callback, $param1 = null, $param2 = null, $param3 = null)
    {
        if (phpQuery::plugin('WebBrowser')) {
            $params = func_get_args();
            return phpQuery::callbackRun([phpQuery::$plugins, 'browserPost'], $params);
        }

        phpQuery::debug('WebBrowser plugin not available...');
        throw new Exception('WebBrowser plugin not available...');
    }

    /**
     * call WebBrowser.browser function in WebBrowser Plugin
     *
     * @param mixed           $ajaxSettings
     * @param string|callable $callback
     * @param null|mixed      $param1
     * @param null|mixed      $param2
     * @param null|mixed      $param3
     * @return phpQueryObject
     * @throws Exception
     */
    public static function browser($ajaxSettings, $callback, $param1 = null, $param2 = null, $param3 = null)
    {
        if (phpQuery::plugin('WebBrowser')) {
            $params = func_get_args();
            return phpQuery::callbackRun([phpQuery::$plugins, 'browser'], $params);
        }

        phpQuery::debug('WebBrowser plugin not available...');
        throw new Exception('WebBrowser plugin not available...');
    }

    /**
     * Gen PHP tag with $code
     *
     * @param string $code
     * @return string
     */
    public static function php($code)
    {
        return phpQuery::code('php', $code);
    }

    /**
     * Gen $type tag with $code
     *
     * @param string $type Type tag
     * @param string $code
     * @return string
     */
    public static function code($type, $code)
    {
        return "<$type><!-- ".trim($code)." --></$type>";
    }

    /**
     * __callStatic function in Plugin
     *
     * @param string $method Method name in Plugin
     * @param mixed  $params
     * @return mixed
     */
    public static function __callStatic($method, $params)
    {
        return call_user_func_array(
            [phpQuery::$plugins, $method],
            $params
        );
    }

    /**
     * dataSetupNode function
     *
     * @param DOMNode $node
     * @param string  $documentID
     * @return DOMNode
     */
    protected static function dataSetupNode($node, $documentID)
    {
        // search are return if already exists
        foreach (phpQuery::$documents[$documentID]->dataNodes as $dataNode) {
            if ($node->isSameNode($dataNode)) {
                return $dataNode;
            }
        }
        // if it doesn't, add it
        phpQuery::$documents[$documentID]->dataNodes[] = $node;
        return $node;
    }

    /**
     * dataRemoveNode function
     *
     * @param DOMNode $node
     * @param string  $documentID
     * @return void
     */
    protected static function dataRemoveNode($node, $documentID)
    {
        // search are return if already exists
        foreach (phpQuery::$documents[$documentID]->dataNodes as $k => $dataNode) {
            if ($node->isSameNode($dataNode)) {
                unset(phpQuery::$documents[$documentID]->dataNodes[$k]);
                unset(phpQuery::$documents[$documentID]->data[$dataNode->dataID]);
            }
        }
    }

    /**
     * data function
     *
     * @param DOMNode     $node
     * @param string      $name
     * @param mixed       $data
     * @param null|string $documentID
     * @return int|mixed
     */
    public static function data($node, $name, $data, $documentID = null)
    {
        if (empty($documentID)) {
            // TODO check if this works
            $documentID = phpQuery::getDocumentID($node);
        }

        $document = phpQuery::$documents[$documentID];
        $node = phpQuery::dataSetupNode($node, $documentID);
        if (!isset($node->dataID)) {
            $node->dataID = ++phpQuery::$documents[$documentID]->uuid;
        }

        $id = $node->dataID;
        if (!isset($document->data[$id])) {
            $document->data[$id] = [];
        }

        if (!is_null($data)) {
            $document->data[$id][$name] = $data;
        }

        return (!empty($name) && isset($document->data[$id][$name]))
            ? $document->data[$id][$name]
            : $id;
    }

    /**
     * removeData function
     *
     * @param DOMNode $node
     * @param string  $name
     * @param string  $documentID
     */
    public static function removeData($node, $name, $documentID)
    {
        if (empty($documentID)) {
            // TODO check if this works
            $documentID = phpQuery::getDocumentID($node);
        }

        $document = phpQuery::$documents[$documentID];
        $node = phpQuery::dataSetupNode($node, $documentID);
        $id = $node->dataID;

        if ($name) {
            if (isset($document->data[$id][$name])) {
                unset($document->data[$id][$name]);
            }

            $name = null;
            foreach ($document->data[$id] as $name) {
                break;
            }
            if (!$name) {
                phpQuery::removeData($node, $name, $documentID);
            }
        }
        else {
            phpQuery::dataRemoveNode($node, $documentID);
        }
    }

    /**
     * Log Debug
     *
     * @param mixed $data
     */
    public static function debug($data)
    {
        if (phpQuery::$debug) {
            var_dump($data);
        }
    }
}

/**
 * Class phpQueryPlugins
 * Plugins static namespace class.
 *
 * @author  Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 * @TODO    move plugin methods here (as statics)
 */
class phpQueryPlugins
{
    public function __call($method, $args)
    {
        if (isset(phpQuery::$extendStaticMethods[$method])) {
            $return = call_user_func_array(phpQuery::$extendStaticMethods[$method], $args);
        }
        elseif (isset(phpQuery::$pluginsStaticMethods[$method])) {
            $class = phpQuery::$pluginsStaticMethods[$method];
            $realClass = "phpQueryPlugin_$class";
            $return = call_user_func_array([$realClass, $method], $args);
        }
        else {
            throw new Exception("Method '{$method}' doesnt exist");
        }

        return isset($return) ? $return : $this;
    }
}

/**
 * Shortcut to phpQuery::pq($arg1, $context)
 *
 * @param string|array|DOMNode|DOMNodeList   $arg1    HTML markup, CSS Selector, DOMNode or array of DOMNodes
 * @param null|string|phpQueryObject|DOMNode $context DOM ID from $pq->getDocumentID(), phpQuery object (determines also query root) or DOMNode (determines also query root)
 * @return phpQueryObject
 * @see     phpQuery::pq()
 * @author  Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 */
function pq($arg1, $context = null)
{
    $args = func_get_args();
    return call_user_func_array(['phpQuery', 'pq'], $args);
}

// add plugins dir and Zend framework to include path
//set_include_path(
//    get_include_path()
//    .PATH_SEPARATOR.dirname(__FILE__).'/phpQuery/'
//    .PATH_SEPARATOR.dirname(__FILE__).'/phpQuery/plugins/'
//);

// why ? no __call nor __get for statics in php...
// XXX __callStatic will be available in PHP 5.3
phpQuery::$plugins = new phpQueryPlugins();

// include bootstrap file (personal library config)
// This file is executed everytime phpQuery is included.
if (file_exists(__DIR__.'/bootstrap.php')) {
    require_once __DIR__.'/bootstrap.php';
}
// Probably you want to use one of those functions here:
//phpQuery::ajaxAllowHost();
//phpQuery::ajaxAllowURL();
//phpQuery::plugin();
