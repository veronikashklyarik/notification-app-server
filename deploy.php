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

desc('Upload frontend assets built in CI');
task('build:upload', function () {
    // public/build is produced by the "Build frontend assets" step in deploy.yml
    if (! file_exists(__DIR__.'/public/build/manifest.json')) {
        throw error('public/build/manifest.json is missing — run "npm ci && npm run build" before deploying.');
    }

    upload(__DIR__.'/public/build/', '{{release_path}}/public/build/');

    // Fail before deploy:publish switches `current`, so a broken upload never goes live
    run('test -f {{release_path}}/public/build/manifest.json');
});

desc('Restart PHP-FPM to clear OPcache');
task('php:fpm:restart', function () {
    run('sudo systemctl restart php8.4-fpm');
});

desc('Restart the queue worker via supervisorctl');
task('queue:restart', function () {
    run('cd {{release_path}} && {{bin/php}} artisan queue:restart');
});

// Hooks

after('deploy:vendors', 'build:upload');
after('deploy:failed', 'deploy:unlock');
after('deploy:success', 'crontab:sync');
after('deploy:success', 'php:fpm:restart');
after('deploy:success', 'queue:restart');
