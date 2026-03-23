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

// Tasks

desc('Install & build npm packages');
task('npm:build', function () {
    run('cd {{release_path}} && npm ci && npm run build');
});

// Hooks

after('artisan:migrate', 'npm:build');
after('deploy:failed', 'deploy:unlock');
