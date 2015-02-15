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
 * Wrapper class for Rutube videos
 *
 * @author Fran Diéguez <fran@openhost.es>
 * @version \$Id\$
 * @copyright OpenHost S.L., Mér Xuñ 01 15:58:58 2011
 * @package Panorama\Video
 **/
namespace Panorama\Video;

class Rutube implements VideoInterface
{
    public $url;
    public $options = array();

    private $rtAPIUrl = "http://rutube.ru/api/video/";

    /**
     * @param $url
     * @param array $options
     */
    public function __construct($url, array $options = array())
    {
        $this->url = $url;
        $this->options = $options;
        if (!($this->videoId = $this->getvideoId())) {
            throw new \Exception("Cannot fetch video data.", 1);
        }
        $this->getRtInfo();
    }

    /*
     * Returns the title for this Rutube video
     *
     */
    public function getTitle()
    {
        //rt_info["movie"][0]["title"][0].strip
        if (!isset($this->title)) {
            $rtInfo = $this->getRtInfo();
            $this->title = $rtInfo->title;
        }

        return $this->title;
    }

    /*
     * Returns the thumbnail for this Rutube video
     *
     */
    public function getThumbnail()
    {
        if (!isset($this->thumbnail)) {
            $rtInfo = $this->getRtInfo();
            $this->thumbnail = $rtInfo->thumbnail_url;
        }

        return $this->thumbnail;
    }

    /*
     * Returns the duration in secs for this Rutube video
     *
     */
    public function getDuration()
    {
        if (!isset($this->duration)) {
            $rtInfo = $this->getRtInfo();
            $this->duration = $rtInfo->duration;
        }

        return $this->duration;
    }

    /*
     * Returns the embed url for this Rutube video
     *
     */
    public function getEmbedUrl()
    {
        if (!isset($this->embedUrl)) {
            $rtInfo = $this->getRtInfo();
            $this->embedUrl = $rtInfo->embed_url;
        }

        return $this->embedUrl;
    }

    /*
     * Returns the HTML object to embed for this Rutube video
     *
     */
    public function getEmbedHTML($options = array())
    {
        return  null;
    }

    /*
     * Returns the FLV url for this Rutube video
     *
     */
    public function getFLV()
    {
        return null;
    }

    /*
     * Returns the Download url for this Rutube video
     *
     */
    public function getDownloadUrl()
    {
        return null;
    }

    /*
     * Returns the name of the Video service
     *
     */
    public function getService()
    {
        return "Rutube";
    }

    /*
     * Loads the video information from Rutube API
     *
     */
    public function getRtInfo()
    {

        $videoId = (string) $this->getVideoId();
        $url = $this->rtAPIUrl . "{$videoId}";

        if (!isset($this->rtInfo)) {
            $content = @file_get_contents($url);
            if (!$content) {
                throw new \Exception('Не удалось определить видео.');
            }
            $this->rtInfo = json_decode($content);
        }

        return $this->rtInfo;

    }

    /*
     * Calculates the Video ID from an Rutube URL
     *
     * @param $url
     */
    public function getVideoID()
    {
        if (!isset($this->videoId)) {
            $path = parse_url($this->url, PHP_URL_PATH);
            $parts = explode('/',trim($path,' /'));
            if ($parts[0] == 'video' && $parts[1] != 'embed') {
                $id = $parts[1];
            } else {
                $id = false;    
            }

            $this->videoId = $id;
        }

        return $this->videoId;
    }
}
