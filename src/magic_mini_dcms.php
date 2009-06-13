<?php
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is MozillaService.org
 *
 * The Initial Developer of the Original Code is
 * The Mozilla Foundation.
 * Portions created by the Initial Developer are Copyright (C) 2006
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Austin King <aking@mozilla.com> (Original Author)
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

/**
 * Magic Mini Distributed CMS
 * client side user pastes in url or embed code. If it is an url the client
 * requests candidate media objects and gets a potentially empty list of
 * media objects
 */

class Media_Item
{
    public $type;
    public $width;
    public $height;
    public $uri;
    
    const NULL_MEDIA = "null_media";
    
    public static function makeEmpty()
    {
        return new Media_Item;
    }
    
    public function __construct($type=Media_Item::NULL_MEDIA, $width=0, $height=0, $mediaUrl='')
    {
        $this->type = $type;
        $this->width = $width;
        $this->height = $height;
        $this->uri = $mediaUrl;
    }
    
    public function valid()
    {
        return $type != Media_Item::NULL_MEDIA;
    }
}

abstract class Media_Finder
{
    private $domXpath = NULL;
    
    /**
    * @param  string the url which hosts our media
    * @retrun list of Media objects
    */
    public static function mediaIn($url)
    {
       return Media_Finder::dispatch($url);
    }
    
    /**
     * Given a String of HTML, instances of
     * Media_Finder will search for valid media objects
     * such as photos or videos and return a list of them.
     * @param string $html - A String of HTML
     * @return array - An array of Media_Items, or an empty array
     */
    protected abstract function findAll($html);

    protected static function dispatch($uri)
    {
        $finder = Media_Finder::functionFor($uri);
        return $finder->findAll( $finder->html($uri));
    }
    
    protected static function functionFor($uri)
    {
        if (preg_match("/https?:\/\/(www\.)?flickr/", $uri)) {
            return new Flickr_Finder;
        } elseif (preg_match("/https?:\/\/(www\.)?youtube/", $uri)) {
            return new Youtube_Finder;
        } elseif (preg_match("/https?:\/\/(www\.)?vimeo/", $uri)) {
            return new Vimeo_Finder;
        } elseif (preg_match("/https?:\/\/(www\.)?dailymotion/", $uri)) {
            return new Dailymotion_Finder;
        } else {
            return new Fallback_Finder;
        }
    }
    
    /**
     * @category Testing
     */
    public static function testFunctionFor($uri) { return Media_Finder::functionFor($uri); }
    
    public function html($uri)
    {
        $ch = curl_init();
        $userAgent = "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.0.5) Gecko/2008120121 Firefox/3.0.5";
                             
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $html = curl_exec($ch);
        
        if (!$html) {
            echo "
        cURL error number:" .curl_errno($ch);
            echo "
        cURL error:" . curl_error($ch);
            exit;
        }
        curl_close($ch);
        
        return $this->_tidy($html);
    }
    
    protected function _tidy($html)
    {
        
        $tidy = new tidy;
        $tidy->parseString($html, array('output-xhtml' => TRUE), 'utf8');
        $tidy->cleanRepair();
        return $tidy;        
    }

    protected function evalXpath($html, $xpath, $contextNode=NULL)
    {
        if (is_null($this->domXpath)) {
            $domDoc = new DOMDocument();
            @$domDoc->loadHTML($html);
            $this->domXpath = new DOMXPath($domDoc);            
        } 
        if (is_null($contextNode)) {
            return $this->domXpath->evaluate($xpath);    
        } else {
            return $this->domXpath->evaluate($xpath, $contextNode);    
        }        
    }
}

class Youtube_Finder extends Media_Finder
{
    function findAll($html)
    {
        $rv = array();
        $embeds = $this->evalXpath($html, "//input[@id='embed_code']");          
        if (is_null($embeds)) {
            return $rv;
        }
        for ($i=0; $i<$embeds->length; $i++) {
            $objectHtml = $embeds->item($i)->getAttribute('value');
        
        //$objectHtml was a serialized into value, but is now a well formed piece of HTML
        //so let's unpack it
            $objectDom = new DOMDocument();
            $objectDom->loadHTML($this->_tidy($objectHtml));
            $os = $objectDom->getElementsByTagName('object');
            
            if ($os->length >= 1) {
                $o = $os->item(0);
                $es = $objectDom->getElementsByTagName('embed');
                if ($es->length >= 1) {
                    $e = $es->item(0);
                    array_push($rv, new Media_Item('youtube',
                                   $o->getAttribute('width'), $o->getAttribute('height'),
                                   $e->getAttribute('src')));
                }
            }
        }
        
        return $rv;
    }
}

class Vimeo_Finder extends Media_Finder
{        
    function findAll($html)
    {
        $rv = array();
        $embeds = $this->evalXpath($html, "//object");        
        if (is_null($embeds)) {
            return $rv;
        }
        for ($i=0; $i<$embeds->length; $i++) {
            $embed = $embeds->item($i);
            $width = $embed->getAttribute('width');
            $height = $embed->getAttribute('height');
            $uri = $embed->getAttribute('data');
            if (strpos($uri, 'http') == FALSE || strpos($uri, 'vimeo.com') == FALSE) {
                $uri = 'http://vimeo.com' . $uri;
            }
            if (! empty($width) && intval($width) > 0 &&
                ! empty($height) && intval($height) > 0 &&
                ! empty($uri)) {
                array_push($rv, new Media_Item('vimeo', $width, $height, $uri));
            }
        }
        
        return $rv;
    }
}

class Flickr_Finder extends Media_Finder
{    
    function findAll($html)
    {
        $rv = array();
        $imgs = $this->evalXpath($html, "//div[@class='photoImgDiv']/img");        
        if (is_null($imgs)) {
            return $rv;
        }
        for ($i=0; $i<$imgs->length; $i++) {
            $img = $imgs->item($i);
            $width = $img->getAttribute('width');
            $height = $img->getAttribute('height');
            $uri = $img->getAttribute('src');
            
            //TODO slurp in <link rel="canonical" and add to link attribute
            if (! empty($width) && intval($width) > 0 &&
                ! empty($height) && intval($height) > 0 &&
                ! empty($uri)) {
                $flickrImage = new Media_Item('flickr', $width, $height, $uri);
                
                array_push($rv, $flickrImage);
            }
        }
        return $rv;
    }
}

class Dailymotion_Finder extends Media_Finder
{
    function findAll($html)
    {
        $rv = array();
        $embeds = $this->evalXpath($html, "//object");
        if (is_null($embeds)) {
            return $rv;
        }
        for ($i=0; $i<$embeds->length; $i++) {
            $embed = $embeds->item($i);
            
            $movie = "";
            $flashvars = "";
                        
            $params = $this->evalXpath($html, "//param", $embed);
            for ($i=0; $i<$params->length; $i++) {
                $param = $params->item($i);
                if ($param->getAttribute('name') == 'movie') {
                    $movie = 'http://www.dailymotion.com' . $param->getAttribute('value');
                } elseif ($param->getAttribute('name') == 'flashvars') {
                    $flashvars = $param->getAttribute('value');
                }
            }
            $item = new Media_Item('dailymotion', 320, 240, $movie);
            $item->flashvars = $flashvars;
            array_push($rv, $item);
        }
        return $rv;
    }
}

/**
 * General Fallback for finding usable pieces of media content in
 * a page.
 * 
 * Security note: It's safe to offer arbitrary images as valid
 * media objects, but we probably should not offer to embed
 * random object / embed tags.
 */
class Fallback_Finder extends Media_Finder
{
    function findAll($html)
    {
        $imgs = $this->evalXpath($html, "//img");
        $rv = array();
        if (is_null($imgs)) {
            return $rv;
        }
        for ($i=0; $i < $imgs->length; $i++) {
            $img = $imgs->item($i);
            if ($img->hasAttribute('width') &&
                $img->hasAttribute('height') &&
                $img->hasAttribute('src')) {                
                
                    $width = $img->getAttribute('width');
                    $height = $img->getAttribute('height');
                    $uri = $img->getAttribute('src');
                    
                    if (! empty($width) && intval($width) > 0 &&
                        ! empty($height) && intval($height) > 0 &&
                        ! empty($uri)) {
                        array_push($rv, new Media_Item('image', $width, $height, $uri));
                    }
            } 
        }
        $this->_sortByHeight($rv);
        return $rv;
    }
    
    private function _sortByHeight(&$images)
    {
        function sizeSort($a, $b)
        {
            if ($a == $b || ($a->height == $b->height && $a->width == $b->width)) {
                    return 0;
                } elseif ($a->height * $a->width < $b->height * $b->width) {
                    return 1;
                } else {
                    return -1;
                }
        }
        usort($images, "sizeSort");
    }
    /**
     * @category Testing
     */
    public function testSortByHeight(&$images) { return $this->_sortByHeight($images); }
}
?>