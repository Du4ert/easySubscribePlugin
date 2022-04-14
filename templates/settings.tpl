{**
 * templates/settings.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Settings form for the easySubscribe plugin.
 *}
<script>
	$(function() {ldelim}
		$('#easySubscribeSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form
	class="pkp_form"
	id="easySubscribeSettingsForm"
	method="POST"
	action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	<!-- Always add the csrf token to secure your form -->
	{csrf}

	{fbvFormArea id="easySubscribePluginSettings"}
		{fbvFormSection label="plugins.generic.easySubscribe.captchaType"}
			{fbvElement
				type="select"
				id="captchaType"
				default=$captchaTypes[0]
				from=$captchaTypes
				selected=$captchaType
				description="plugins.generic.easySubscribe.captchaType.description"
			}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>