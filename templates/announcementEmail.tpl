{**
 * templates/announcementEmail.tpl
 *}

 <h1>{$announcement->getLocalizedTitle()|escape}</h1>
 <h2>Текст из плагина</h2>
 <p class="date">
     {$announcement->getDatePosted()|date_format:$dateFormatShort}
 </p>
 <p class="description">
     {if $announcement->getLocalizedDescription()}
         {$announcement->getLocalizedDescription()|strip_unsafe_html}
     {else}
         {$announcement->getLocalizedDescriptionShort()|strip_unsafe_html}
     {/if}
 </p>