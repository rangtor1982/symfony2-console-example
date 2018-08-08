<?php
namespace System\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
use Ello\ArtistBundle\Entity\Artist;
use Ello\ArtistBundle\Entity\ArtistTranslation;
use Ello\VideoBundle\Entity\Video;
use Ello\VideoBundle\Entity\VideoTranslation;
use Ello\StationsBundle\Entity\Stations;
use Ello\StationsBundle\Entity\StationsTranslation;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use System\UploadBundle\Manager\UploadDirectory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
/**
 * Generate Charts
 * Class generateChartsCommand
 * @package Ello\StatisticBundle\Command
 */
class importContentCommand extends ContainerAwareCommand
{
    private $path = '/home/iradio/content/';
//    private $path = 'D:/tmp/content/';
    protected $replace_arr = [
        "." => "",
        "," => "",
        " " => "-",
        "'" => "",
        '"' => "",
        "`" => "",
        "." => "",
        "_" => "-",
        " "=> "-",
        "."=> "",
        "/"=> "-",
        ","=>"",
        "-"=>"-",
        "("=>"",
        ")"=>"",
        "["=>"",
        "]"=>"",
        "→"=>"",
        "="=>"-",
        "+"=>"-",
        "*"=>"",
        "?"=>"",
        "\""=>"",
        "'"=>"",
        "&"=>"",
        "%"=>"",
        "#"=>"",
        "@"=>"",
        "!"=>"",
        ";"=>"",
        "№"=>"",
        "^"=>"",
        ":"=>"",
        "~"=>"",
        "\""=>"",
        "\\"=>"",
        "«"=>"",
        "»"=>"",
        "—"=>"",
        "–"=>"",
        "ў"=>"y",
        "‘"=>"",
        "’"=>"",
        "’"=>""
    ];
    protected $translit = [
        "А"=>"a",
        "Б"=>"b",
        "В"=>"v",
        "Г"=>"g",
        "Д"=>"d",
        "Е"=>"e",
        "Ё"=>"e",
        "Ж"=>"j",
        "З"=>"z",
        "И"=>"i",
        "Й"=>"y",
        "К"=>"k",
        "Л"=>"l",
        "М"=>"m",
        "Н"=>"n",
        "О"=>"o",
        "П"=>"p",
        "Р"=>"r",
        "С"=>"s",
        "Т"=>"t",
        "У"=>"u",
        "Ф"=>"f",
        "Х"=>"h",
        "Ц"=>"ts",
        "Ч"=>"ch",
        "Ш"=>"sh",
        "Щ"=>"sch",
        "Ъ"=>"",
        "Ы"=>"i",
        "Ь"=>"j",
        "Э"=>"e",
        "Ю"=>"yu",
        "Я"=>"ya",
        "а"=>"a",
        "б"=>"b",
        "в"=>"v",
        "г"=>"g",
        "д"=>"d",
        "е"=>"e",
        "ё"=>"e",
        "ж"=>"j",
        "з"=>"z",
        "и"=>"i",
        "й"=>"y",
        "к"=>"k",
        "л"=>"l",
        "м"=>"m",
        "н"=>"n",
        "о"=>"o",
        "п"=>"p",
        "р"=>"r",
        "с"=>"s",
        "т"=>"t",
        "у"=>"u",
        "ф"=>"f",
        "х"=>"h",
        "ц"=>"ts",
        "ч"=>"ch",
        "ш"=>"sh",
        "щ"=>"sch",
        "ъ"=>"y",
        "ы"=>"i",
        "ь"=>"j",
        "э"=>"e",
        "ю"=>"yu",
        "я"=>"ya",
        "ї"=>"i",
        "Ї"=>"Yi",
        "є"=>"ie",
        "Є"=>"Ye",
        "і"=>"i",
        "І"=>"I",
    ];
    private $em;
    private $locales;
    
    public $log_line = 0;
    public $records_stat = [];


    protected function configure()
    {
        $this->setName('content:import')
            ->setDescription('Import content from disk')
            ->setHelp("Import content from disk");
//            ->addArgument('period', InputArgument::REQUIRED, 'Please, set period');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(0);
//        if(file_exists($this->getContainer()->getParameter('kernel.root_dir').'/logs/import.log'))unlink($this->getContainer()->getParameter('kernel.root_dir').'/logs/import.log');
//        $input_period = $input->getArgument('period');
        /** @var EntityManager $em */
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->locales = $this->getContainer()->getParameter('a2lix_translation_form.locales');
        if (substr(php_uname(), 0, 7) == "Windows"){ 
            $this->path = 'D:/tmp/content/';
        } 
        else { 
            $this->path = '/home/iradio/content/';
        }
        $this->records_stat = [
            'stations_created' => 0,
            'stations_updated' => 0,
            'artists_created' => 0,
            'artists_updated' => 0,
            'audios_created' => 0,
            'audios_updated' => 0,
            'videos_created' => 0,
            'videos_updated' => 0,
        ];
        $this->writeLog("Import start, getting directory listing...");
        $data_arr = $this->getListing();
        $this->writeLog("Directory listing done");
//        print_r($data_arr);
//        die();
        foreach ($data_arr as $data){
            $this->writeLog("Working with station {$data['slug']}");
            $station = $this->em->getRepository('ElloStationsBundle:Stations')->findOneByStationSlug($data['slug']);
            if($station) {
                $this->writeLog("Station found");
                foreach ($data['artists'] as $artists) {
                    $this->writeLog("Working with artist {$artists['slug']}");
                    $artist = $this->em->getRepository('ElloArtistBundle:Artist')->findOneBySlug($artists['slug']);
                    if(!$artist){
                        $this->writeLog("Artist {$artists['slug']} not found, creating...");
                        $artist = $this->createArtist([
                            'artist' => $artists,
                            'station' => $station
                        ]);
                        if(!$artist) continue;
                    } else {
                        $this->writeLog("Artist {$artists['slug']} exist");
                        if($artist->getStationId()->getId() !== $station->getId()){
                            $this->writeLog("Artist {$artists['slug']} exist in other station, checking next artist");
                            continue;
                        }
                    }
                    $this->writeLog("Generating audio for {$artists['slug']}");
                    $this->generateContent([
                        'content' => $artists['audios'],
                        'isAudio' => true,
                        'artist' => $artist,
                        'station' => $station
                    ]);
                    $this->writeLog("Generating video for {$artists['slug']}");
                    $this->generateContent([
                        'content' => $artists['videos'],
                        'isAudio' => false,
                        'artist' => $artist,
                        'station' => $station
                    ]);
                }
            } else {
                $this->writeLog("Station NOT found");
            }
        }
        $this->writeLog("Import complited");
    }
    
    protected function writeLog($text) {
        $file = $this->getContainer()->getParameter('kernel.root_dir').'/logs/import-'.date("d-m-Y").'.log';
        $text = $this->log_line++.' '.date("Y-m-d H:i:s").' - '.$text.PHP_EOL;
//        echo $text;
        file_put_contents($file, $text, FILE_APPEND);
    }
    
    private function clearText($text){
        return trim(strtr(strtolower(trim($text)), $this->replace_arr),'-');
    }
    
    private function translitText($text){
        return strtr(trim($text), $this->translit);
    }

    private function clearAndTranslit($text){
        return $this->clearText($this->translitText($text));
    }
    
    private function createArtist($data){
//        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $artist = new Artist();
        $tmp = explode('_', $data['artist']['dir_name']);
        $translations = new \Doctrine\Common\Collections\ArrayCollection();
        if(isset($tmp[1])){
            $name = substr($tmp[0], 0, 100);
            $last_name = substr($tmp[1], 0, 100);
        } else {
            $name = null;
            $last_name = isset($tmp[0]) ? substr($tmp[0], 0, 100) : null;
        }
        foreach ($this->locales as $locale) {
            $text = $locale == 'en' ? $this->translitText($last_name) : $last_name;
            $translationField = new ArtistTranslation();
            $translationField->setField('lastName');
            $translationField->setContent($text);
            $translationField->setLocale($locale);
            $translations->add($translationField);
            if($name){
                $text = $locale == 'en' ? $this->translitText($name) : $name;
                $translationField = new ArtistTranslation();
                $translationField->setField('name');
                $translationField->setContent($text);
                $translationField->setLocale($locale);
                $translations->add($translationField);
            }
        }
        $artist->setTranslations($translations);
        $artist->setSlug($data['artist']['slug']);
        $artist->setIsInteractive(false);
        $artist->setIsPublished(false);
        $artist->setStationId($data['station']);
        $artist->setLastName($this->translitText($last_name));
        $artist->setPubDate(new \DateTime());
        if($name) $artist->setName($this->translitText($name));
        try {
            $this->em->persist($artist);
            $this->em->flush();
            $this->writeLog("Artist {$data['artist']['slug']} created");
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->writeLog("Error on creating Artist {$data['artist']['slug']}");
            $this->writeLog("Doctrine Exception: ".$e->getMessage());
            return false;
        }
        return $artist;
    }
    
    private function generateContent($data){
//        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $method = $data['isAudio'] ? 'getAudioFile' : 'getOriginalVideoSrc';
        $type = $data['isAudio'] ? 'Audio' : 'Video';
        foreach ($data['content'] as $item){
            $this->writeLog("Working with $type {$item['slug']}");
            $content = $this->em->getRepository('ElloVideoBundle:Video')->findOneBySlug($item['slug']);
            if(!$content){
                $this->writeLog("$type {$item['slug']} not found, creating...");
                $content = $this->createContent([
                    'content' => $item,
                    'isAudio' => $data['isAudio'],
                    'artist' => $data['artist'],
                    'station' => $data['station']
                ]);
                if(!$content) continue;
            } elseif(!$content->{$method}()) {
                $this->writeLog("Item {$item['slug']} found without $type, adding...");
                $this->addContent([
                    'content' => $content,
                    'content_info' => [
                        'file' => $item['path'],
                        'mime' => $item['mime_type'],
                        'file_name' => $item['file_name']
                    ],
                    'isAudio' => $data['isAudio']
                ]);
            } else {
                $this->writeLog("$type {$item['slug']} exist, working with next $type");
            }
        }
        return false;
    }
    
    private function createContent($data){
        $type = $data['isAudio'] ? 'Audio' : 'Video';
        $content = new Video();
        $content->setTitle($this->translitText($data['content']['title']));
        $content->setSlug($data['content']['slug']);
        $translations = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($this->locales as $locale) {
            $text = $locale == 'en' ? $this->translitText($data['content']['title']) : $data['content']['title'];
            $translationField = new VideoTranslation();
            $translationField->setField('title');
            $translationField->setContent($text);
            $translationField->setLocale($locale);
            $translations->add($translationField);
        }
        $content->setTranslations($translations);
        $content->setStationId($data['station']);
        $content->addArtist($data['artist']);
        try {
            $this->em->persist($content);
            $this->em->flush();
            $this->writeLog("$type {$data['content']['title']} created, adding file streams...");
            $this->addContent([
                'content' => $content,
                'content_info' => [
                    'file' => $data['content']['path'],
                    'mime' => $data['content']['mime_type'],
                    'file_name' => $data['content']['file_name']
                ],
                'isAudio' => $data['isAudio']
            ]);
            $this->writeLog("$type {$data['content']['title']} file streams generation started in background");
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->writeLog("Error on creating $type {$data['content']['title']}");
            $this->writeLog("Doctrine Exception: ".$e->getMessage());
            return false;
        }
        return $content;
    }
    
    private function addContent($data){
        if(file_exists($data['content_info']['file'])){
            if($data['isAudio']){
                $path = $this->getContainer()->getParameter('kernel.root_dir').'/../www/uploads/audio_files/'.$data['content']->getId().'/';
                if(!is_dir($path)){
                    mkdir($path, 0777, true);
                }
                $file_extension = 'mp3';
                if(strpos($data['content_info']['file_name'],'mpeg') !== false) $file_extension = 'mp3';
                if(strpos($data['content_info']['file_name'],'wav') !== false) $file_extension = 'wav';
                if(strpos($data['content_info']['file_name'],'aac') !== false) $file_extension = 'aac';
                if(strpos($data['content_info']['file_name'],'m4a') !== false) $file_extension = 'm4a';                             
                $file_name = uniqid('audio');
                $data['content']->setAudioFile($file_name.'.mp3');
                $new_file = $path.$data['content']->getAudioFile();
//                $file = new UploadedFile($data['content_info']['file'], $data['content_info']['file_name'],$data['content_info']['mime']);
                exec('rm -rf '.$path.'/*');
                copy($data['content_info']['file'],$path.$file_name.'.'.$file_extension);
//                $file->move($path,$file_name.'.'.$file_extension);
                if(in_array($file_extension,['wav','aac','m4a']) !== false){
//                    exec('ffmpeg -i '.$path.$file_name.'.'.$file_extension.' -vn -ar 44100 -ac 2 -ab 192k -f mp3 '.$new_file);
//                    exec('ffmpeg -i '.$new_file.' -c:a aac -strict -2 -b:a 192k -vn -hls_list_size 0 '.$new_file.'.m3u8');
                    exec('ffmpeg -i '.$path.$file_name.'.'.$file_extension.' -vn -ar 44100 -ac 2 -ab 192k -f mp3 '.$new_file.' > /dev/null 2>/dev/null ');
                    exec('ffmpeg -i '.$new_file.' -c:a aac -strict -2 -b:a 192k -vn -hls_list_size 0 '.$new_file.'.m3u8 > /dev/null 2>/dev/null &');
                    unlink($path.$file_name.'.'.$file_extension);
                }
                elseif($file_extension == 'mp3'){
//                    exec('ffmpeg -i '.$new_file.' -c:a aac -strict -2 -b:a 192k -vn -hls_list_size 0 '.$new_file.'.m3u8');
                    exec('ffmpeg -i '.$new_file.' -c:a aac -strict -2 -b:a 192k -vn -hls_list_size 0 '.$new_file.'.m3u8 > /dev/null 2>/dev/null &');
                }
                
                $this->em->persist($data['content']);
                $this->em->flush();
            } else {
//                $config = $this->getContainer()->getParameter('ello_video');
//                $presets = $config['amazon']['presets'];
                $directory = $this->getContainer()->getParameter('kernel.root_dir').'/../www/uploads/videos/'.$data['content']->getId().'/';
                if(!is_dir($directory)){
                    mkdir($directory, 0777, true);
                }

		preg_match('/\.\w+$/i', $data['content_info']['file_name'], $math);
		// Create new name file
		$file_name = time() . $math[0];
                $data['content']->setOriginalVideoSrc($file_name);
                $this->em->persist($data['content']);
                $this->em->flush();
                exec('rm -rf '.$directory.'/*');
                copy($data['content_info']['file'],$directory.$file_name);
                exec('php '.$this->getContainer()->getParameter('kernel.root_dir').'/console video:upload:cron '.$data['content']->getId().' > /dev/null 2>/dev/null &');
            }
        }
        return false;
    }
    
    private function createStation($data){
        return false;
    }

    private function getListing(){
        $stations_struct = scandir($this->path);
        $stations = [];
        $s = 0;
        foreach($stations_struct as $station){
            if(in_array($station,['.','..']) === false && is_dir($this->path.$station)) {
                $artists_struct = scandir($this->path.$station);
                $station_slug = str_replace(['---','--'],"-", $this->clearAndTranslit($station));
                $artist_arr = [];
                $art = 0;
//                $this->writeLog("Station: $station");
                foreach($artists_struct as $artist){
                    $audio_arr = $video_arr = [];
                    $au = $v = 0;
//                    $this->writeLog("check artist: $artist");
                    if(in_array($artist,['.','..', 'Name_Lastname']) === false && is_dir($this->path.$station.'/'.$artist)) {
                        $audio_path = false;
                        if(file_exists($this->path.$station.'/'.$artist.'/Audio')) $audio_path = $this->path.$station.'/'.$artist.'/Audio';
                        if(file_exists($this->path.$station.'/'.$artist.'/audio')) $audio_path = $this->path.$station.'/'.$artist.'/audio';
                        if($audio_path) {
                            $content_struct = scandir($audio_path);
                            foreach($content_struct as $content){
                                $cur_file = $audio_path.'/'.$content;
                                if(in_array($artist,['.','..']) === false && is_file($cur_file)) {
                                    $title = substr(basename($cur_file), 0, -4);
                                    $slug = str_replace(['---','--'],"-",$this->clearAndTranslit($title));
                                    $audio_arr[$au++] = [
                                        'file_name' => $content,
                                        'mime_type' => mime_content_type($cur_file),
                                        'title' => substr($title, 0, 100),
                                        'slug' => substr($slug, 0, 255),
                                        'path' => $cur_file
                                    ];
                                }
                            }
                        }
                        $video_path = false;
                        if(file_exists($this->path.$station.'/'.$artist.'/Video')) $video_path = $this->path.$station.'/'.$artist.'/Video';
                        if(file_exists($this->path.$station.'/'.$artist.'/video')) $video_path = $this->path.$station.'/'.$artist.'/video';
                        if($video_path) {
                            $content_struct = scandir($video_path);
                            foreach($content_struct as $content){
                                $cur_file = $video_path.'/'.$content;
                                if(in_array($artist,['.','..']) === false && is_file($cur_file)) {
                                    $title = substr(basename($cur_file), 0, -4);
                                    $slug = str_replace(['---','--'],"-",$this->clearAndTranslit($title));
                                    $video_arr[$v++] = [
                                        'file_name' => $content,
                                        'mime_type' => mime_content_type($cur_file),
                                        'title' => substr($title, 0, 100),
                                        'slug' => substr($slug, 0, 255),
                                        'path' => $cur_file
                                    ];
                                }
                            }
                        }
                        $artist_arr[$art++] = [
                            'dir_name' => $artist,
                            'slug' => substr(str_replace(['---','--'],"-",$this->clearAndTranslit($artist)), 0, 100),
                            'audios' => $audio_arr,
                            'videos' => $video_arr
                        ];
                    }
                }
                $stations[$s]['dir'] = $this->path.$station;
                $stations[$s]['slug'] = $station_slug;
                $stations[$s++]['artists'] = $artist_arr;
            }
        }
        return $stations;
    }
}