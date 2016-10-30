<?php
namespace App\Http\Libraries;

class Helper
{

    //Convert 12 hours to 24 hours (NOT STABLE)
    public function converttime($number){

        $number = intval($number);

        for($i = 0; $i<12; $i++)
            if($number == $i) return strval($number + 12) . ':00';	
        
        if($number == 12) return strval(12) . ':00';
        
    }

    public function unsets($json){
        // unset($json->slug);
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
        unset($json->image_file_name);
        unset($json->configure_group_email);
    }

    public function dictionary($input){
        $dict = [
            'George Fox',
            'Brandrigg Room',
            'Cartmel'
        ];

        foreach($dict as $key=>$place){
            if(preg_match("/".$place."/i", $input, $output_array)){
                return $place;
            };
        }
        return;
        // return $dict;
    }

    /*
    * Check if it contains special characters
    */
    public function isValid($str) {
        return !preg_match('/[^A-Za-z0-9.#\\-$]/', $str);
    }

    public function sortByStartTime($a, $b){
        $a = explode(':', $a->start)[0];
        $b = explode(':', $b->start)[0];
        return strcmp($a, $b);
    }
}