<?php
Http::isAjaxRequest('get', true);

if($query = Http::fromGet('query'))
{
	$searcher = new Searcher();
	$request = trim(htmlspecialchars(urldecode($query), ENT_QUOTES));
	$request = preg_replace("/[\.,:;!\?\"'\+\(\)\[\}\^\$\*]/", "", $request);
	
	$result = $searcher -> searchInAllModelsAjax($request, false);
	
	$result = [
		'query' => $request,  
		'suggestions' => array_values($result),
		'data' => array_keys($result)
	];
	
	Http::responseJson($result);
}