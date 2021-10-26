{**
 * templates/frontend/pages/unsubscribe.tpl
    * uses
    * $emailsList array
 *}
 {include file="frontend/components/header.tpl" pageTitle="plugins.generic.easySubscribe.page.title"}

 <div id="main-content" class="page page_editorial_team">
 
     {include file="frontend/components/breadcrumbs.tpl" currentTitleKey="plugins.generic.easySubscribe.page.title"}
     {* Page Title *}
     <div class="page-header">
         {include file="frontend/components/editLink.tpl" page="management" op="settings" path="" anchor="" sectionTitleKey=""}
         <h1>{translate key="plugins.generic.easySubscribe.page.list.title"}</h1>
     </div>
     {* /Page Title *}
     <table>
         <thead>
             <tr>
                 <td>Id</td>
                 <td>Email</td>
                 <td>Active</td>
                 <td>Delete</td>
             </tr>
         </thead>
    {foreach from=$emailsList item=email}
            <tr>
            <td>{$email->getId()}</td>
            <td>{$email->getEmail()}</td>
            <td>{$email->getActive()}</td>
            <td><a href="/jour/easysubscribe/unsubscribe?email={$email->getData('email')}&id={$email->getId()}">Удалить</a></td>
            </tr>
    {/foreach}
    </table>

     
 
     
 </div><!-- .page -->

 
 
 {include file="common/frontend/footer.tpl"}