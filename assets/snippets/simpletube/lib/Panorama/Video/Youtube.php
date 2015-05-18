<?php
/**
 * This file is part of the Onm package.
 *
 * (c)  OpenHost S.L. <developers@openhost.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 **/
/**
 * Wrapper class for Youtube
 *
 * @author Fran Diéguez <fran@openhost.es>
 * @package Panorama\Video
 **/
namespace Panorama\Video;

class Youtube  implements VideoInterface
{
    public $url;
    public $options = array();

    /**
     * @param $url
     * @param array $options
     * @throws \Exception
     */
    public function __construct($url, array $options = array())
    {
        $this->url = $url;
        if (!array_key_exists('youtube', $options)
            && !array_key_exists('api_key', $options['youtube'])
            && empty($options['youtube']['api_key'])
        ) {
            throw new \Exception("Missing Youtube configuration.");
        }
        $this->options = $options['youtube'];
        if (!($this->videoId = $this->getVideoId())) {
            throw new \Exception("Cannot fetch video data.", 1);
        }
        $this->feed = $this->getFeed();

    }

    /*
     * Returns the feed that contains information of video
     *
     */
    public function getFeed()
    {
        if (!isset($this->feed)) {
            $videoId = $this->getVideoID();
            $apikey = $this->options['api_key'];

            $url =
                "https://www.googleapis.com/youtube/v3/videos?key=".$apikey
                ."&id=".$videoId
                ."&part=snippet,contentDetails,statistics,player"
            ;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            $data = curl_exec($ch);
            curl_close($ch);

            $videoObj = @json_decode($data);
            if (empty($videoObj->items)) {
                throw new \Exception('Video Id not valid.');
            }

        }
        return $videoObj->items[0];
    }

    /*
     * Returns the video ID from the video url
     *
     * @returns string, the Youtube ID of this video
     */
    public function getVideoId()
    {
        if (!isset($this->videoId)) {
            $this->videoId =  $this->getIdFromUrl($this->url);
        }
        return $this->videoId;
    }

    /*
     * Returns the video title
     *
     */
    public function getTitle()
    {

        if (!isset($this->title)) {
            $this->title = (string) $this->feed->snippet->title;
        }

        return $this->title;
    }

    /*
     * Returns the descrition for this video
     *
     * @returns string, the description of this video
     */
    public function getDescription()
    {
        if (!isset($this->description)) {
            $content = $this->feed->snippet->description;
            $this->description = (string) $content;
        }

        return $this->description;
    }

    /*
     * Returs the object HTML with a specific width, height and options
     *
     * @param width,   the width of the final flash object
     * @param height,  the height of the final flash object
     * @param options, you can read more about the youtube player options
     *                 in  http://code.google.com/intl/en/apis/
     *                     youtube/player_parameters.html
     *                 Use them in options
     *                 (ex {:rel => 0, :color1 => '0x333333'})
     */
    public function getEmbedHTML($options = array())
    {
        $defaultOptions = array('width' => 560, 'height' => 349);
        $options = array_merge($defaultOptions, $options);

        // convert options into
        $htmlOptions = "";
        if (count($options) > 0) {
            foreach ($options as $key => $value) {
                if (in_array($key, array('width', 'height'))) {
                    continue;
                }
                $htmlOptions .= "&" . $key . "=" . $value;
            }
        }
        $embedUrl = $this->getEmbedUrl();

        // if this video is not embed
        return   "<object width='{$options['width']}' "
                ."height='{$options['height']}'>\n"
                ."<param name='movie' value='{$embedUrl}{$htmlOptions}'>\n"
                ."<param name='allowFullScreen' value='true'>\n"
                ."<param name='allowscriptaccess' value='always'>\n"
                ."<param name='wmode' value='transparent'>\n"
                ."<embed src='{$embedUrl}{$htmlOptions}' "
                    ."type='application/x-shockwave-flash'\n"
                    ."allowscriptaccess='always' allowfullscreen='true'\n"
                    ."width='{$options['width']}' "
                    ."height='{$options['height']}'>\n"
                ."</object>";
    }

    /*
     * Returns the FLV url
     *
     * @returns string, the url to the video URL
     */
    public function getFLV()
    {
        if (!isset($this->FLV)) {
            $this->FLV =  $this->getEmbedUrl();
        }

        return $this->FLV;

    }

    /*
     * Returns the embed url of the video
     *
     * @returns string, the embed url of the video
     */
    public function getEmbedUrl()
    {
        $this->embedUrl = '';
        if (empty($this->embedUrl))
            $this->embedUrl = 'http://www.youtube.com/embed/'.$this->getVideoID();
        if ($listId = $this->getListIdFromUrl($this->url)) $this->embedUrl .= '?list='.$listId;
        return $this->embedUrl;
    }

    /*
     * Returns the service name for this video
     *
     * @returns string, the service name of this video
     */
    public function getService()
    {
        return "Youtube";
    }

    /*
     * Returns the url for downloading the flv video file
     *
     * @returns string, the url for downloading the flv video file
     */
    public function getDownloadUrl()
    {
        if (!isset($this->downloadUrl)) {
            $this->downloadUrl = $this->getEmbedUrl();
        }

        return $this->embedUrl;
    }

    /*
     * Returns the duration in sec of the video
     *
     * @returns string, the duration in sec of the video
     */
    public function getDuration()
    {
        if (!isset($this->duration)) {
            $this->duration = new \DateTime('@0'); // Unix epoch
            $this->duration->add(
                new \DateInterval($this->feed->contentDetails->duration));
        }
        return $this->duration->getTimestamp();
    }

    /*
     * Returns the video Thumbnails
     *
     * @returns mixed, the video thumbnails
     */
    public function getThumbnails()
    {
        if (!isset($this->thumbnails))
            $this->thumbnails = $this->feed->snippet->thumbnails;
        return $this->thumbnails;
    }

    /*
     * Returns the video Thumbnail
     *
     * @returns string, the video thumbnail url
     */
    public function getThumbnail()
    {
        if (!isset($this->thumbnail)) {
            $thumbnails = $this->getThumbnails();
            
            $this->thumbnail = (string) $thumbnails->high->url;
        }
        return $this->thumbnail;
    }

    /*
     * Returns the video tags
     *
     * @returns mixed, a list of tags for this video
     */
    public function getTags()
    {
        if (!isset($this->tags))
            $this->tags = (string) $this->feed->snippet->title;

        return $this->tags;
    }

    /*
     * Returns the watch url for the video
     *
     * @returns string, the url for watching this video
     */
    public function getWatchUrl()
    {
        if (!isset($this->watchUrl))
            $this->watchUrl = $this->url;

        return $this->watchUrl;
    }

    /**
     * Returns the value of the param given
     *
     * @param string, the param to look for
     * @return string, the value of the param
     */
    private function getIdFromUrl($url)
    {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $matches);
        return $matches[1];
    }
    private function getListIdFromUrl($url) {
        preg_match('%list=([^&]*)%i',$url,$matches);
        return empty($matches) ? false : $matches[1];
    }
}
