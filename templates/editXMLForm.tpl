{**
 * plugins/generic/jatsXmlEditor/editXMLForm.tpl
 *
 * Copyright (c) National Documentation Centre
 *
 *
 *}



{assign var="pageTitle" value="plugins.generic.jatsXmlEditor.editJatsFileLink"}
{include file="common/header.tpl"}

<form id="jatsEdit" method="post" action="#">

{include file="common/formErrors.tpl"}
<br />

<fieldset>
    <legend>{translate key="plugins.generic.jatsXmlEditor.bodyTags"}</legend>
    {if $sections}
        {foreach name=sections from=$sections key=sectionIndex item=section}
            <fieldset>
                <legend>{translate key="plugins.generic.jatsXmlEditor.section"}</legend>
                <table>
                    <tr>
                        <td>
                            <label>{fieldLabel name="sections-$sectionIndex-title" key="plugins.generic.jatsXmlEditor.sectionTitle"}</label>
                            <input type="text" name="sections[{$sectionIndex}][title]" id="sections-{$sectionIndex|escape}-title" value="{$section.title}" />
                        </td>
                        <td>
                            <label>{fieldLabel name="sections-$sectionIndex-secType" key="plugins.generic.jatsXmlEditor.sectionType"}</label>
                            <select name="sections[{$sectionIndex}][secType]" id="sections-{$sectionIndex|escape}-secType" size="1" class="selectMenu">
                                {html_options options=$sectionTypes selected=$section.secType}
	                        </select>
                        </td>
                    </tr>
                </table>
                
                {if $section.paragraphs}
                    {foreach name=paragraphs from=$section.paragraphs key=paragraphIndex item=paragraph}
                        <fieldset>
                            <legend>{translate key="plugins.generic.jatsXmlEditor.paragraph"}</legend>
                            <label>{fieldLabel name="sections-$sectionIndex-paragraph-$paragraphIndex-content" key="plugins.generic.jatsXmlEditor.paragraphContent"}</label>
                            <textarea rows="4" cols="50" name="sections[{$sectionIndex}][paragraphs][{$paragraphIndex}][content]" id="sections-{$sectionIndex|escape}-paragraph-{$paragraphIndex|escape}-content" value="{$paragraph.content}">{$paragraph.content}</textarea>

                            <p><input type="submit" class="button" name="deleteParagraph[{$sectionIndex}][{$paragraphIndex}]" id="deleteParagraph_{$sectionIndex}_{$paragraphIndex}" value="{translate key="plugins.generic.jatsXmlEditor.deleteParagraph"}" /></p>
                        </fieldset>
                    {/foreach}
                {/if}
                <p><input type="submit" class="button" name="addParagraph[{$sectionIndex}]" id="addParagraph_{$sectionIndex}" value="{translate key="plugins.generic.jatsXmlEditor.addParagraph"}" /></p>
                <p><input type="submit" class="button" name="deleteSection[{$sectionIndex}]" id="deleteParagraph_{$sectionIndex}" value="{translate key="plugins.generic.jatsXmlEditor.deleteSection"}" /></p>
            </fieldset>
        {/foreach}
    {else}
        {fieldLabel key="plugins.generic.jatsXmlEditor.noBodyTags"}
    {/if}
    <p><input type="submit" class="button" name="addSection" value="{translate key="plugins.generic.jatsXmlEditor.addSection"}" /></p>
</fieldset>


<input type="submit" class="button" name="save" value="{translate key="common.save"}" />
<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager" op="plugins" escape=false}'" />
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
