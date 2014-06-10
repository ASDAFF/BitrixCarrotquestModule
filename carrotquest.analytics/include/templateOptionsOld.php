<? IncludeModuleLangFile(__FILE__); ?>

<style>
	.carrotquest_template_list
	{
		margin: 20px 0 0 0; // Отступ сверху
		padding: 0;
	}
	
	.carrotquest_menu_title
	{
		cursor: pointer;
		list-style-type: none;
	}
	
	.carrotquest_menu_title input
	{
		cursor: pointer;
	}
	
	.carrotquest_menu_arrow
	{
		border: 5px solid transparent;	 
		border-left: 5px solid green;
		position: absolute;
		margin-top: 4px;
	}
	
	.carrotquest_menu_arrow.carrotquest_menu_opened
	{
		border: 5px solid transparent;	 
		border-top: 5px solid green;
		margin-top: 7px;
		margin-left: -2px;
		position: absolute;
	}
	
	.carrotquest_inner_menu
	{
		display: none;
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

<script>
	$(document).ready(function () {
		
		// Обработчик открытия выпадающего меню
		$(".carrotquest_menu_title").click(function (e) {
			if (e.target.tagName != 'INPUT' && $(e.target).parent().hasClass("carrotquest_menu_title"))
			{
				$(e.currentTarget).find(".carrotquest_menu_arrow").toggleClass('carrotquest_menu_opened');
				$(e.currentTarget).find("ul").slideToggle('slow');
			}
		});
		
		// Обработчик автодобавления галочек
		$(".carrotquest_menu_title > input").click(function (e) {
			var checked = $(e.target).prop("checked");
			var elements = $(e.target).parent().children().find("input");
			if (checked)
				elements.attr("checked","checked");
			else
				elements.removeAttr("checked");		
		});
		
		// Обработчик автодобавления галочек
		$(".carrotquest_menu_item").click(function (e) {
			var checkbox = $(e.currentTarget).find("input");

			if (e.target.tagName != "INPUT")
				if (checkbox.prop("checked"))
					checkbox.removeAttr("checked");
				else
					checkbox.attr("checked","checked");
			else ; // Кликнули по самому чекбоксу, сам поставится
		});
		
	});
</script>

<?= GetMessage('CARROTQUEST_TEMPLATES_LIST_HEADER_1');?> "<?= CARROTQUEST_BACKUP_PATH; ?>".<br>
<?= GetMessage('CARROTQUEST_TEMPLATES_LIST_HEADER_2');?>:
<ul class="carrotquest_template_list">
<?
	/**
	* Эти настройки необходимы одновренменно в опциях модуля и в установщике. Поэтому вынес в отдельный файл.
	*/
	
	$sites = CSite::GetList();
	$content = json_decode(COption::GetOptionString(CARROTQUEST_MODULE_ID, "cqReplacedTemplates"));
	while ($site = $sites->Fetch())
	{
		$siteName = '';
		if ($_REQUEST['install'] == 'Y')
			$siteChecked = "checked";
		else
		{
			$siteName = 'carrotquest_site_'.$site["LID"];
			$siteChecked = $content[ $siteName ];
		}
		?> <li class="carrotquest_menu_title" title="<?= GetMessage('CARROTQUEST_TEMPLATES_SHOW_SITE'); ?>">
				<span class="carrotquest_menu_arrow"></span>
				<input type="checkbox" class="carrotquest_menu_checkbox" name="<?= $siteName; ?>" title="<?= GetMessage('CARROTQUEST_TEMPLATES_MOD_SITE'); ?>" <?= $siteChecked; ?>>
				<span>
					<?= GetMessage('CARROTQUEST_TEMPLATES_SITE').' "'.$site["NAME"].' (ID = \''.$site["LID"].'\')"'; ?>
				</span>
				<ul class="carrotquest_inner_menu">
					<?	$rsTemplates = CSite::GetTemplateList($site["LID"]);
					while ($template = $rsTemplates->Fetch())
					{
						$templateName = 'carrotquest_template_'.$site["LID"]."^".$template["TEMPLATE"];
						if ($_REQUEST['install'] == 'Y')
							$checked = "checked";
						else
							$checked = $content[$templateName];
						?>
						<li class="carrotquest_menu_item" title="<?= GetMessage('CARROTQUEST_TEMPLATES_MOD_TEMPLATE'); ?>">
							<input type="checkbox" name="<?= $name; ?>" class="carrotquest_menu_checkbox" <?= $checked; ?>>
							<span>
								<?= GetMessage('CARROTQUEST_TEMPLATES_TEMPLATE').' "'.$template["TEMPLATE"].'"'; ?>
							</span>
						</li>
						<?
					}
					?>
				</ul>
			</li>
		<?
	};
?>
</ul>