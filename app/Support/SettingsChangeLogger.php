<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class SettingsChangeLogger
{
    /**
     * Log a single setting change for a user.
     *
     * @param  mixed  $oldValue
     * @param  mixed  $newValue
     */
    public static function logChange(Authenticatable $user, ?string $settingKey, ?string $settingPage, $oldValue = null, $newValue = null): void
    {
        try {
            DB::table('user_settings_changes')->insert([
                'user_id' => $user->getAuthIdentifier(),
                'setting_key' => $settingKey,
                'setting_page' => $settingPage,
                'old_value' => is_null($oldValue) ? null : (is_scalar($oldValue) ? (string) $oldValue : json_encode($oldValue)),
                'new_value' => is_null($newValue) ? null : (is_scalar($newValue) ? (string) $newValue : json_encode($newValue)),
                'changed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Don't let logging break normal flow. Swallow silently.
            // Could add optional logging to system log if necessary.
            logger()->warning('Failed to log settings change: '.$e->getMessage());
        }
    }
}
