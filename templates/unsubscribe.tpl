{**
 * templates/frontend/pages/unsubscribe.tpl
 *}
 {include file="frontend/components/header.tpl" pageTitle="plugins.generic.easySubscribe.page.title"}

 <div id="main-content" class="page page_editorial_team">
 
     {include file="frontend/components/breadcrumbs.tpl" currentTitleKey="plugins.generic.easySubscribe.page.title"}
     {* Page Title *}
     <div class="page-header">
         {include file="frontend/components/editLink.tpl" page="management" op="settings" path="" anchor="" sectionTitleKey=""}
         <h1>{translate key="plugins.generic.easySubscribe.page.unsubscribe.title"}</h1>
     </div>
     {* /Page Title *}


     {if $status === 'error'}
         <div class="alert alert-danger" role="alert">
         {$message}
         </div>
         {elseif $status === 'success'}
            <div class="alert alert-success" role="alert">
            {$message}
         </div>
     {/if}

     
 
     
 </div><!-- .page -->

 
 
 {include file="common/frontend/footer.tpl"}