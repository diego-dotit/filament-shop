<?php

namespace App\Http\Controllers\Api\General;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\General\GeneralSettingsResource;
use App\Settings\GeneralSettings;

class SettingsController extends Controller
{
    /**
     * GET /api/settings
     *
     * Returns the current general site settings.
     */
    public function show(): GeneralSettingsResource
    {
        $settings = app(GeneralSettings::class);

        return new GeneralSettingsResource($settings);
    }
}
