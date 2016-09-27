<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SocietyController extends Controller{
    
    /*------------------------------ APIS STARTS ------------------------------
    /*
    * API: /societies
    * Get the whole filtered list of societies with its information
    * @return Societies JSON
    */
    public function getSocieties(){

        //If it has cache, use cache instead
        // if(Cache::has('list'))
        //     return response()->json(Cache::get('list'));

        $urls = self::getUrls();
        
        $results = self::multi_thread_request($urls);

        $societies = [];

        foreach($results as $r){
            $json = (object) json_decode($r, true);
            
            //Ignore null objects
            if(is_null($json)) continue;

            //Default remove unwanted propeties
            $json = self::necessities($json);
                
            $societies[] = $json;
        }

        //Cache expires after 60 mins
        // Cache::put('list', $societies, 1);

        return response()->json($societies);

    }

    
    /*
    * API: /societies/raw
    * Get the whole raw list of societies with its information
    * @return Societies JSON
    */
    public function getSocietiesRaw(){

        //If it has cache, use cache instead
        if(Cache::has('rawlist'))
            return response()->json(Cache::get('rawlist'));

        $urls = self::getUrls();
        
        $results = self::multi_thread_request($urls);

        $societies = [];

        foreach($results as $r){
            $json = (object) json_decode($r, true);
            
            //Ignore null objects
            if(is_null($json)) continue;

            $societies[] = $json;
        }

        // Cache expires after 60 mins
        Cache::put('rawlist', $societies, 1);

        return response()->json($societies);
    }

    /*
    * API: /societies/{id}
    * Get a single society with its information
    * @return Societies JSON
    */
    public function getSociety($id){

        $json = file_get_contents(url('/') . '/api/v1/societies');
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

        $json = file_get_contents(url('/') . '/api/v1/' . $option);
        
        $societies = json_decode($json);

        foreach($societies as $society){
            
            foreach($society->days as $index => $day){
                $slot = new \stdClass();
                $slot->id = $society->id;
                $slot->name = $society->name;
                $slot->img = $society->img;
                // $slot->day = $day->day;

                $start = preg_split('/-/', $day->time)[0];
                $end = preg_split('/-/', $day->time)[1];

                $slot->start = $start;
                $slot->end = $end;

                // Starts  -- TEMPORARY FOR BELLY DANCING SOCIETY: WILL BE REMOVED UNTIL THEY CHANGE TO PROPER FORMAT
                if(preg_match("/[pm|am]/i", $slot->end)){
                    $slot->start = self::converttime($start);
                    $slot->end = self::converttime($end);
                }
                // End  -- TEMPORARY FOR BELLY DANCING SOCIETY: WILL BE REMOVED UNTIL THEY CHANGE TO PROPER FORMAT
                
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
            usort($day, [$this, "sortByStartTime"]);
            $timetable[$key] = $day;
        }

        return json_encode($timetable);

    }
    /*------------------------------ APIS ENDS ------------------------------*/

    private static function sortByStartTime($a, $b){
        $a = explode(':', $a->start)[0];
        $b = explode(':', $b->start)[0];
        return strcmp($a, $b);
    }
    

    /*------------------------------ METHODS ------------------------------*/
    /*
    * Return a list of all societies' dedicated json url
    * @return array
    */
    public function getUrls(){
        
        $domain = 'http://lusu.co.uk/groups/';
        $temp   = [];
        $pages  = 22;
    
        $requestlist = [];
        
        for($i = 0;$i<$pages;$i++){
            $offset = self::offset($i);
            $url = self::offsetUrl($offset);
            array_push($requestlist, $url);
        }
        
        //Results contains the whole list of societies' slug
        $results = self::multi_thread_request($requestlist, 'XMLHttpRequest');
        
        foreach($results as $slugs){
            
            foreach(self::extract($slugs) as $slug){
                
                //Remove any short name that contains special characters
                if(!self::isValid($slug)) continue;

                array_push($temp, $domain . $slug . '.json');
                
            }
        }
        return $temp;

    }

    /*
    * Return multiple page requests
    * @Param Nodes $nodes The number of nodes
    * @Param Method $method Whether to use XMLHttpRequest as header
    */
    public function multi_thread_request($nodes, $header=''){
        
        $mh = curl_multi_init(); 
        $curl_array = array();
        $res = array();
        $size = sizeof($nodes);
        
        for($i=0;$i<$size;$i++){
            $curl_array[$i] = curl_init($nodes[$i]); 
            curl_setopt($curl_array[$i],CURLOPT_FOLLOWLOCATION,true);
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_array[$i], CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($curl_array[$i], CURLOPT_TIMEOUT, 10);

            if($header == 'XMLHttpRequest')
                curl_setopt($curl_array[$i], CURLOPT_HTTPHEADER, array('x-requested-with: XMLHttpRequest'));

            curl_multi_add_handle($mh, $curl_array[$i]); 
        } 

        $active = NULL; 
        
        do { 
            // usleep(500); 
            curl_multi_exec($mh, $active); 
        } while($active > 0); 


        for($i=0;$i<$size;$i++)
            $res[$i] = curl_multi_getcontent($curl_array[$i]);
        
        for($i=0;$i<$size;$i++)
            curl_multi_remove_handle($mh, $curl_array[$i]); 
        
        curl_multi_close($mh);        
        return $res; 
    } 


    /*------------------------------ LIBRARIES ------------------------------*/
    public function necessities($json){
        unset($json->slug);
        unset($json->nextofkin_required);
        unset($json->over_eighteenid);
        unset($json->member_approval_requiredid);
        unset($json->member_visibility);
        unset($json->max_subscription_years);
        unset($json->image_content_type);
        unset($json->image_file_size);
        unset($json->group_type_id);
        unset($json->union_id);
        unset($json->asset_id);
        unset($json->created_at);
        unset($json->updated_at);
        unset($json->workflow_state);
        unset($json->over_eighteen);
        unset($json->member_approval_required);
        unset($json->display_name);
        unset($json->image_processing);
        unset($json->cms_draft);
        unset($json->articles_draft);
        unset($json->nominal_code);
        unset($json->display_as_subsite);
        unset($json->act_as_subsite);
        unset($json->page_id);
        unset($json->is_bespoke_subsite);
        unset($json->group_membership_expiry_date);
        unset($json->group_terms_and_conditions);
        unset($json->has_overridden_terms_and_conditions);
        unset($json->file_generated_at);

        $json->img = self::getSocietyLogo($json);
        unset($json->image_file_name);

        $json->email = $json->configure_group_email;
        unset($json->configure_group_email);

        $json->description = addslashes($json->description);
        $json->days = self::getDates($json->description);
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
    * DAY, HH:MM-HH:MM, LOCATION
    * Monday, 14:00-16:00, Georege Fox
    */
    public function getDay($string, $input_line){

        $day = new \stdClass();

        // First format: Monday 20:00-22:00, Playroom, Great Hall
        preg_match("/(".$string.")[,|:|\s]*(\d*[.|:]\d*-\d*[.|:]\d*),.([^-|<|(]*)/i", $input_line, $output);
        
        // Starts  -- TEMPORARY FOR BELLY DANCING SOCIETY: WILL BE REMOVED UNTIL THEY CHANGE TO PROPER FORMAT
        // Second format: Monday: 8-10 pm @ Great Hall A35
        preg_match("/(".$string.")[,|:|\s]*(\d*[^abc|0-9]\d*[.|\s][am|pm]*)[.|\s]*@[.|\s]([^-|]*)/i", $input_line, $format2);
        
        if($format2){
            // $day->day = $format2[1];
            $day->time = $format2[2];
            $day->location = $format2[3];
            return $day;
        }
        // Ends -- TEMPORARY FOR BELLY DANCING SOCIETY: WILL BE REMOVED UNTIL THEY CHANGE TO PROPER FORMAT

        if(empty($output)) return;

        
        // $day->day = $output[1];
        $day->time = $output[2];
        $day->location = preg_split("/</", $output[3])[0];

        return $day;
    }
    
    //TEMPORARY FOR BELLY DANCING SOCIETY: WILL BE REMOVED UNTIL THEY CHANGE TO PROPER FORMAT
    public function converttime($number){

        $number = intval($number);

        for($i = 0; $i<12; $i++){
        if($number == $i) return strval($number + 12) . ':00';	
        }
        
        if($number == 12) return strval(12) . ':00';
        
    }

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
    * Check if it contains special characters
    */
    public function isValid($str) {
        return !preg_match('/[^A-Za-z0-9.#\\-$]/', $str);
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