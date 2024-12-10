<?php
$region = Registry::get('Region');
$maintenance = Registry::get('UnderMaintenance');
$message = is_string($maintenance) ? $maintenance : '';

if($message === '')
    if($region === 'ru')
        $message = 'На сайте ведутся технические работы.<br>Он скоро снова будет доступен.';
    else
        $message = 'The website is currently undergoing maintenance.<br>It will be back online soon.';
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
		<meta name="robots" content="noindex,nofollow" />
        <title><?php echo explode('<br>', $message)[0]; ?></title>
        
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@600&display=swap" rel="stylesheet">

        <style type="text/css">
            *{margin: 0; padding: 0; box-sizing: border-box; font-family: 'Roboto Condensed';}
            html{height: 100%;}
            body{display: flex; justify-content: center; align-items: center; flex-direction: column; gap: 30px;             
            background: radial-gradient(circle, rgba(251,234,234,1) 0%, rgba(241,232,232,1) 100%);
            background: rgb(251,234,234); height: 100%;}
            svg{width: 100px;}
            div{font-weight: 600; text-align: center; color: #555; font-size: 18px; line-height: 22px; margin: 0 2%;}
        </style>
	</head>
	<body>
        <div><?php echo $message; ?></div>
        <svg fill="#858f9c" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
            <!--!Font Awesome Free 6.7.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
            <path d="M78.6 5C69.1-2.4 55.6-1.5 47 7L7 47c-8.5 8.5-9.4 22-2.1 31.6l80 104c4.5 5.9 11.6 9.4 19 9.4l54.1 0 109 109c-14.7 29-10 65.4 14.3 89.6l112 112c12.5 12.5 32.8 12.5 45.3 0l64-64c12.5-12.5 12.5-32.8 0-45.3l-112-112c-24.2-24.2-60.6-29-89.6-14.3l-109-109 0-54.1c0-7.5-3.5-14.5-9.4-19L78.6 5zM19.9 396.1C7.2 408.8 0 426.1 0 444.1C0 481.6 30.4 512 67.9 512c18 0 35.3-7.2 48-19.9L233.7 374.3c-7.8-20.9-9-43.6-3.6-65.1l-61.7-61.7L19.9 396.1zM512 144c0-10.5-1.1-20.7-3.2-30.5c-2.4-11.2-16.1-14.1-24.2-6l-63.9 63.9c-3 3-7.1 4.7-11.3 4.7L352 176c-8.8 0-16-7.2-16-16l0-57.4c0-4.2 1.7-8.3 4.7-11.3l63.9-63.9c8.1-8.1 5.2-21.8-6-24.2C388.7 1.1 378.5 0 368 0C288.5 0 224 64.5 224 144l0 .8 85.3 85.3c36-9.1 75.8 .5 104 28.7L429 274.5c49-23 83-72.8 83-130.5zM56 432a24 24 0 1 1 48 0 24 24 0 1 1 -48 0z"/>
        </svg>
	</body>
</html>        