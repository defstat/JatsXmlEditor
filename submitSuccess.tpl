{**
 * plugins/generic/jatsXmlEditor/submitSuccess.tpl
 *
 * Copyright (c) 2017 National Documentation Centre
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a message indicating that the article was successfuly submitted.
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.jatsXmlEditor.success"}
{include file="common/header.tpl"}
{/strip}

<p>{translate key="plugins.generic.jatsXmlEditor.successDescription"}  <a href="{plugin_url}">{translate key="plugins.generic.jatsXmlEditor.successReturn"}</a></p>

{include file="common/footer.tpl"}