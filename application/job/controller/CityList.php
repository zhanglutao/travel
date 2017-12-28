<?php
namespace app\job\controller;

use think\Controller;
use think\Db;
use think\Debug;
use think\Request;
use think\config;

class CityList extends Controller
{
    public function index()
    {
        echo 'spider job';
    }

    public function get_city_list(){
        $cityName = Request::instance()->get('cityName');
        $cityRelation = Request::instance()->get('cityRelation');
        $cityListData = array(
            'requestor' => 'pc',
            'cityName' => $cityName,
            'platform' => 'adr',
            'cityRelation' => $cityRelation,
        );
        $cityListUrl = config::get('url.city_list_qunar');
        $getCurlResult = vpost($cityListUrl, http_build_query($cityListData));
        echo $getCurlResult;exit;
//        $cityList = json_decode($getCurlResult, true);
        $cityList = $getCurlResult;
        if (!$cityList) {
            echo $cityList;
        }else{
            return null;
        }
    }
}
