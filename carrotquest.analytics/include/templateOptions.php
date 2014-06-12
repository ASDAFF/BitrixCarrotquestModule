<? IncludeModuleLangFile(__FILE__); ?>

<style>
	.carrotquest_template_list
	{
		margin: 20px 0 0 0; // Отступ сверху
		padding: 0;
	}

	.carrotquest_menu_item
	{
		list-style-type: none;
	}
	
	.carrotquest_menu_checkbox
	{
		width: 20px;
		margin-left: 10px;
	}
		
	
	
</style>

<?= GetMessage('CARROTQUEST_TEMPLATES_LIST_HEADER_1');?> "<?= CARROTQUEST_BACKUP_PATH; ?>".<br>
<?= GetMessage('CARROTQUEST_TEMPLATES_LIST_HEADER_2');?>:
<ul class="carrotquest_template_list">
<?
	/**
	* Эти настройки необходимы одновренменно в опциях модуля и в установщике. Поэтому вынес в отдельный файл.
	*/
	
	// Получаем список всех имеющихся шаблонов сайтов.
	$sites = CSite::GetList();
	$templateList = array();
	while ($site = $sites->Fetch())
	{
		$rsTemplates = CSite::GetTemplateList($site["LID"]);
		while ($template = $rsTemplates->Fetch())
			$templateList[] = $template["TEMPLATE"];
	};
	$templateList = array_unique($templateList);
	$content = (array)json_decode(COption::GetOptionString(CARROTQUEST_MODULE_ID, "cqReplacedTemplates"));
	foreach($templateList as $template)
	{ 
		$checked = "checked";
		if ($_REQUEST['install'] != 'Y' && is_array($content) && !in_array($template, $content))
			$checked = ""
		?>
		<li class="carrotquest_menu_item" title="<?= GetMessage('CARROTQUEST_TEMPLATES_MOD_TEMPLATE'); ?>">
			<input type="checkbox" name = "<?= "carrotquest_template_".$template; ?>" class="carrotquest_menu_checkbox" <?= $checked; ?>>
			<span>
				<?= GetMessage('CARROTQUEST_TEMPLATES_TEMPLATE').' "'.$template.'"'; ?>
			</span>
		</li>
	<? } 
?>
</ul>