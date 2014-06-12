<? 
IncludeModuleLangFile( __FILE__ );
CModule::IncludeModule("currency");

/**
* Класс для работы с сервером CarrotQuest.
*/
class CarrotQuestApi
{
	/**
	* Имя модуля, инициализируется в конструкторе
	*/
	public static $MODULE_ID;
	/**
	* Содержит текст последней ошибки или false, если все в порядке
	*/
	public $Error = false;
	/**
	* Токен для отсылки данных с сервера <samp>api.API-KEY.API-SECRET</samp>
	*/
    private $AuthToken; 
	/**
	* Идентификатор пользователя в системе Carrot Quest
	*/
	private $UID = null;
	
	/**
	* Конструктор класса. Инициализирует свойства.
	*/
	function __construct()
    {
		$this->MODULE_ID = CARROTQUEST_MODULE_ID;
		
        if (COption::GetOptionString($this->MODULE_ID, 'cqApiKey') != "" && COption::GetOptionString($this->MODULE_ID, 'cqApiSecret') != "")
            $this->AuthToken = 'app.' . COption::GetOptionString($this->MODULE_ID, 'cqApiKey') . '.' . COption::GetOptionString($this->MODULE_ID, 'cqApiSecret');
		if (array_key_exists('carrotquest_uid', $_COOKIE))
			$this->UID = $_COOKIE['carrotquest_uid'];
    }
	
	/**
	*	Выполняет подключение к Carrot Quest на стороне клиента (JavaScript). JS объект <var>carrotquest</var> уже должен быть инициализирован.
	*   Если пользователь залогинен, шлет идентификационные данные методом <var>carrotquest.identify()</var>.
	*	<b>Параметры:</b> отсутствуют
	*	<b>Возвращаемое значение:</b>
	*	true, если в параметрах модуля найден API-KEY, false в противном случае
	*/
	public function Connect ()
	{
		// В header-е уже должен быть инициализирован carrotquest (в js)
		$ApiKey = COption::GetOptionString($this->MODULE_ID,"cqApiKey");
		
		if ($ApiKey)
		{?>
			
			<script>
				if (typeof(carrotquest) != "undefined")
					carrotquest.connect("<?= $ApiKey; ?>");
				else ;
					// console.log("Ошибка сервера carrotquest (connect)!");
			</script>
			
			<!-- Вызов идентификации -->
			<?if (CUser::IsAuthorized()) { ?>
				<script>
					if (typeof(carrotquest) != "undefined") // На всякий случай, чтобы не выдавал в консоль ругань
					{
						carrotquest.identify({
												$uid: "<?= CUser::GetID(); ?>",
												$email: "<?= CUser::GetEmail(); ?>", 
												$name: "<?= CUser::GetLogin(); ?>"
											});
					}
					else ;
					//	console.log("Ошибка сервера carrotquest (identify)!");
				</script>
		<?	}
		}
		else
		{
			return false;
		}
		return true;
	}
	
	/**
	*	Трекинг события со стороны сервера.
	*	<b>Параметры:</b>
	*	<var>$event</var> - название события (строка)
	*	<b>Возвращаемое значение:</b>
	*	Текст ответа на запрос в формате json.
	*/
	public function Track ($event)
	{
		$data = array(
			"event"			=> $event,
			"app"			=> '$self_app',
			"user"			=> $this->UID
		);
		
		$url = "http://api.carrotquest.io/v1/events?auth_token=".$this->AuthToken;
        $answer = $this->HttpQuery('POST',$url, $data);
		$answer = json_decode($answer, true);
		return $answer;
	}
	
	/**
	*	Отправляет запрос через Http со стороны сервера.
	*	<b>Параметры:</b>
	*	<var>$method</var> - Метод передачи параметров (GET или POST)
	*	<var>$url</var> - Адрес запроса
	*	<var>$data</var> - Данные, передаваемые методом <var>$method</var>
	*	<b>Возвращаемое значение:</b>
	*	Текст ответа на запрос в формате json.
	*/
	private function HttpQuery ($method, $url, $data) 
	{
        $fields = '';
        foreach($data as $key => $value) { 
            $fields .= $key . '=' . $value . '&'; 
        }
        rtrim($fields, '&');
		
		$msg = curl_init();
		try
		{
			curl_setopt($msg, CURLOPT_URL, $url);
			if ($method == 'POST')
				curl_setopt($msg, CURLOPT_POST, count($data));
			elseif ($method == 'GET')
				curl_setopt($msg, CURLOPT_GET, count($data));
			else
			{
				curl_close($msg);
				return false;
			}
			curl_setopt($msg, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($msg, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($msg);
		}
		catch (Exception $e)
		{
			$result = array("meta" => array("error" => "Exception", "errorMessage" => $e->GetMessage()), "data" => array());
			$this->Error = $e->GetMessage();
		};
		
		curl_close($msg);
		
		return $result;
    }
	
	/**
	*	Отправляет запрос через Http со стороны сервера.
	*	<b>Параметры:</b> отсутствуют
	*	<b>Возвращаемое значение:</b>
	*	Количество морковок, выбранных пользователем. False в случае ошибки.
	*/
	public function GetSelectedCarrots ()
	{
		$data = array();
		$url = "http://api.carrotquest.io/v1/users/".$this->UID.'/carrots/$self_app?auth_token='.$this->AuthToken;
        $answer = $this->HttpQuery('GET',$url, $data);
		$answer = json_decode($answer, true);
		if ($answer['meta']['error'])
			return false;
		else
			return $answer["data"];
	}
	
	/**
	*	Событие подтверждения заказа. Если включена бонусная система - срабатывает со стороны сервера, иначе со стороны клиента.
	*	<b>Параметры:</b>
	*	<var>$ID</var> - Идентификатор заказа.
	*	<var>$arFields</var> - Параметры заказа в формате Bitrix.
	*	<var>$_COOKIE['carrotquest_basket_items']</var> - список товаров в корзине в формате Carrot Quest (JSON)
	*	<b>Возвращаемое значение:</b>
	*	Ответ сервера на запрос подтверждения заказа, если включена бонусная система. Если выключена -  {server: false};
	*/
	public function OrderConfirm ($ID, $arFields)
    {
		if (COption::GetOptionString($this->MODULE_ID,'cqActivateBonus') == "checked")
		{
			// Делаю через сервер, чтоб не было проблем с безопасностью бонусов.
			$data = array(
				"items" 		=> $_COOKIE['carrotquest_basket_items'],
				"appOrderId"	=> $ID,
				"app"			=> '$self_app',
				"user"			=> $this->UID,
			);
			setcookie('carrotquest_basket_items','',0,"/");
			setcookie('carrotquest_order_id', '',0,"/");
			
			$url = "http://api.carrotquest.io/v1/orders?auth_token=".$this->AuthToken;
			$answer = $this->HttpQuery('POST',$url, $data);
			$answer = json_decode($answer, true);
			return $answer;
		}
		else {
			// Остался кук carrotquest_basket_items, мы его поймаем при загрузке страницы заказа в js
			setcookie('carrotquest_order_id', $ID,0,"/");
			return array('server' => false);
		}
    }
	
	/**
	*	Метод модифицирует массив $arResult, добавляя в него скидку Carrot quest
	*	<b>Параметры:</b>
	*	<var>$arResult</var> - Массив в формате битрикс, содержащий параметры заказа.
	*	<b>Возвращаемое значение:</b>
	*	Измененный <var>$arResult</var>
	*	<var>$_COOKIE['carrotquest_price']</var> - стоимость заказа с учетом скидки Carrot quest
	*/
	public function CalcDiscount($arResult)
	{
		// Вычисляем скидку
		$total = $arResult['ORDER_PRICE'];

		$CarrotInfo = $this->GetSelectedCarrots();
		if ($CarrotInfo)
		{
			// Максимальное количетво морковок, которым можно расплатиться за заказ
			$max_carrots = floor($total * $CarrotInfo['max_discount']);

			// Валидация скидки
			if ( $CarrotInfo['carrots_selected'] > $max_carrots || $CarrotInfo['carrots_selected'] < 0)
				$discount_percent = 0;
			else
				// Доля скидки
				$discount_percent = round($CarrotInfo['carrots_selected'] * $CarrotInfo['max_discount'] / $max_carrots, 4);
			
			// Стоимость скидки в рублях
			$discount_value = floor($total * $discount_percent);
			
			// Перопределяем итоговую стоимость
			$arResult["CARROTQUEST_DISCOUNT_PRICE"] = $discount_value;
			$arResult["CARROTQUEST_DISCOUNT_PRICE_FORMATED"] = CCurrencyLang::CurrencyFormat($discount_value, "RUB", true);
			$priceFormat = $arResult["ORDER_TOTAL_PRICE_FORMATED"];
			$price = 0;
			
			for ($i = 0, $out = false; !$out && $i < strlen($priceFormat); $i++)
			{
				if ($priceFormat[$i] == ' ');
				elseif (ord($priceFormat[$i]) >= 48 && ord($priceFormat[$i]) <= 57)
					$price = $price * 10 + $priceFormat[$i];
				else
					$out = true;
			}
			$result_price = $price - $discount_value;
			
			// Устанавливаем кук для обработчика оформления заказа
			setcookie('carrotquest_price',$result_price,0, "/");
			$arResult["ORDER_TOTAL_PRICE_FORMATED"] = CCurrencyLang::CurrencyFormat($result_price, "RUB", true);
		}
				
		return $arResult;
	}
}
