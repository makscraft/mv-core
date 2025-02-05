<?php
Http::isAjaxRequest('get', true);
$model = Http::fromGet('model');
$field = Http::fromGet('field');
$request = htmlspecialchars(Http::fromGet('query'), ENT_QUOTES);

if(!Registry::checkModel($model) || !$field)
    exit();

$model = new $model();

if(!$object = $model -> loadRelatedData() -> getElement($field))
    exit();

if($object -> getType() == "parent")
{			
    $object -> setSelfModel(get_class($model));
    
    if(isset($_GET['id']))
    {				
        $object -> setSelfId(intval($_GET['id'])) -> getAvailbleParents($model -> getTable());				
        $result = $object -> getDataForAutocomplete($request, Database::instance());
    }
    else if(isset($_GET['ids']) && $_GET['ids'])
    {
        $ids = explode(",", $_GET['ids']);
        $object -> getAvailbleParents($model -> getTable());
        $result = $model -> getParentsForMultiAutocomplete($request, $ids);
    }
    else
    {
        $object -> getAvailbleParents($model -> getTable());
        $result = $object -> getDataForAutocomplete($request, Database::instance());
    }
}
else
    $result = $object -> getDataForAutocomplete($request, Database::instance());
    
if(isset($result["query"]))
    $result["query"] = htmlspecialchars_decode($result["query"], ENT_QUOTES);

Http::responseJson($result);