<? 
IncludeModuleLangFile( __FILE__ );

class CarrotQuestApi
{
	public static $MODULE_ID = "carrotquest";
	public $Error = false; // Сюда запишется ошибка, если такая произойдет
    private $AuthToken; // Токен для отсылки данных с сервера api.API-KEY.API-SECRET
	private $UID = null; // Идентификатор пользователя в Carrotquest
	
	function __construct()
    {
        if (COption::GetOptionString("carrotquest", 'cqApiKey') != "" && COption::GetOptionString("carrotquest", 'cqApiSecret') != "")
            $this->AuthToken = 'app.' . COption::GetOptionString("carrotquest", 'cqApiKey') . '.' . COption::GetOptionString("carrotquest", 'cqApiSecret');
		if (array_key_exists('carrotquest_uid', $_COOKIE))
			$this->UID = $_COOKIE['carrotquest_uid'];
			
    }
	
	/* Выполняет подключение к carrotquest. JS объект carrotquest уже должен быть инициализирован.
	*  Если пользователь залогинен, шлет идентификационные данные carrotquest.
	*/
	
	/*public function tryKeys ($ApiKey, $ApiSecret)
	{
		// Вообще я думаю это надо сделать.
		$data = array(
			"event"			=> 'TryKeys',
			"app"			=> '$self_app',
			"user"			=> $this->UID
		);
		
		$url = "http://api.carrotquest.io/v1/events?auth_token=".$ApiKey.'.'.$ApiSecret;
        $answer = $this->HttpPost($url, $data);
		$answer = json_decode($answer, true);
		return $answer;
	}*/
	
	public static function Connect ()
	{
		// В header-е уже должен быть инициализирован carrotquest (в js)
		$ApiKey = COption::GetOptionString("carrotquest","cqApiKey");
		if ($ApiKey)
		{?>
			<script>
				if (typeof(carrotquest) != "undefined")
					carrotquest.connect("<?= $ApiKey; ?>");
				else
					console.log("Ошибка сервера carrotquest (connect)!");
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
					else
						console.log("Ошибка сервера carrotquest (identify)!");
				</script>
		<?	}
		}
		else
		{
			return false;
		}
		return true;
	}
	
	public function track ($event)
	{
		$data = array(
			"event"			=> $event,
			"app"			=> '$self_app',
			"user"			=> $this->UID
		);
		
		$url = "http://api.carrotquest.io/v1/events?auth_token=".$this->AuthToken;
        $answer = $this->HttpPost($url, $data);
		$answer = json_decode($answer, true);
		return $answer;
	}
	
	// Отправка любой информации запросом POST
	private function HttpPost($url, $data) 
	{
        $fields = '';
        foreach($data as $key => $value) { 
            $fields .= $key . '=' . $value . '&'; 
        }
        rtrim($fields, '&');

        $post = curl_init();

        curl_setopt($post, CURLOPT_URL, $url);
        curl_setopt($post, CURLOPT_POST, count($data));
        curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($post);

        curl_close($post);
		
		return $result;
    }
	
	// Отправка любой информации запросом GET
	private function HttpGet($url, $data) 
	{
        $fields = '';
        foreach($data as $key => $value) { 
            $fields .= $key . '=' . $value . '&'; 
        }
        rtrim($fields, '&');

        $get = curl_init();

        curl_setopt($get, CURLOPT_URL, $url);
        curl_setopt($get, CURLOPT_GET, count($data));
        curl_setopt($get, CURLOPT_GETFIELDS, $fields);
        curl_setopt($get, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($get);

        curl_close($get);
		
		return $result;
    }
	
	// Получение объекта, указывающего количество морковок, выбранных пользователем
	public function GetSelectedCarrots ()
	{
		$data = array();
		$url = "http://api.carrotquest.io/v1/users/".$this->UID.'/carrots/$self_app?auth_token='.$this->AuthToken;
        $answer = $this->HttpGet($url, $data);
		$answer = json_decode($answer, true);
		return $answer["data"];
	}
	
	// Отправка события о заказе
	public function OrderConfirm ($ID, $arFields)
    {
		// Чисто тестовый вывод
		// RewriteFile('c:\Bitrix\www\bitrix\modules\carrotquest\tmp.txt',json_encode($data));
		if (COption::GetOptionString('carrotquest','cqActivateBonus') == "checked")
		{
			// Делаю через сервер, чтоб не было проблем с безопасностью бонусов.
			$data = array(
				"items" 		=> $_COOKIE['CQBasketItems'],
				"appOrderId"	=> $ID,
				"app"			=> '$self_app',
				"user"			=> $this->UID,
			);
			setcookie('CQBasketItems','');
			setcookie('CQOrderId', '');
			
			$url = "http://api.carrotquest.io/v1/orders?auth_token=".$this->AuthToken;
			$answer = $this->HttpPost($url, $data);
			$answer = json_decode($answer, true);
			return $answer;
		}
		else {
			// Остался кук CQBasketItems, мы его поймаем при загрузке страницы заказа в js
			setcookie('CQOrderId', $ID);
		}
    }
}