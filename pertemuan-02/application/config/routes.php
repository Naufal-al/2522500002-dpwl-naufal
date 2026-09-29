<?php
$route = [];
$route['default_controller'] = 'home';
$route['info/(:any)'] = 'home/info/$1';
$route['dokter/(:num)'] = 'home/dokter/$1';