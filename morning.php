<?php
define("memobird",1);
require_once('memobird.php');
date_default_timezone_set('Etc/GMT-8');
class helloMorning extends memobird {
    public $access = false;
    public $time = false;
    public $htmlCode;
    public $return;

    public function __construct()
    {
        self::rangeTime();
        if ($this->time == true && $_GET['action'] == "crontab") $this->access = true;
        $this->htmlCode = '<html><head></head><style>body{text-align:center}h2{font-size:2em;}h3{font-size:5em;}p{font-weight:bold;font-size:1.4em;}h4{font-size:1.4em;}</style><body><h2>你好早安</h2><p>'.date("Y").' Year</p><h4>'.date("l").' / '.date("F, d").'</h4><h3>'.date("d").'</h3><p>MEMOBIRD</p></body></html>';
    }

    public function rangeTime(){
        $str = date('Ymd ',time());
        $start = strtotime($str."06:55:00");
        $end = strtotime($str."07:05:00");
        $now = time();
        if($now >= $start && $now <= $end)
        {
            $this->time = true;
        }else{
            $this->time = false;
        }
    }

    public function printLog(){
        $return = $this->return;
        $file  = 'logMorning.txt';
        file_put_contents($file, date("Y-m-d H:i:s")."  [".$return["result"]."][".$return["showapi_res_error"]."][".$return["printcontentid"]."] \n",FILE_APPEND);
    }

    public function action(){
        require_once 'config.php';
        $memobird = new memobird();
        $memobird->ak = config::$ak; //access key
        $memobird->memobirdID = config::$memobirdID;//设备编码
        $memobird->str = config::$str;//用户标识
        $memobird->userid = $memobird->Register()['showapi_userid'];//注册绑定 User

        $this->return = $memobird->printHtml($this->htmlCode);
        $this->printLog();
    }
}

$helloMorning = new helloMorning();

if(!$helloMorning->access){
    exit('Access Violation');
}

$helloMorning->action();

