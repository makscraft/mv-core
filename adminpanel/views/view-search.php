<?php
if('' !== $search_text = Http::fromGet('text', ''))
  $search_text = trim(htmlspecialchars(urldecode($search_text), ENT_QUOTES));

$search_text = Service::cleanHtmlSpecialChars($search_text);	

if($search_text && mb_strlen($search_text) > 1)
    $result = (new Searcher) -> searchInAllModels($search_text);
else
    $result = ['number' => 0, 'html' => []];

$url_params = $search_text ? 'text='.$search_text : '';
$limit = $admin_panel -> getPaginationLimit();
$paginator = new Paginator($result['number'], $limit);

if($limit = Http::fromGet('pager-limit'))
{
    if($admin_panel -> savePaginationLimit($limit))
    {
        $paginator -> setLimit($limit);
        Http::reload('view=search&text='.$search_text);
    }
}

$paginator -> setUrlParams($url_params);
$html = array_slice($result['html'], $paginator -> getStart(), $paginator -> getLimit());
$html = implode('', $html);

include Registry::get('IncludeAdminPath').'includes/header.php';
?>
<div id="columns-wrapper">
    <div id="index-search" class="search-page">
        <h3 class="column-header"><?php echo I18n::locale('search'); ?>
            <span class="header-info"><?php echo I18n::locale('results-found'); ?>: <?php echo $result["number"]; ?></span>
        </h3>
         <div id="search-results">
            <?php echo $html; ?>
         </div>
         <div id="search-pager">
            <div class="pager-limit">
                <span><?php echo I18n::locale('pager-limit'); ?></span>
                <select>
                    <?php echo $paginator -> displayPagerLimits(AdminPanel::PAGINATION_LIMITS); ?>
                </select>
                <input type="hidden" name="text" value="<?php echo $search_text; ?>" />
	          </div>
            <?php echo $paginator -> displayPagesAdmin(); ?>  
         </div>    
    </div>            
</div>
<?php include Registry::get('IncludeAdminPath').'includes/footer.php'; ?>