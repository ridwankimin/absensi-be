<?php
defined('BASEPATH') or exit('No direct script access allowed');
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

$route['login']['POST'] = 'auth/login';
// $route['api/perizinan']['post'] = 'ApiPerizinan/hasil';
$route['perizinan']['GET'] = 'perizinan/index';
$route['perizinan']['POST'] = 'perizinan/index';
// $route['perizinan/update/(:any)']['POST'] = 'perizinan/update/$1';
$route['auth/reset_password']['post'] = 'auth/reset_password';
