<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MockController extends Controller{
    
    /*
    * API: /mock
    * Mock data 2009
    */
    public function getMock(){
    $json = [];
    $json[0] = '
    {
        "id":8160,
        "name":"Advertising",
        "img":"https://s3-eu-west-1.amazonaws.com/nusdigital/group/images/8160/medium/AD_LOGO_4.jpg",
        "email":"PLEASESUPPLYANEMAIL@EMAIL.COM",
        "description":"<p>The society is designed for students interested in the world of advertising, marketing and communications.</p> <p><font color=\"#626262\">We aim to be a valuable resource to our members and provide them with necessary industrial skills through hands on experience. We deliver diverse and innovative events and are motivated to succeed through creating new contacts and effective networking. Through our unique combination of events, we aim to increase awareness and prepare our members for careers in the industry.</font></p> <p>Find us on Facebook here - https://www.facebook.com/AdvertisingSociety/?fref=ts</p> <p>Or check our website - http://luadvertisingsociety.weebly.com/</p>",
        "days": {
            "1": {
                
                "time": "18:00-20:00",
                "location": "George Fox"
            },
            "3": {
                "time": "16:00-18:00",
                "location": "George Fox"
            },
            "5": {
                "time": "20:00-22:00",
                "location": "County Main"
            }
        }
    }';

    $json[1] = '
    {
        "id":8161,
        "name":"African Caribbea",
        "img":"https://s3-eu-west-1.amazonaws.com/nusdigital/group/images/8161/medium/13975277_294471200944508_3962735751063991780_o.jpg",
        "email":"lacs@lancaster.ac.uk",
        "description":"123",
        "days": {
            "1": {
                "time": "14:00-16:00",
                "location": "George Fox"
            },
            "2": {
                "time": "16:00-18:00",
                "location": "George Fox"
            },
            "3": {
                "time": "18:00-20:00",
                "location": "County Main"
            }
        }
    }';

    $json[2] = '
    {
        "id":8162,
        "name":"Aikido",
        "img":"https://s3-eu-west-1.amazonaws.com/nusdigital/group/images/8162/medium/aikido-at-lancaster-university.png",
        "email":"aikido@lancaster.ac.uk",
        "description":"123",
        "days": {
            "0": {
                "time": "12:00-14:00",
                "location": "George Fox"
            },
            "4": {
                "time": "20:00-22:00",
                "location": "George Fox"
            },
            "5": {
                "time": "16:00-18:00",
                "location": "County Main"
            }
        }
    }';

        $mock = [];
        $mock[] = json_decode($json[0], true);;
        $mock[] = json_decode($json[1], true);;
        $mock[] = json_decode($json[2], true);;
        return $mock;
    }

}