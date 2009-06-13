<?php
require_once('../lib/simpletest/autorun.php');
require_once('../../web/application/libraries/magic_mini_dcms.php');

class TestMagicMiniDCMS extends UnitTestCase
{
    /*
     * Want to add a new site?
     * Change this method to testIntegration and add your url...
     * say http://openvideo.dailymotion.com/us/video/x97w8x_the-day-the-robots-woke-up_shortfilms
     * php all_tests.php > ../data/dcms-dailymotion.html
     * Change this method back to Integration so it won't run every time.
     * Edit first and last couple of lines
     * Read their HTML and use XPather to experiment and write your plugin
     * Create a new Dailymotion_Finder and testDailymotion below
     * write a regexp in Media_Finder functionFor which returns your Dailymotion_finder
     */
    function /*test*/Itegration()
    {
        $finder = new Fallback_Finder;
        echo $finder->html('http://www.dailymotion.com/relevance/search/chickens/video/x5zw0z_chicken-dance_music');
        //$mediaItems = Media_Finder::mediaIn('http://openvideo.dailymotion.com/us/video/x97w8x_the-day-the-robots-woke-up_shortfilms');
        //print_r($mediaItems);
        //$this->assertTrue(count($mediaItems) > 0);
    }
    
    function testDailymotion()
    {
        $filename = $this->data("dcms-dailymotion.html");
        $finder = Media_Finder::testFunctionFor('http://www.dailymotion.com/relevance/search/chickens/video/x5zw0z_chicken-dance_music');
        
        $this->assertTrue($finder instanceof Dailymotion_Finder, "We have linked up Dailymotion");
        
        $actual = $finder->findAll($finder->html($filename));
        $this->assertEqual(count($actual), 1);
        $this->assertEqual($actual[0]->type, 'dailymotion');
        $this->assertEqual($actual[0]->width, 320);
        $this->assertEqual($actual[0]->height, 240);
        $this->assertEqual($actual[0]->uri, 'http://www.dailymotion.com/flash/dmplayer/dmplayer.swf?1198005301');
        $this->assertEqual($actual[0]->flashvars,
                           'url=rev=1198005301&lang=en&callback=player_proxy&videoUrl=http%3A%2F%2Fwww.dailymotion.com%2Fvideo%2Fx5zw0z_chicken-dance_music&embedUrl=http%3A%2F%2Fwww.dailymotion.com%2Fswf%2Fx5zw0z&preview=http%3A%2F%2Fak2.static.dailymotion.com%2Fstatic%2Fvideo%2F745%2F270%2F10072547%3Ajpeg_preview_large.jpg%3F20080702230035&hmz=706c61796572&autoPlay=1&external=0&video=%2Fcdn%2FFLV-320x240%2Fvideo%2Fx5zw0z%3Fkey%3D57b52c641094d894ad0393122d7aeca313c68de%40%40spark%7C%7C%2Fcdn%2FFLV-80x60%2Fvideo%2Fx5zw0z%3Fkey%3D57b52c641094d894ad0393122d7aeca313c68de%40%40spak-mini%7C%7C%2Fcdn%2FH264-512x384%2Fvideo%2Fx5zw0z%3Fkey%3D57b52c641094d894ad0393122d7aeca313c68de%40%40h264&instream=external%40%40advertising%40%40your+video+in+%25%25c%25%25+second%25%25p%25%25s%25%25&log=http%3A%2F%2Flogger.dailymotion.com%2Fvideo%2Faccess%2Fx5zw0z%3Fsession_id%3D%26referer%3D%26country%3DUS%26key%3D9u7oogdvhi1o7h5f0kknh6o%26v%3D4a261ac0%26i%3Dd8fe0cac%26h%3Dfed922b59c6ead8dbf2591108b173e6d%40%40firstplay%7C%7Chttp%3A%2F%2Fsense.dailymotion.com%2Fimages%2Fvs%2Ftag.gif%3Flog%3D1%26action%3DHQ%252Fenabled%26video%3D10072547%26videotitle%3Dchicken%2Bdance%26version%3Ddaily-2.14.11-prod%26actionpage%3Dplayer%252Finternal%252Fdailymotion%252Frecent%40%40enable_hq%7C%7Chttp%3A%2F%2Fsense.dailymotion.com%2Fimages%2Fvs%2Ftag.gif%3Flog%3D1%26action%3DHQ%252Fdisabled%26video%3D10072547%26videotitle%3Dchicken%2Bdance%26version%3Ddaily-2.14.11-prod%26actionpage%3Dplayer%252Finternal%252Fdailymotion%252Frecent%40%40disable_hq&selfURL=http%3A%2F%2Fwww.dailymotion.com%2Frelevance%2Fsearch%2Fchickens%2Fvideo%2Fx5zw0z_chicken-dance_music&hide=%40info%40%40share%40%40osdlogo%40&share=%2Fwidget%2Fvideo%2Fshare%2Fx5zw0z_chicken-dance_music%3Fskin%3Ddefault&info=%2Frss%2Fvideo%2Fx5zw0z_chicken-dance_music&related=%2Frss%2Frelated%2Fx5zw0z_chicken-dance_music%2F1%3A30&theFilterParams=VideoRecommendedVideo+10072547+15+++15+10441526+true+xml&relatedAtEnd=1&playerPath=%2Fflash%2Fdmplayer%2Fdmplayer.swf&info_videoId=x5zw0z&info_videoChannel=music&info_userLogin=zlovestny48');
        
    }
    
    function testYoutube()
    {
        //TODO testFunctionFor
        
        $filename = $this->data("dcms-youtube.html");
        $uTube = new Youtube_Finder;
        $actual = $uTube->findAll($uTube->html($filename));
        $this->assertEqual(count($actual), 1);
        $this->assertEqual($actual[0]->type, 'youtube');
        $this->assertEqual($actual[0]->width, 425);
        $this->assertEqual($actual[0]->height, 344);
        $this->assertEqual($actual[0]->uri, 'http://www.youtube.com/v/HY_utC-hrjI&hl=en&fs=1');
    }
    
    function testVimeo()
    {
        $filename = $this->data("dcms-vimeo.html");
        $vimeo = new Vimeo_Finder;
        $actual = $vimeo->findAll($vimeo->html($filename));
        $this->assertEqual(count($actual), 1);
        $this->assertEqual($actual[0]->type, 'vimeo');
        $this->assertEqual($actual[0]->width, 504);
        $this->assertEqual($actual[0]->height, 378);
        $this->assertEqual($actual[0]->uri, 'http://vimeo.com/moogaloop_local.swf?clip_id=4166841&server=vimeo.com&autoplay=0&fullscreen=1&show_portrait=0&show_title=0&show_byline=0&color=00ADEF&context=user:1242210&context_id=&hd_off=0&buildnum=25024');
    }
    
    function testFlickr()
    {
        $filename = $this->data("dcms-flickr_photo_page.html");
        $fallback = new Flickr_Finder;
        $html = $fallback->html($filename);
        
        $actual = $fallback->findAll($fallback->html($filename));

        $this->assertEqual(count($actual), 1);
        $this->assertEqual($actual[0]->type, 'flickr');
        $this->assertEqual($actual[0]->width, 500);
        $this->assertEqual($actual[0]->height, 375);
        $this->assertEqual($actual[0]->uri, 'http://farm4.static.flickr.com/3411/3534454049_1dc249c782.jpg?v=0');
        
        
    }
    
    function testUnknownSiteFinder()
    {
        $filename = $this->data("dcms-flickr_photo_page.html");
        $fallback = new Fallback_Finder;
        $actual = $fallback->findAll($fallback->html($filename));
        
        $this->assertEqual(count($actual), 45);
        $this->assertEqual($actual[0]->width, 500);
        $this->assertEqual($actual[0]->height, 375);
        $this->assertEqual($actual[0]->uri, 'http://farm4.static.flickr.com/3411/3534454049_1dc249c782.jpg?v=0',
                           "We should find the biggest image in the page first");
        
        $this->assertEqual($actual[1]->width, 75);
        $this->assertEqual($actual[1]->height, 75, "Then the next and so on");
    }
    
    private function data($file)
    {
        return "file://" . getcwd() . "/../data/$file";
    }
}
?>
