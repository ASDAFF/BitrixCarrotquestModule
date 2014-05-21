<? 
IncludeModuleLangFile( __FILE__ );

class CarrotQuestApi
{
	public static $MODULE_ID = "carrotquest";
	public $Error = false; // Сюда запишется ошибка, если такая произойдет
    private $AuthToken; // Токен для отсылки данных с сервера api.API-KEY.API-SECRET
	
	function __construct()
    {
        if (COption::GetOptionString("carrotquest", 'cqApiKey') != "" && COption::GetOptionString("carrotquest", 'cqApiSecret') != "")
            $this->AuthToken = 'app.' . COption::GetOptionString("carrotquest", 'cqApiKey') . '.' . COption::GetOptionString("carrotquest", 'cqApiSecret');
    }
	
	/* Выполняет подключение к carrotquest. JS объект carrotquest уже должен быть инициализирован.
	*  Если пользователь залогинен, шлет идентификационные данные carrotquest.
	*/
	
	public static function Connect ()
	{
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
			$this->Error = "Connect failed: API-KEY not found!";
			return false;
		}
		return true;
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
		$url = "http://api.carrotquest.io/v1/users/".$_COOKIE['carrotquest_uid'].'/carrots/$self_app?auth_token='.$this->AuthToken;
        $answer = $this->HttpGet($url, $data);
		$answer = json_decode($answer, true);
		return $answer["data"];
	}
	
	// Отправка события о заказе
	public function OrderConfirm ($ID, $arFields)
    {
		// Делаю через сервер, чтоб не было проблем с безопасностью бонусов.
		$data = array(
			"items" 		=> $_COOKIE['CQBasketItems'],
			"appOrderId"	=> $ID,
			"app"			=> '$self_app',
			"user"			=> $_COOKIE['carrotquest_uid']
		);
		
		$url = "http://api.carrotquest.io/v1/orders?auth_token=".$this->AuthToken;
        $answer = $this->HttpPost($url, $data);
		RewriteFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/carrotquest/tmp.txt", $answer);
		$answer = json_decode($answer, true);
		return $answer;
    }
}