<?php
namespace App\Http\Libraries;

class Thread
{
    /*
    * Return multiple page requests
    * @Param Nodes $nodes The number of nodes
    * @Param Method $method Whether to use XMLHttpRequest as header
    */
    public function multiple($nodes, $header=''){
        
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
}