<?php
namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:veronikashklyarik/notification-app-server.git');

add('shared_files', ['.env']);
add('shared_dirs', ['storage']);
add('writable_dirs', []);

// Hosts

host('64.227.122.5')
    ->set('port', 29635)
    ->set('remote_user', 'vshklyarik')
    ->set('domain', 'notifyr.grinik.pl')
    ->set('public_path', 'public')
    ->set('branch', 'main')
    ->set('http_user', 'www-data')
    ->set('writable_mode', 'chmod')
    ->set('writable_chmod_mode', '0775')
    ->set('deploy_path', '/var/www/notifyr');

// Hooks

after('deploy:failed', 'deploy:unlock');
