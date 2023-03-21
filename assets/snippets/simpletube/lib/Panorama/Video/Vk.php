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

class Vk  implements VideoInterface
{
    public $url;
    private $feed = null;
    public $options = array();

    /**
     * @param $url
     * @param array $options
     * @throws \Exception
     */
    public function __construct($url, array $options = array())
    {
        $this->url = $url;
        if (!array_key_exists('vk', $options)
            || !array_key_exists('accessToken', $options['vk'])
            || empty($options['vk']['accessToken'])
        ) {
            throw new \Exception("Missing vk configuration.");
        }
        $this->options = $options['vk'];
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
        $accessToken = $this->options['accessToken'];
        if (!$accessToken) {
            throw new \Exception("Unable to get access token.");
        }
        if (!isset($this->feed)) {
            $videoId = $this->getVideoID();

            $url = "https://api.vk.com/method/video.get?v=5.131&videos={$videoId}&access_token={$accessToken}";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            $data = curl_exec($ch);
            curl_close($ch);

            $videoObj = @json_decode($data);

            if (!empty($videoObj->error->error_msg)) {
                throw new \Exception($videoObj->error->error_msg);
            }

            if (empty($videoObj->response->items)) {
                throw new \Exception('Video Id not valid.');
            }

        }
        return $videoObj->response->items[0];
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
        return $this->feed->title;
    }

    /*
     * Returns the descrition for this video
     *
     * @returns string, the description of this video
     */
    public function getDescription()
    {
        return $this->feed->description;
    }

    /*
     * Returns the embed url of the video
     *
     * @returns string, the embed url of the video
     */
    public function getEmbedUrl()
    {
        return $this->feed->player;
    }

    /*
     * Returns the service name for this video
     *
     * @returns string, the service name of this video
     */
    public function getService()
    {
        return "VK";
    }

    /*
     * Returns the url for downloading the flv video file
     *
     * @returns string, the url for downloading the flv video file
     */
    public function getDownloadUrl()
    {
        return '';
    }

    /*
     * Returns the duration in sec of the video
     *
     * @returns string, the duration in sec of the video
     */
    public function getDuration()
    {
        return $this->feed->duration;
    }

    /*
     * Returns the video Thumbnail
     *
     * @returns string, the video thumbnail url
     */
    public function getThumbnail()
    {
        $thumbnail = end($this->feed->image)->url;
        return $thumbnail;
    }

    /*
     * Returns the video tags
     *
     * @returns mixed, a list of tags for this video
     */
    public function getTags()
    {
        return '';
    }

    /*
     * Returns the watch url for the video
     *
     * @returns string, the url for watching this video
     */
    public function getWatchUrl()
    {
        return $this->url;
    }
    public function getEmbedHTML()
    {
        return '';
    }

    public function getFLV()
    {
        return '';
    }

    /**
     * Returns the value of the param given
     *
     * @param string, the param to look for
     * @return string, the value of the param
     */
    private function getIdFromUrl($url)
    {
        preg_match("/(-?\d+)[^\d]+(\d+)/", $url, $matches);
        return isset($matches[1]) && isset($matches[2]) ? $matches[1].'_'.$matches[2] : false;
    }
}
