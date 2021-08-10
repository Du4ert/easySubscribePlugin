{**
 * templates/frontend/pages/subscribe.tpl
 *}
 {include file="frontend/components/header.tpl" pageTitle="plugins.generic.easySubscribe.page.subscribe"}

 <div id="main-content" class="page page_editorial_team">
 
     {include file="frontend/components/breadcrumbs.tpl" currentTitleKey="plugins.generic.easySubscribe.breadcrumbs"}
     {* Page Title *}
     <div class="page-header">
         {include file="frontend/components/editLink.tpl" page="management" op="settings" path="" anchor="" sectionTitleKey=""}
         <h1>{translate key="plugins.generic.easySubscribe.page.subscribe.title"}</h1>
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

     {if $status !== 'success'}
        <form class="pkp_form register" id="register" method="post" action="{url op="register"}">
        {csrf}
    
        {if $source}
            <input type="hidden" name="source" value="{$source|escape}" />
        {/if}
    
        {include file="common/formErrors.tpl"}
    
        <fieldset class="consent">
    
        
        <div class="form-group email">
        <label>
            {translate key="user.email"}
            <span class="form-control-required">*</span>
            <span class="sr-only">{translate key="common.required"}</span>
            <input class="form-control" type="email" name="email" id="email" value="{$email|escape}" placeholder="{translate key='plugins.generic.easySubscribe.page.email.placeholder'}" maxlength="90" required>
        </label>
        </div>
    
        </fieldset>
    
        {* recaptcha spam blocker *}
        {if $reCaptchaHtml}
            <fieldset class="recaptcha_wrapper">
                <div class="fields">
                    <div class="form-group recaptcha">
                        {$reCaptchaHtml}
                    </div>
                </div>
            </fieldset>
        {/if}
    
        <div class="buttons">
            <button class="btn btn-primary submit" name="submit" type="submit">
                {translate key="plugins.generic.easySubscribe.form.button"}
            </button>
        </div>
    </form>
     {/if}
     
 </div><!-- .page -->

 <script>
 // Disable submit button if inputs are empty
    let form = document.getElementById('register');
    let submit = form.elements.submit;
    let inputs = form.querySelectorAll('input:required');
    submit.disabled = true;

    inputs.forEach(item => {
     item.addEventListener('input', checkForm);
        
    });

    function checkForm() {
     
        for (item of inputs) {
            if ((item.type === 'text' || item.type === 'email') && item.value == '') {
                submit.setAttribute('disabled', true);
                break;
            } else if(item.type === 'checkbox' && !item.checked) {
                submit.setAttribute('disabled', true);
                break;
            } else {
                submit.disabled = false;
            }
     }
    }
 </script>
 
 {include file="common/frontend/footer.tpl"}