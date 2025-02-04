<?php
if(Http::fromPost('action') === 'translit' && Http::fromPost('string') !== null)
	Http::responseText(I18n::translitUrl(Http::fromPost('string')));

if(Http::fromPost('switch-off') === 'warnings')
{
	Session::set('hide-warnings', true);
	Http::responseText('1');
}