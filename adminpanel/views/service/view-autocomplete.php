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
    $object -> setSelfModel(get_class($system -> model));
    
    if(isset($_GET['id']))
    {				
        $object -> setSelfId(intval($_GET['id'])) -> getAvailbleParents($system -> model -> getTable());				
        $result = $object -> getDataForAutocomplete($request, $system -> db);
    }
    else if(isset($_GET['ids']) && $_GET['ids'])
    {
        $ids = explode(",", $_GET['ids']);
        $object -> getAvailbleParents($system -> model -> getTable());
        $result = $system -> model -> getParentsForMultiAutocomplete($request, $ids);
    }
    else
    {
        $object -> getAvailbleParents($system -> model -> getTable());
        $result = $object -> getDataForAutocomplete($request, $system -> db);
    }
}
else
    $result = $object -> getDataForAutocomplete($request, $system -> db);
    
if(isset($result["query"]))
    $result["query"] = htmlspecialchars_decode($result["query"], ENT_QUOTES);

Http::responseJson($result);