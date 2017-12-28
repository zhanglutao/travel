<?php
/**
 * Created by PhpStorm.
 * User: zuopeng
 * Date: 2017/9/15
 * Time: 下午12:39
 */

namespace app\common\controller;

use think\Controller;
use think\Db;
use think\Debug;
use think\Request;

class init extends Controller {

    protected $req_type;
    protected $token = '';
    protected $param;
    protected $user_id = 0;
    protected $log_str = '';
    protected $sql_log = array();
    protected $device_code = '';

    protected $module_name = '';
    protected $controller_name = '';
    protected $action_name = '';

    public function __construct(){
        parent::__construct();
        $request = Request::instance();
        $this->module_name =  $request->module();
        $this->controller_name = $request->controller();
        $this->action_name = $request->action();

        $this->start();
    }


    private function start(){
        Debug::remark('begin');
        Db::listen(function($sql,$time,$explain){
            // 记录SQL
            $str = $sql. ' ['.$time.'s]';
            log4php::init('SqlLog')->info($str);
            $this->sql_log[] =  $str;
            // 查看性能分析结果
        });

        $data = file_get_contents("php://input");
        log4php::init($this->module_name)->info('接口请求原始数据:'.$data);
        parse_str($data,$this->param);
        if(!$this->param){
            $this->param = array_merge((array)input('post.'),(array)input('get.'));
        }
        $this->req_type = strtolower($this->controller_name.'/'.$this->action_name);

        $reqtype_data = config("reqtype_list");
        if(!isset($reqtype_data[$this->req_type])){
            $this->erro('REQTYPE_NOT_DEFINITION');
        }else{
            $reqtype_data = $reqtype_data[$this->req_type];
        }
        $this->log_str = "{$this->req_type}[{$reqtype_data['name']}]";
        if(!isset($this->param['device_code'])){
            $this->erro("DEVICE_CODE_IS_NULL");
        }
        $this->device_code = $this->param['device_code'];
        if(empty($this->device_code)){$this->erro("DEVICE_CODE_IS_NULL");};

        Log4php::init($this->module_name)->info("{$this->log_str}开始");
        Log4php::init($this->module_name)->info($this->log_str.'接口请求原始数据:'.$data);
        Log4php::init($this->module_name)->info($this->log_str.'接口请求JSON数据:'.json_encode($this->param));
        Log4php::init($this->module_name)->info($this->log_str.'请求头：'.json_encode(getallheaders()));
        Log4php::init($this->module_name)->info($this->log_str.'文件：'.json_encode($_FILES));
    }


    /**
     * 成功
     * @param unknown $msg
     * @param unknown $data
     */
    protected function succ($msg='ok',$data=array()){
        $array = array(
            'token'=>(string)$this->token,
            'code'=>'0',
            'msg'=>(string)$msg,
            'data'=>array(
                'request'=>(object)$this->param,
                'response'=>(object)$data,
            ),
        );
        $this->outPut($array);
    }

    /**
     * 失败
     * @param unknown $msg
     * @param unknown $data
     */
    protected function erro($code,$data=array(),$msg=''){
        $sys_return_code = (array)config('SYS_RETURN_CODE');
        $return_code = (array)config('RETURN_CODE');
        $return_code = array_merge($sys_return_code,$return_code);
        if(isset($return_code[$code]['code'])){
            $return_data = $return_code[$code];
        }else{
            if(!empty($msg)){
                $return_data['code'] = $code;
            }else{
                $this->erro('CODE_IS_NULL');
            }
        }
        if(isset($return_data['msg'])){
            if(empty($msg)){$msg = (string)$return_data['msg'];}
        }

        $array = array(
            'token'=>(string)$this->token,
            'code'=>(string)$return_data['code'],
            'msg'=>(string)$msg,
            'data'=>array(
                'request'=>(object)$this->param,
                'response'=>(object)$data,
            ),
        );
        $this->outPut($array);
    }

    /**
     * 公共返回
     */
    protected function outPut($array){
        Debug::remark('end');
        if(config('app_debug')){
            unset($this->param['file']);
            $array['trace'] = array(
                'api_url'=>"http://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}",//请求地址
                'run_time'=>Debug::getRangeTime('begin','end',6).'s',
                'now_time'=>date('Y-m-d H:i:s',Request::instance()->time()),
                'run_mem'=>Debug::getRangeMem('begin','end').'kb',
                'destination_ip'=>gethostbyname($_SERVER['SERVER_NAME']),//目的IP
                'php_version'=>phpversion(),
                'upload_max_file_size'=>ini_get('upload_max_filesize'),
                'post_max_size'=>ini_get('post_max_size'),
                'source_ip'=>Request::instance()->ip(),
                'request_data'=>$this->param,
                'sql'=>$this->sql_log,
            );
        }
        $str = json_encode($array);
        Log4php::init($this->module_name)->info($this->log_str.'接口返回:'.$str.' - '.$array['msg']);
        Log4php::init($this->module_name)->info($this->log_str.'结束');
        $str = json_encode($array);
        header('Content-Type:application/json; charset=utf-8');
        echo $str;die();
    }

    /**
     * 获取参数
     * @param $key
     * @param null $default
     */
    protected function getParam($key,$default=null,$rule=[],$msg=''){
        try{
            if(!isset($this->param[$key]) && $default===null){
                $this->erro('PARAM_KEY_IS_UNDEFINED',array(),'参数没有定义:'.$key);
            }elseif(!isset($this->param[$key]) && $default!==null){
                $data = $default;
            }elseif(isset($this->param[$key]) && $default===null){
                $data = $this->param[$key];
            }elseif(isset($this->param[$key]) && $default!==null){
                if(empty($this->param[$key])){
                    $data = $default;
                }else{
                    $data = $this->param[$key];
                }
            }
            if(is_string($data)){
                $data = trim($data);
            }
            $code = $sys_msg = '';
            if(is_array($rule) && !empty($rule)){
                if(!in_array($data,$rule)){
                    $code = 'PARAM_KEY_NOT_IN_ARRAY';
                    $sys_msg = $key.'参数传值范围错误,传值范围:'.join(',',$rule);
                }
            }elseif(is_string($rule) && !empty($rule)){
                if(!preg_match($rule,$data)){
                    $code = 'PARAM_KEY_NO_RULE';
                    $sys_msg = $key.'参数校验失败';
                }
            }else{
                if($default===null && empty($data)){
                    $code = 'PARAM_KEY_IS_NULL';
                    $sys_msg = '参数不能为空:'.$key;
                }
            }
            if(!empty($code)){
                if(empty($msg)){
                    $msg = $sys_msg;
                }
                return $this->erro($code,[],$msg);
            }
            return $data;
        }catch (\Exception $e){
            return $this->erro('PARAM_GET_ERROR',[],"{$key}获取失败!原因：[{$e->getFile()}-{$e->getLine()}行]{$e->getMessage()}");
        }
    }

}