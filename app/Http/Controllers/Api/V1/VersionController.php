<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VersionController extends Controller
{
    /**
     * Checks the app version
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'version' => 'required|string',
            'platform' => 'required|string|in:ios,android',
        ]);

        $currentVersion = $request->input('version');
        $platform = $request->input('platform');

        $versionInfo = DB::table('app_versions')
            ->where('platform', $platform)
            ->first();

        if (! $versionInfo) {
            return response()->json([
                'current_version' => $currentVersion,
                'minimum_version' => '1.0.0',
                'recommended_version' => '1.0.0',
                'latest_version' => '1.0.0',
                'update_required' => false,
                'force_update' => false,
                'message' => 'Your app is up to date',
                'download_url' => null,
            ]);
        }

        $updateRequired = version_compare($currentVersion, $versionInfo->recommended_version, '<');
        $forceUpdate = version_compare($currentVersion, $versionInfo->minimum_version, '<') || $versionInfo->force_update;

        return response()->json([
            'current_version' => $currentVersion,
            'minimum_version' => $versionInfo->minimum_version,
            'recommended_version' => $versionInfo->recommended_version,
            'latest_version' => $versionInfo->latest_version,
            'update_required' => $updateRequired,
            'force_update' => $forceUpdate,
            'message' => $versionInfo->message,
            'download_url' => $versionInfo->download_url,
        ]);
    }
}
