<?php

namespace App\Modules\System;

\App\Core\Auth::requireLogin();
$router->add('GET', '/lang/{lang}', function ($lang) {
    \App\Core\Lang::set($lang);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?: \App\Core\Auth::getBaseUrl()));
    exit;
});
