<?php

/**
 * Class memobird
 * author 权那他 (原作者：iecheng)
 * update 2020/04/13
 */
class memobird
{
    public static $_instance;
    public static $ak;
    public static $memobirdId;
    public static $character;
    public static $server;
    public static $url;
    public static $userId;
    public static $message;

    /**
     * @param $array
     */
    public static function setApi($array)
    {
        date_default_timezone_set($array['timezone']);
        self::$ak = $array['ak'];
        self::$server = $array['server'];
        self::$url = $array['url'];
        self::$memobirdId = $array['memobirdId'];
        self::$character = $array['character'];
    }

    /**
     * 设置单实例
     * @param memobird $memobird
     */
    public static function set(memobird $memobird)
    {
        self::$_instance = $memobird;
    }

    /**
     * 获取单实例
     * @return memobird
     */
    public static function get()
    {
        if (empty(self::$_instance)) {
            die("Exception: Single instance is not set");
        }
        return self::$_instance;
    }

    /**
     * @return mixed
     */
    private static function getUserId()
    {
        if (self::$userId == null) {
            $userId = self::curl(
                self::$url['getUserId'],
                http_build_query(
                    array(
                        'ak' => self::$ak,
                        'timestamp' => date('Y-m-d h:m:s', time()),
                        'memobirdID' => self::$memobirdId,
                        'useridentifying' => self::$character
                    )
                )
            )['showapi_userid'];
            self::$userId = $userId;
        }
        return self::$userId;
    }

    /**
     * @return mixed
     */
    public static function printPaper()
    {
        $msg = self::$message ?: "";
        // 清空
        self::$message = null;
        return self::curl(
            self::$url['printPaper'],
            http_build_query(
                array(
                    'ak' => self::$ak,
                    'timestamp' => date('Y-m-d h:m:s', time()),
                    'printcontent' => $msg,
                    'memobirdID' => self::$memobirdId,
                    'userID' => self::getUserId()
                )
            )
        );
    }

    /**
     * @param $url
     * @return mixed
     */
    public static function printUrl($url)
    {
        return self::curl(
            self::$url['printUrl'],
            http_build_query(
                array(
                    'ak' => self::$ak,
                    'timestamp' => date('Y-m-d h:m:s', time()),
                    'printUrl' => $url,
                    'memobirdID' => self::$memobirdId,
                    'userID' => self::getUserId()
                )
            )
        );
    }

    /**
     * @param $html
     * @return mixed
     */
    public static function printHtml($html)
    {
        return self::curl(
            self::$url['printHtml'],
            http_build_query(
                array(
                    'ak' => self::$ak,
                    'timestamp' => date('Y-m-d h:m:s', time()),
                    'printHtml' => base64_encode(self::charsetToGBK($html)),
                    'memobirdID' => self::$memobirdId,
                    'userID' => self::getUserId()
                )
            )
        );
    }

    /**
     * @param $printcontentID
     * @return mixed
     */
    public static function getPaperStatus($printcontentID)
    {
        return self::curl(
            self::$url['getPrintStatus'],
            http_build_query(
                array(
                    'ak' => self::$ak,
                    'timestamp' => date('Y-m-d h:m:s', time()),
                    'printcontentID' => $printcontentID
                )
            )
        );
    }

    /**
     * @param $content
     * @return mixed
     */
    public static function getPic($content)
    {
        return self::curl(
            self::$url['getPic'],
            http_build_query(
                array(
                    'ak' => self::$ak,
                    'imgBase64String' => $content
                )
            )
        );
    }

    /**
     * @param $msg
     * @return string
     */
    public static function setMsg($msg)
    {
        $c = 'T:' . base64_encode(self::charsetToGBK($msg) . "\n");
        if (!empty(self::$message)) {
            $c = '|' . $c;
        }
        self::$message .= $c;
        return self::$message;
    }

    /**
     * @param $url
     * @return mixed|string
     */
    public static function setImagesUrl($url)
    {
        $c = file_get_contents($url);
        $r = self::getPic(base64_encode($c));
        if ($r['showapi_res_code'] == 1) {
            $r = self::setMsg($r['result']);
        }
        return self::setMsgEncode($r);
    }

    /**
     * @param $img
     * @return string
     */
    public function setImages($img)
    {
        $c = 'P:' . base64_encode($img);
        if (!empty(self::$message)) {
            $c = '|' . $c;
        }
        self::$message .= $c;
        return self::$message;
    }

    /**
     * @param $content
     * @return string
     */
    public static function setMsgEncode($content)
    {
        if (empty(self::$message)) {
            $content = '|' . $content;
        }
        self::$message .= $content;
        return self::$message;
    }

    /**
     * 构造printPaper方法中$printcontent格式，多个可以循环并用|拼接
     * @param $type
     * @param $content
     * @return string
     */
    public static function getMsg($type, $content)
    {
        switch ($type) {
            case 'T':
                return $type . ':' . base64_encode(self::charsetToGBK($content) . "\n");
                break;
            case 'P':
                return 'P:' . base64_encode($content);
            default:
                return null;
        }
    }

    /**
     * 发起 server 请求
     * @param $action
     * @param $params
     * @return mixed
     */
    public static function curl($action, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$server . $action);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //处理http证书问题
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        if (false === $data) {
            $data = curl_errno($ch);
        }
        curl_close($ch);
        return json_decode($data, true);
    }

    /**
     * @param $mixed
     * @return array|false|string
     */
    public static function charsetToGBK($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $k => $v) {
                if (is_array($v)) {
                    $mixed[$k] = charsetToGBK($v);
                } else {
                    $encode = mb_detect_encoding($v, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
                    if ($encode == 'UTF-8') {
                        $mixed[$k] = iconv('UTF-8', 'GBK', $v);
                    }
                }
            }
        } else {
            $encode = mb_detect_encoding($mixed, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
            if ($encode == 'UTF-8') {
                $mixed = iconv('UTF-8', 'GBK', $mixed);
            }
        }
        return $mixed;
    }
}

// new 对象
$memobird = new memobird();
$memobird->setApi(
    array(
        //access key，这里改成自己的
        "ak" => "access key",
        //设备编码，这里改成自己的
        "memobirdId" => "memobird id",
        //用户标识,随便填
        "character" => "12121233",
        // 下面的基本不要动
        "timezone" => "Etc/GMT-8",
        "server" => "http://open.memobird.cn/home/",
        "url" => array(
            'getUserId' => 'setuserbind',
            'printPaper' => 'printpaper',
            'printUrl' => 'printpaperFromUrl',
            'printHtml' => 'printpaperFromHtml',
            'getPrintStatus' => 'getprintstatus',
            'getPic' => 'getSignalBase64Pic',
        )
    )
);
// set 单实例
memobird::set($memobird);

// 可以获取单实例
//$memobird = $memobird::get();

//这里添加消息内容  可以多次调用
$memobird->setMsg("权那他");
$memobird->setMsg("续写和改写的一个 memobird API打印类");
// 添加后，最后打印
$memobird->printPaper();

// 可查看返回内容
//var_dump($memobird->printPaper());
//$memobird->setMsg("再来一个");
//var_dump($memobird->printPaper());


//添加图片
//$memobird->setImages(file_get_contents('example.bmp'));
//打印
//$memobird->printPaper();

// 打印网页
//$memobird->printUrl('http://memobird.iecheng.cn/tmp/memobird/example.html');

// 打印网页
//$html = '<html><head></head><style>h2{font-size:2em;}</style><body><h2>欢迎使用</h2><p>Hello,MEMOBIRD!</p></body></html>';
//$res = $memobird->printHtml($html);

