<?php
namespace Gateway\Pay\Token188;
use Gateway\Pay\ApiInterface;
use Illuminate\Support\Facades\Log;
class Api implements ApiInterface
{
    //异步通知页面需要隐藏防止CC之类的验证导致返回失败
    private $url_notify = '';
    private $url_return = '';

    public function __construct($id)
    {
		
        $this->url_notify = SYS_URL_API . '/pay/notify/' . $id;
        
        $this->url_return = SYS_URL . '/pay/return/' . $id;
    }

    /**
     * @param array $config 支付渠道配置
     * @param string $out_trade_no 本系统的订单号
     * @param string $subject 商品名称
     * @param string $body 商品介绍
     * @param int $amount_cent 金额/分
     * @throws \Exception
     */
    function goPay($config, $out_trade_no, $subject, $body, $amount_cent)
    {
        
        if (!isset($config['app_id']) || !isset($config['api_secret'])) {
            throw new \Exception('请检查[app_id,api_secret] (应用ID,后台应用密钥)是否填写');
        }

        $this->url_return .= '/' . $out_trade_no;
        $params = [
            'merchantId' => $config['app_id'],
            'outTradeNo' => $out_trade_no,
            'subject' => $subject,
            'totalAmount' => sprintf('%.2f', $amount_cent / 100),
            'attach' => $amount_cent,
            'body' => $body,
            'coinName' => 'USDT-TRC20',
            'notifyUrl' => $this->url_notify,
            'timestamp' => $this->msectime(),
            'nonceStr' => $this->getNonceStr(16)
        ];
        //echo $params['totalAmount'];
        $mysign = self::GetSign($config['api_secret'], $params);
        // 网关连接
        $ret_raw = self::_curlPost('https://api.token188.com/utg/pay/address', $params,$mysign,1);
        
		
        $ret = @json_decode($ret_raw, true);
        //print_r($ret);
        //die();
        /*
        if (!$ret || !isset($ret['code']) || $ret['code'] == 400) {
            Log::error('Pay.MugglePay.goPay.order, request failed', ['response' => $ret_raw]);
            throw new \Exception($ret['msg']);
        }*/
        //print_r($ret);
        \App\Order::whereOrderNo($out_trade_no)->update(['pay_trade_no' => $ret['data']['orderNo']]);
        //页面展示出来
        //header("Location: /token188/index.php?orderNo=".$ret['data']['orderNo']."&out_trade_no=".$out_trade_no."&address=".$ret['data']['address']."&totalAmount=".$params['totalAmount']."&amount=".$ret['data']['amount']."&coinName=".$params['coinName']);
        header("Location: ".$ret['data']['paymentUrl']);
        exit;
    }
    

    /**
     * @param $config
     * @param callable $successCallback
     * @return bool|string
     * @throws \Exception
     */
    function verify($config, $successCallback)
    {
        $isNotify = isset($config['isNotify']) && $config['isNotify'];
        if ($isNotify) {
            $content = file_get_contents('php://input');
            //$content = file_get_contents('php://input', 'r');
            
            $json_param = json_decode($content, true); //convert JSON into array
            
            Log::error($_POST);
            $coinPay_sign = $json_param['sign'];
			unset($json_param['sign']);
			unset($json_param['notifyId']);
            $sign = self::GetSign($config['api_secret'], $json_param);
			
            // check sign
            if ($sign !== $coinPay_sign) {
                Log::error('Pay.CoinPay.verify, sign invalid', ['$json_param' => $json_param]);
                echo json_encode(['status' => 400]);
                return false;
            }
            $json_param['sign'] = $sign;

            // check request format
            if ($json_param['merchantId']!=$config['app_id']) {
                Log::error('Pay.CoinPay.verify, sign invalid', ['$json_param' => $json_param]);
                echo json_encode(['status' => 401]);
                return false;
            }

           
            $out_trade_no = $json_param['outTradeNo'];
            // check payment status
            if ($json_param['tradeStatus'] === 'SUCCESS') {
                $pay_trade_no=$json_param['tradeNo'];
                $price=($json_param['originalAmount'] * 100);
                $successCallback($out_trade_no, $price, $pay_trade_no);
                
                echo 'success';
                return true;
            } else {
                Log::error('Pay.CoinPay.verify, status illegal', ['$json_param' => $json_param]);
            }

            echo json_encode(['status' => 406]);
            return false;
        } 
    }

    /**
     * 退款操作
     * @param array $config 支付渠道配置
     * @param string $order_no 订单号
     * @param string $pay_trade_no 支付渠道流水号
     * @param int $amount_cent 金额/分
     * @return true|string true 退款成功  string 失败原因
     */
    function refund($config, $order_no, $pay_trade_no, $amount_cent)
    {
        return '此支付渠道不支持发起退款, 请手动操作';
    }

    /**
     * 设置签名，详见签名生成算法
     * @param $secret
     * @param $params
     * @return array
     */
    public function GetSign($secret, $params)
    {
        $p=ksort($params);
        reset($params);

		if ($p) {
			$str = '';
			foreach ($params as $k => $val) {
				$str .= $k . '=' .  $val . '&';
			}
			$strs = rtrim($str, '&');
		}
		$strs .='&key='.$secret;

        $signature = md5($strs);

        //$params['sign'] = base64_encode($signature);
        return $signature;
    }
    public function msectime() {
		list($msec, $sec) = explode(' ', microtime());
		$msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
		return $msectime;
    }
    /**
     * 返回随机字符串
     * @param int $length
     * @return string
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function _curlPost($url,$params=false,$signature,$ispost=0){
        
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); //设置超时
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array('token:'.$signature)
        );
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}