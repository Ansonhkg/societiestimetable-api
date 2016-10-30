<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

use App\Http\Controllers\Controller;

use App\Http\Libraries\Helper;
use App\Http\Libraries\Thread;

use Illuminate\Http\Request;

class SocietyController extends Controller{
    
    private $helper;
    private $thread;
    private $url;
    private $cache_on;
    
    public function __construct()
    {
        $this->helper = new Helper();
        $this->thread = new Thread();

        //Local
        // $this->url = url('/') . '/api/v1/';
        $this->cache_on = false;

        //Production
        $this->url = url('/') . '/';
        // $this->cache_on = true;
    }

    /*------------------------------ APIS STARTS ------------------------------
    /*
    * API: /societies
    * Get the whole filtered list of societies with its information
    * @return Societies JSON
    */
    public function getSocieties(){

        //If it has cache, use cache instead
        if(Cache::has('list') && $this->cache_on)
            return response()->json(Cache::get('list'));

        $urls = self::getUrls();
        
        $results = $this->thread->multiple($urls);

        $societies = [];

        foreach($results as $r){
            $json = (object) json_decode($r, true);
            
            //Ignore null objects
            if(is_null($json)) continue;

            //Default remove unwanted propeties
            $json = self::getNecessities($json);
                
            $societies[] = $json;
        }

        //Cache expires after 60 mins
        if($this->cache_on){
            Cache::put('list', $societies, 1);
        }

        return response()->json($societies);

    }

    
    /*
    * API: /societies/raw
    * Get the whole raw list of societies with its information
    * @return Societies JSON
    */
    // public function getSocietiesRaw(){

    //     //If it has cache, use cache instead
    //     if(Cache::has('rawlist') && $this->cache_on)
    //         return response()->json(Cache::get('rawlist'));

    //     $urls = self::getUrls();
        
    //     $results = $this->thread->multiple($urls);

    //     $societies = [];

    //     foreach($results as $r){
    //         $json = (object) json_decode($r, true);
            
    //         //Ignore null objects
    //         if(is_null($json)) continue;

    //         $societies[] = $json;
    //     }

    //     // Cache expires after 60 mins
    //     if($this->cache_on){
    //         Cache::put('rawlist', $societies, 1);
    //     }

    //     return response()->json($societies);
    // }

    /*
    * API: /societies/{id}
    * Get a single society with its information
    * @return Societies JSON
    */
    public function getSociety($id){

        $json = file_get_contents($this->url . 'societies');
        $json = json_decode($json);
        
        //Putting the object in the array avoid escaping special characeters and quotes
        $society = [];

        foreach($json as $s){
            if($s->id == $id){
                $society[] = $s;
                unset($s->days);
                $s->description = str_replace("&nbsp;", " ", $s->description);
                return $society;
            }
        }
        
        return 'Invalid society id';
    }

    /*
    * API: /urls
    * Get the list of societies .json url
    */
    public function getUrlsJSON(){
                
        return response()->json(self::getUrls());
    
    }

    /*
    * API: /timetable
    */
    public function timetable($option='societies'){

        $timetable = [
            'Monday'   =>[],
            'Tuesday'  =>[],
            'Wednesday'=>[],
            'Thursday' =>[],
            'Friday'   =>[],
            'Saturday' =>[],
            'Sunday'   =>[]
        ];

        $days = [
            0 => 'Monday',
            1 => 'Tuesday',
            2 => 'Wednesday',
            3 => 'Thursday',
            4 => 'Friday',
            5 => 'Saturday',
            6 => 'Sunday'
        ];

        $json = file_get_contents($this->url . $option);
        
        $societies = json_decode($json);

        foreach($societies as $society){
            
            foreach($society->days as $index => $day){
                $slot = new \stdClass();
                $slot->id = $society->id;
                $slot->url = 'http://www.lusu.co.uk/groups/' . $society->slug;
                $slot->name = $society->name;
                $slot->img = $society->img;
                $slot->start = $day->start;
                $slot->end = $day->end;
                $slot->location = $day->location;

                //Put slots into days
                for($i=0;$i<sizeof($days);$i++){
                    if($index == $i){
                        array_push($timetable[$days[$i]], $slot);
                    }
                }
            }
        }
        
        //Sort by start day in ascd order
        foreach($timetable as $key=>$day){
            usort($day, [$this->helper, "sortByStartTime"]);
            $timetable[$key] = $day;
        }

        return json_encode($timetable);

    }
    /*------------------------------ APIS ENDS ------------------------------*/
    

    /*------------------------------ METHODS ------------------------------*/
    /*
    * Return a list of all societies' dedicated json url
    * @return array
    */
    public function getUrls(){
        
        $domain = 'http://lusu.co.uk/groups/';
        $temp   = [];
        $pages  = 31;
    
        $requestlist = [];
        
        for($i = 0;$i<$pages;$i++){
            $offset = self::offset($i);
            $url = self::offsetUrl($offset);
            array_push($requestlist, $url);
        }
        
        //Results contains the whole list of societies' slug
        $results = $this->thread->multiple($requestlist, 'XMLHttpRequest');
        
        foreach($results as $slugs){
            
            foreach(self::extract($slugs) as $slug){
                
                //Remove any short name that contains special characters
                if(!$this->helper->isValid($slug)) continue;

                array_push($temp, $domain . $slug . '.json');
                
            }
        }
        return $temp;

    }

    /*------------------------------ LIBRARIES ------------------------------*/
    public function getNecessities($json){

        $json->img = self::getSocietyLogo($json);

        $json->email = $json->configure_group_email;

        $json->description = addslashes($json->description);
        $json->days = self::getDates($json->description);

        $this->helper->unsets($json);

        return $json;
    }

    /*
    * @return (object) week
    * Place all daily object into a weekly array
    */
    public function getDates($input_line){
        
        $input_line = str_replace("&nbsp;", " ", $input_line);

        $week = [];

        array_push($week, self::getDay("Monday",    $input_line));
        array_push($week, self::getDay("Tuesday",   $input_line));
        array_push($week, self::getDay("Wednesday", $input_line));
        array_push($week, self::getDay("Thursday",  $input_line));
        array_push($week, self::getDay("Friday",    $input_line));
        array_push($week, self::getDay("Saturday",  $input_line));
        array_push($week, self::getDay("Sunday",    $input_line));

        //Remove any null values
        $week = array_filter($week, function($var){return !is_null($var);});
        
        return $week;
    }

    /*
    * @return (object) day
    * Extract Times and Location from string with the following format
    */
    public function getDay($string, $input_line){
        
        // Monday, 14:00-16:00, Georege Fox
        if($format_default = self::format_default($string, $input_line)) return $format_default;

        // Monday: 8-10 pm @ Great Hall A35
        if($format_belly = self::format_belly($string, $input_line)) return $format_belly;

        // Wednesdays 18:00-20:00
        if($format_aikido = self::format_aikido($string, $input_line)) return $format_aikido;

        // Tuesday 8-10pm
        // if($format_folk = self::format_folk($string, $input_line)) return $format_folk;

        return;
    }
    
    //--------- Formats ----------//
    /*
    * Default format
    * DAY, HH:MM-HH:MM, LOCATION
    * Monday, 14:00-16:00, Georege Fox
    */
    public function format_default($string, $input_line){

        preg_match("/(".$string.")[,|:|\s]*(\d*[.|:]\d*-\d*[.|:]\d*),.([^-|<|(]*)/i", $input_line, $output);
        
        if(empty($output)) return;

        $day = new \stdClass();
        $day->location = preg_split("/</", $output[3])[0];

        $day->start = preg_split('/-/', $output[2])[0];
        $day->end = preg_split('/-/', $output[2])[1];

        return $day;
    }

    /*
    * Format 2: For belly dancing society only. will be removed when they change their format to default
    * Monday: 8-10 pm @ Great Hall A35
    */
    public function format_belly($string, $input_line){

        preg_match("/(".$string.")[,|:|\s]*(\d*[^abc|0-9]\d*[.|\s][am|pm]*)[.|\s]*@[.|\s]([^-|]*)/i", $input_line, $output);
        
        if(empty($output)) return;

        $day = new \stdClass();
        $day->location = $output[3];

        $day->start = preg_split('/-/', $output[2])[0];
        $day->end = preg_split('/-/', $output[2])[1];
        
        if(preg_match("/[pm|am]/i", $day->end)){
            $day->start = $this->helper->converttime($day->start);
            $day->end = $this->helper->converttime($day->end);
        }

        return $day;
    }

    /*
    * Format 3: For Aikido society only. Will be removed when they change their format to defaukt.
    * We currently train in the Brandrigg Room of Cartmel College:
    *   Wednesdays 18:00-20:00
    *   Saturdays 10:00-12:00
    */
    public function format_aikido($string, $input_line){

        preg_match("/(".$string."[s?])[,|:|\s]*(\d*[.|:]\d*-\d*[.|:]\d*)[^-|<|(]*/i", $input_line, $output);

        if(empty($output)) return;

        $day = new \stdClass();
        $day->location = $this->helper->dictionary($input_line);
        $day->start = preg_split('/-/', $output[2])[0];
        $day->end = preg_split('/-/', $output[2])[1];

        return $day;
    
    }

    // Tuesday 8-10pm
    // public function format_folk($string, $input_line){

    //     $input_line = strip_tags($input_line);

    //     preg_match("/(".$string.")[s?][^\d]*(\d*-\d*[am|pm]*)/i", $input_line, $output);

    //     if(empty($output[2])) return;
    //     $day = new \stdClass();
    //     $day->location = 'Click to View More';
        
    //     $day->start = preg_split('/-/', $output[2])[0];
    //     $day->end = preg_split('/-/', $output[2])[1];

    //     if(preg_match("/[pm|am]/i", $day->end)){
    //         $day->start = $this->helper->converttime($day->start);
    //         $day->end = $this->helper->converttime($day->end);
    //     }

    //     return $day;

    // }


    /*
    * @return offsfet number
    * It's 8 because it only has 8 socities list on a page before clicking on 'Load More'
    * See: http://lusu.co.uk/groups
    */
    public function offset($i){
        return $i * 8;
    }

    /*
    * @return group offset url
    */
    public function offsetUrl($offset){
        return 'http://lusu.co.uk/groups/more_groups?offset='.$offset.'&group_type=1';
    }

    /*
    * Extract information (society short name) from the page
    */
    public function extract($string){
        $string = htmlentities($string);
        $string = stripslashes($string);
        $string = str_replace(';', '', $string);
        preg_match_all("/(?<=groups\/).*?(?=&quot&)/", $string, $results);
        return $results[0];
    }

    /*
    * Return as image link
    */
    public function getSocietyLogo($societyObj){
        return $imgsrc = "https://s3-eu-west-1.amazonaws.com/nusdigital/group/images/{$societyObj->id}/medium/{$societyObj->image_file_name}";
    }
}