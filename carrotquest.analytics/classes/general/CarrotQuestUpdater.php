<? 
IncludeModuleLangFile( __FILE__ );
CModule::IncludeModule('sale');

/**
* ����� �������� ����������� ������� ��������� �������, ������������ � Carrot Quest.
*/
class CarrotQuestUpdater
{	
	public $MODULE_ID;
	private $TEMPLATE_LIST;
	private $MODIFICATIONS;
	
	function __construct ()
	{
		$this->MODULE_ID = CARROTQUEST_MODULE_ID;
		$this->SetTemplateList();
		$this->MODIFICATIONS  = array(
			// ��������� �������� ������
			"catalog" => array(
				"name" => "catalog",
				"path" => $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/catalog/templates/.default/", 
				"data" => array(
					array (
						"file" => "bitrix/catalog.element/.default/template.php",
						"after" => "#END#",
						"data" =>	"<!-- CarrotQuest Detailed Product Info Event Start -->\n".
									"<? //CModule::IncludeModule('".CARROTQUEST_MODULE_ID."');\n".
									"	if (COption::GetOptionString('".CARROTQUEST_MODULE_ID."', 'cqTrackProductDetails')) { ?>".
									"		<script>\n".
									"			carrotquest.track('".'$product_view'."', {\n".
									"				objectId: '<?= ".'$arResult["ID"]'."; ?>',\n".
									"				objectName: '<?= ".'$arResult["NAME"]'."; ?>',\n".
									"				objectUrl: window.location.protocol + '//' + window.location.host + '".'<?= $arResult["DETAIL_PAGE_URL"]; ?>'."',\n".
									"				objectType: '".'$product'."',\n".
									"				fullObject: ".'<?= json_encode($arResult); ?>'."\n".
									"			});\n".
									"		</script>\n".
									"	<?} ?>\n".
									"<!-- CarrotQuest Detailed Product Info Event End -->",
					),
				),
			),
			// �������
			"sale.basket.basket" => array(
				"name" => "sale.basket.basket",
				"path" => $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/sale.basket.basket/templates/.default/", 
				"data" => array(
					array (
						"file" => "template.php",
						"after" => "#END#",
						"data" =>	"<!-- CarrotQuest Basket Visit Event Start -->\n".
									"<?  //CModule::IncludeModule('".CARROTQUEST_MODULE_ID."');\n".
									"	 if (COption::GetOptionString('".CARROTQUEST_MODULE_ID."', 'cqTrackCartVisit')) { ?>\n".
									"		<script>\n".
									"			carrotquest.track('Cart')\n".
									"		</script>\n".
									"	<?} ?>".
									"<!-- CarrotQuest Basket Visit Event End -->",
					),
				),
			),
			/*"sale.order.ajax" => array(
				"name" => "sale.order.ajax",
				"path" => $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/sale.order.ajax/templates/.default/", 
				"data" => array(
					array (
						"file" => "result_modifier.php",
						"after" => "#END#",
						"data" =>	"",
					),
					array (
						"file" => "summary.php",
						"after" => "#END#",
						"data" =>	"",
					),
					array (
						"file" => "confirm.php",
						"after" => "#END#",
						"data" =>	"",
					),
					array (
						"file" => "lang/en/template.php",
						"after" => "#END#",
						"data" =>	"",
					),
					array (
						"file" => "lang/ru/template.php",
						"after" => "#END#",
						"data" =>	"",
					),
				),
			),*/
		);
	}

	function SetTemplateList ($object = false)
	{
		// ��������� ������		
		$type = gettype($object);
		if (!$object)
			$this->TEMPLATE_LIST = json_decode(COption::GetOptionString($this->MODULE_ID, "cqReplacedTemplates"));
		elseif ($type == 'string')
			$this->TEMPLATE_LIST = json_decode($object);
		elseif ($type != 'array' && $type != 'object')
			return false;
		else
			$this->TEMPLATE_LIST = $object;
		
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqReplacedTemplates",json_encode($this->TEMPLATE_LIST));
		
		return true;
	}
	
	function GetListFromRequest ()
	{
		$this->TEMPLATE_LIST = array();

		foreach ($_REQUEST as $key => $value)
		{
			if (preg_match('/carrotquest_template_([\s\S]+)/', $key, $matches))
				$this->TEMPLATE_LIST[$matches[1]] = array(
					"NAME" => $matches[1],
				);
			/* ��� ����������� ������
			if (preg_match('/carrotquest_site_([\s\S]+)/', $key, $matches))
				$this->TEMPLATE_LIST[$key] = array(
					"site" => $matches[1],
					"template" => false,
				);
			
			if (preg_match('/carrotquest_template_([\s\S]+)\^([\s\S]+)/', $key, $matches))
				$this->TEMPLATE_LIST[$key] = array(
					"site" => $matches[1],
					"template" => $matches[2],
				);*/
		}
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqReplacedTemplates",json_encode($this->TEMPLATE_LIST));

		return true;
	}
	function phpIsClosed ($fileName)
	{
		/*
		$status
		0 - ��� �������
		1 - ���������� ������ <
		2 - ��������� ����������� ������������������ <?
		3 - ����� ����������� ������������������ 2 ��� ������ ? (����������). ���� ������� >, ���������� � 0.
		
		$commentStatus
		0 - ��� �����������
		1 - ��� ���� / ��� �����������
		2 - � ������� ������ ��������� ����������� //
		3 - � ������� ������ ����������� /*
		4 - ������ ����������� 3 ���� * ���������� ��������
		*/
		$length = filesize($fileName);
		$content = file_get_contents($fileName);
		$status = 0;
		$commentStatus = 0;
		$show = false;
		for ($i = 0; $i < $length; $i++)
		{
			switch ($commentStatus)
			{
				case 0: {
					if ($content[$i] == '/' && $status == 2)
						$commentStatus = 1;
					break;
				}
				case 1: {
					if ($content[$i] == '/')
						$commentStatus = 2;
					elseif ($content[$i] == '*')
						$commentStatus = 3;
					else
						$commentStatus = 0;
					break;
				}
				case 2: {
					if ($content[$i] == '\n')
					{
						$commentStatus = 0;
					}
					break;
				}
				case 3: {
					if ($content[$i] == '*')
						$commentStatus = 4;
					break;
				}
				case 4: {
					if ($content[$i] == '/')
						$commentStatus = 0;
					else
						$commentStatus = 3;
					break;
				}
			}
			
			switch ($status)
			{
				case 0: {
					if ($content[$i] == '<' && $commentStatus != 3)
						$status = 1;
					break;
				}
				case 1: {
					$status = ($content[$i] == '?' ? 2 : 0);
					break;
				}
				case 2: {
					if ($content[$i] == '?' && $commentStatus != 3)
					{
						$status = 3;
					}
					break;
				}
				case 3: {
					if ($content[$i] == '>')
					{
						if ($commentStatus == 2)
							$commentStatus = 0;
						$status = 0;
					}
					else
						$status = 2;
					break;
				}
			}
		}
		return ($status != 2);
	}
	
	function UpdateAllTemplates ()
	{
		/**
			������ ��������� �������:
			"NAME" - ��� �������
			"PATH" - ���� � �������
			"SITE_BACKUP_PATH" - ����
			"MODIFICATIONS" - ������-������ ���������, ������������� � ������� �������. ��������� ��������:
				"COMPONENT_TEMPLATE_NAME" - ��� ������� ����������
				"COMPONENT_TEMPLATE_PATH" - ���� � ������� ����������
				"COPIED_BY_CARROTQUEST" - ��� �� ������ ���������� ������� ���������� carrotquest-��
				"FILES_CREATED" - ������ ����� ������ ������ �������, ��������� (�������������) carrotquest-��. �� ��������� ����������� ������� ������� ("COPIED_BY_CARROTQUEST" = true)
				"FILES_MODIFIED" - ������ ����� ������, ���������������� carrotquest-��.
		*/
		foreach($this->TEMPLATE_LIST as $tplName =>& $tpl)
		{ 
			// ������� backup
			$tpl = (array)$tpl;
			$tpl["SITE_BACKUP_PATH"] = CARROTQUEST_BACKUP_PATH.$tplName;
			$tpl["PATH"] = $_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/".$tplName;
			CheckDirPath($tpl["SITE_BACKUP_PATH"]);	
			CopyDirFiles(
				$tpl["PATH"],
				$tpl["SITE_BACKUP_PATH"],
				true, true);
			
			// ������������ ������ �� ����� ��������
			$tpl['MODIFICATIONS'] = array();
			
			foreach ($this->MODIFICATIONS as $value)
				$this->ModifyTemplate($value["name"], $value["path"], $value["data"], $tpl);
		}
		
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqReplacedTemplates",json_encode($this->TEMPLATE_LIST));
	}
	
	function RestoreAllTemplates ()
	{
		foreach($this->TEMPLATE_LIST as $tpl)
			$this->RestoreTemplate($tpl);
	}
	
	/**
	* �������������� ������� ������ ������ ���������� Bitrix.
	* ��� ������������� �������� ��� �� ����������� �������� � ����� /bitrix/templates/���_�������_�����/components/bitrix/
	* <b>���������:</b>
	* <var>$path</var> - ���� � �������, ������� ���������� ���������.
	* <var>$data</var> - ������ ������, ������� ���� ������� � ������. � ������� <code>{file: "template.php", after: "regexp", data: ""}</code>
	* ���� � ����� - ������������ $path.
	* ���� after ����� ��������� �������� ����� #END# - ����� ������ ����� ������������ � ����� �����.
	* <b>������������ ��������:</b>
	* true - � ������ ������ ����������, false - � ������ �������.
	*/
	function ModifyTemplate ($componentTemplateName, $path, $data, & $templateToModify)
	{
		$result = true;
		
		// ���� � �������� ����������� ��� ������� ������� �����
		$componentTemplatePath = $templateToModify["PATH"].'/components/bitrix/'.$componentTemplateName."/";

		// �������� ���� � �����������
		$componentTemplateInfo = array(
			"COMPONENT_TEMPLATE_NAME" => $componentTemplateName,
			"COMPONENT_TEMPLATE_PATH" => $componentTemplatePath,
		);

		if (!file_exists($componentTemplatePath))
		{
			// �������� ������
			CheckDirPath($componentTemplatePath);
			$result = CopyDirFiles($path, $componentTemplatePath, true, true);
			$componentTemplateInfo["COPIED_BY_CARROTQUEST"] = true;
		}
		else 
			$componentTemplateInfo["COPIED_BY_CARROTQUEST"] = false; // ����� �������������� ������ ������������
		
		if ($result)
		{
			$componentTemplateInfo['FILES_CREATED'] = array();
			$componentTemplateInfo['FILES_MODIFIED'] = array();
			foreach ($data as $change)
			{
				// ���� � �����, ������� ����� ��������
				$file = $componentTemplatePath.$change["file"];
				
				if (!file_exists($file))
				{
					// �������� �� ��������� �������
					$result = copy($file, $path.$change["file"]);
					$componentTemplateInfo['FILES_CREATED'][] = $file;
				};
				
				if ($result)
				{
					$componentTemplateInfo['FILES_MODIFIED'][] = $file;
					// ��������� ���������� �����
					$insert = "\n<!-- Carrot Quest Insert Start -->\n".$change["data"]."\n<!-- Carrot Quest Insert End -->\n";	
					
					// ��������� �����
					if ($change["after"] == "#END#")
					{
						// ���������� ������� php ���� �� ��� �� ������...
						if (!$this->phpIsClosed($file))
							$insert = "\n?>".$insert;
						
						$result = (file_put_contents($file, $insert, FILE_APPEND) == strlen($insert));
					}
					else
					{
						$content = file_get_contents ($file);
						if ($content)
						{
							$content = preg_replace("(".$change["after"].")", '$0'.$insert, $content);
							$result = RewriteFile($file, $content);
						}
						else
							$result = false;
					};
				};
			};
		};
			
		// ���� ����� - ��������� ���������. ����� ����� � ������������ ���������.
		$templateToModify['MODIFICATIONS'][] = $componentTemplateInfo;
		if (!$result)
			$this->RestoreTemplate($templateToModify);
		
		return $result;
	}
	
	function RestoreTemplate ($tpl)
	{
		$tpl = (array)$tpl;
		// ������������ ���������
		foreach ($tpl["MODIFICATIONS"] as $mod)
		{
			foreach ($mod -> FILES_MODIFIED as $file)
			{
				$content = file_get_contents ($file);
				$pattern = "~<!-- Carrot Quest Insert Start -->([\s\S]*?)<!-- Carrot Quest Insert End -->~is";
				$content = preg_replace($pattern, "", $content);
				RewriteFile($file, $content);
			}
		}
	}
}
