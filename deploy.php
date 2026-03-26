<?php

namespace Deployer;

require 'recipe/laravel.php';
require 'contrib/crontab.php';

// Config

set('repository', 'git@github.com:veronikashklyarik/notification-app-server.git');

set('keep_releases', 3);

add('shared_files', ['.env']);
add('shared_dirs', ['storage']);
add('writable_dirs', ['bootstrap/cache']);
add('crontab:jobs', [
    '* * * * * cd {{current_path}} && {{bin/php}} artisan schedule:run >> /dev/null 2>&1',
]);

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

desc('Restart PHP-FPM to clear OPcache');
task('php:fpm:restart', function () {
    run('sudo systemctl restart php8.4-fpm');
});

// Hooks

after('artisan:migrate', 'npm:build');
after('deploy:failed', 'deploy:unlock');
after('deploy:success', 'crontab:sync');
after('deploy:success', 'php:fpm:restart');
