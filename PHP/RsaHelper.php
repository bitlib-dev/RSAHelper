生成签名示例代码：

<?php
class linkedme{
    //去除-----BEGIN RSA PRIVATE KEY-----和-----END RSA PRIVATE KEY-----的密钥字符串
    private $privateKey = '';
    //构建请求参数
    public function creatParam($data){
        ksort($data);
        $signStr = '';
        foreach ($data as $key=>$value){
            $signStr .= $key.'='.$value.'&';
        }
        $signStr = rtrim($signStr,'&');
        
        $sign = $this->getSign($signStr);
        $data['sign'] = $sign;
        return $data;
    }
    public function getSign($content){
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
    
        $key = openssl_get_privatekey($privateKey);
        openssl_sign($content, $signature, $key, "SHA256");
        openssl_free_key($key);
        $sign = $signature;
        return $this->String2Hex($sign);
    }
    private function String2Hex($string) {
        $hex = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $tmp = dechex(ord($string[$i]));
            if (strlen($tmp) == 1) {
                $tmp = "0" . $tmp;
            }
            $hex .= $tmp;
        }
        return strtoupper($hex);
    }
    public function post_json_data($url, $arr ,$header = [],$cookie = false) {
        $data_string=json_encode($arr);
        $ch = curl_init();
        $defaultHeader = [
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data_string)
        ];
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeader,$header));
        if($cookie){
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return $return_content;
    //        return array('code'=>$return_code, 'result'=>$return_content);
    }
}

//1.2校验用户登录号码接口
$current = '1.2';
$postPage = [
    '1.1'=>[
        'https://account.linkedme.cc/phone/info',[
            'app_key'=>'4ab94ebee606cb44319c2a9f4058c2d3',
            'channel'=>'2',
            'platform'=>'3',
            'token'=>'b29b8096e6844fb99206d560a490b04e'
        ]
    ],
    '1.2'=>[
        'https://account.linkedme.cc/phone/verify',[
            'app_key'=>'4ab94ebee606cb44319c2a9f4058c2d3',
            'channel'=>'2',
            'phone_num'=>'18613850732',
            'user_information'=>'',
            'platform'=>'3',
            'token'=>'caaea1b170d046cd92b63114647a271a'
        ]
    ]
    
];
$postUrl = $postPage[$current][0];
$param = $postPage[$current][1];
//
$linkedme = new linkedme;
$param = $linkedme->creatParam($param);
$res = $linkedme->post_json_data($postUrl,$param);
var_dump($res);exit;
?>



私钥解密示例代码：
/**
 * 私钥解密sign
 * @param $content
 * @param $private_key
 * @return string
 * @throws ErrorException
 */
public static function parseSign($content, $private_key)
{
    $private_key = openssl_pkey_get_private($private_key);

    if (!$private_key) {
        throw new ErrorException('私钥不可用');
    }

    $return_de = openssl_private_decrypt($content, $decrypted, $private_key);

    if (!$return_de) {
        throw new ErrorException('解密失败,请检查RSA秘钥');
    }

    return $decrypted;
}

/**
 * 十六进制转字符串
 * @param $hex
 * @return string
 */
public static function hexToStr($hex)
{
    $str = "";
    for ($i = 0; $i < strlen($hex) - 1; $i += 2)
        $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    return $str;
}

$mobile = CommonHelper::parseSign(CommonHelper::hexToStr($body), $private_key);
    
