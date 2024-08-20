<?php
include "../../config/autoload.php";

Http :: isAjaxRequest('get', true);
$system = new System('ajax');

if(isset($_GET['query']))
{
	$request = trim(htmlspecialchars(urldecode($_GET["query"]), ENT_QUOTES));
	$request = preg_replace("/[\.,:;!\?\"'\+\(\)\[\}\^\$\*]/", "", $request);
	
	$result = $system -> searchInAllModelsAjax($request, false);
	
	$result = [
		'query' => $request,  
		'suggestions' => array_values($result),
		'data' => array_keys($result)
	];
	
	Http :: responseJson($result);
}