<?php
if(Http::fromPost('action') === 'translit' && Http::fromPost('string') !== null)
	Http::responseText(I18n::translitUrl(Http::fromPost('string')));