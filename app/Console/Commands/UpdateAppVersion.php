<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:version:update
    {platform : Platform to update (ios or android)}
    {version : New version string, e.g. 1.2.0}
    {--minimum : Also set this as the minimum required version (forces update for all older clients)}
    {--force : Set force_update flag — blocks the app until user updates}
    {--message= : Custom message shown in the update popup}
    {--url= : Download URL (App Store / Play Store link)}
')]
#[Description('Update the app version configuration for a platform')]
class UpdateAppVersion extends Command
{
    public function handle(): int
    {
        $platform = $this->argument('platform');
        $version = $this->argument('version');

        if (! in_array($platform, ['ios', 'android'])) {
            $this->error("Platform must be 'ios' or 'android'. Got: {$platform}");

            return self::FAILURE;
        }

        if (! preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $this->error("Version must follow semver format (e.g. 1.2.0). Got: {$version}");

            return self::FAILURE;
        }

        $existing = DB::table('app_versions')->where('platform', $platform)->first();

        $minimumVersion = $this->option('minimum')
            ? $version
            : ($existing->minimum_version ?? '1.0.0');

        $forceUpdate = $this->option('force');

        $message = $this->option('message') ?? ($existing->message ?? null);
        $downloadUrl = $this->option('url') ?? ($existing->download_url ?? null);

        DB::table('app_versions')->updateOrInsert(
            ['platform' => $platform],
            [
                'minimum_version' => $minimumVersion,
                'recommended_version' => $version,
                'latest_version' => $version,
                'force_update' => $forceUpdate,
                'message' => $message,
                'download_url' => $downloadUrl,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->info("Version updated for <comment>{$platform}</comment>:");
        $this->table(
            ['Field', 'Value'],
            [
                ['Platform', $platform],
                ['Latest version', $version],
                ['Recommended version', $version],
                ['Minimum version', $minimumVersion],
                ['Force update', $forceUpdate ? '<fg=red>YES</>' : 'No'],
                ['Message', $message ?? '—'],
                ['Download URL', $downloadUrl ?? '—'],
            ]
        );

        return self::SUCCESS;
    }
}
