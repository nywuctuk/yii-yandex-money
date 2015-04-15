<?php

require_once(dirname(__FILE__).'/../extensions/yandexmoneyapi/api.php');	// https://github.com/yandex-money/yandex-money-sdk-php/tree/master/lib
use \YandexMoney\API;
use \YandexMoney\BaseAPI;
use \YandexMoney\Config;

class TestController extends CController {
    public $breadcrumbs = array();
	
    public $client_secret = "ВВЕДИТЕ client_secret";
    public $client_id = "ВВЕДИТЕ client_id";
    public $redirect_uri = "ВВЕДИТЕ redirect_uri"; 	// for example, http://mysite.ru/index.php?r=test/index
    public $scope = "account-info payment-p2p";
    /*public $scope = 
		'account-info'." ".
		'operation-history'." ".
		'operation-details'." ".
		'incoming-transfers'." ".
		'payment'." ".
		'payment-shop'." ".
		'payment-p2p'." ".
		'money-source';
	*/
	
    public $code;
	
    private $api = null;
    private $request_payment;
	
	public $success;
	public $result;
	public $is_error;		// boolean (flag for 4.4)
	public $test_payment = true;	// boolean

	public $account_info;
	
	
    //func from YandexMoney API (измененная)
    public static function buildObtainTokenUrl2($client_id, $redirect_uri, $scope) {
        $params = sprintf(
            //"client_id=%s&response_type=%s&redirect_uri=%s&scope=%s", $client_id, "code", $redirect_uri, implode(" ", $scope)
            "client_id=%s&response_type=%s&redirect_uri=%s&scope=%s", $client_id, "code", $redirect_uri, $scope
            );
        return sprintf("%s/oauth/authorize?%s", Config::$SP_MONEY_URL, $params);
    }
	
	//1 - Authorization using specific access($scope)
	public function authorizeYM() {
		$auth_url = $this->buildObtainTokenUrl2( $this->client_id, $this->redirect_uri, $this->scope );
		BaseAPI::sendRequest($auth_url);
		$this->redirect($auth_url);
	
	}
	

	// 4.3	https://tech.yandex.ru/money/doc/dg/reference/request-payment-docpage/
	public function requestPaymentP2P() {
       
		$options = array(
			"pattern_id" 	=> 	"p2p",
			"to" 			=> 	"ВВЕДИТЕ НОМЕР КОШЕЛЬКА",	
			"amount" 		=> 	1.00,											//Сумма к оплате.
			//"amount_due" 	=> 	"",												//Сумма к получению.
			"comment"		=> 	"test payment comment",
			"message" 		=> 	"test payment message",
			"label" 		=> 	"test-payment-label",
			//"codepro"
			//"hold_for_pickup"
			//"expire_period"
			
		);
		
		if($this->test_payment == true){
			$options['test_payment'] 	= 	'true';
            $options['test_card'] 		= 	'available';
            $options['test_result'] 	= 	'success';
        }
		
		
		$request_payment = $this->api->requestPayment($options);
		$this->request_payment = $request_payment;			//для передачи результата в processPaymentByWallet
		
		
		if($request_payment->status == "success") {
			$this->is_error = false;
			$this->result = "result - request_payment ПРОШЕЛ ХОРОШО!";
		}
		else {
			$this->is_error = true;
			$this->result = "result - request_payment ОШИБКА: ";
		}
		
		//if ( isset($request_payment->status) && isset($request_payment->error) ) {
		if ( isset($request_payment->error) ) {
			
			$this->result = "result - request_payment  СТАТУС: " . $request_payment->status . " ОШИБКА: " . $request_payment->error;

			if ( isset($request_payment->ext_action_uri) ) {
				$this->redirect($request_payment->ext_action_uri);
				$this->result = $this->result . " ext_action_uri = " . $request_payment->ext_action_uri;
			}
		}

    }
	

	// 4.4	https://tech.yandex.ru/money/doc/dg/reference/process-payment-docpage/
    public function processPaymentByWallet() {
        	
			$options = array(
				"request_id" 	=> 	$this->request_payment->request_id,
				"money_source" 	=> 	'wallet',						//def = wallet
				//"csc"
				//"ext_auth_success_uri"
				//"ext_auth_fail_uri"
			);
			
			if($this->test_payment == true){
				$options['test_payment'] 	= 	'true';
				$options['test_card'] 		= 	'available';
				$options['test_result'] 	= 	'success';
			}
		
			$process_payment = $this->api->processPayment($options);
				

			if ( $process_payment->status == "success" ) {
				$this->result = "result = process_payment ПРОШЕЛ ХОРОШО!" . 
					" Вы отправили " . $process_payment->credit_amount . " на кошелек " . $process_payment->payee;
			}
			
			if ( isset($process_payment->error) ) {
				$this->result = "result = process_payment ОШИБКА: " . $process_payment->error;
			}

    }

	
	public function actionIndex() {

		if ( isset($_GET['code']) ) {
			$this->code = $_GET['code'];
			
			$access_token_response  = API::getAccessToken( $this->client_id, $this->code, $this->redirect_uri, $this->client_secret );	//~ sendRequest
			
			if	( ! property_exists($access_token_response, "error") ) {
			
				$access_token = $access_token_response->access_token;
				
				$this->api = new API($access_token);
				$this->success = "Авторизация успешна пройдена";
				
				// 4
				$this->account_info = $this->api->accountInfo(); //
				
				// 4.3
				$this->requestPaymentP2P();
				
				// 4.4
				if ($this->is_error == false)	{
					$this->processPaymentByWallet();
				}

			}
			else {
				$this->success = "Ошибка при получении access_token_response";
			}
			
		} 
		
		$this->render('index');
		
	}
	
	// default func
	public function actionError() {
		if($error=Yii::app()->errorHandler->error) {
		if(Yii::app()->request->isAjaxRequest)
			echo $error['message'];
		else
			$this->render('error', $error);
		}
	}

}
