// Using Laravel Mix to build assets, to learn more about it: https://laravel.com/docs/8.x/mix#introduction
let mix = require('laravel-mix');

mix.js('assets/src/js/rfw-admin.js', 'assets/dist/js/rfw-admin.js');
mix.js('assets/src/js/rfw-client.js', 'assets/dist/js/rfw-client.js');
