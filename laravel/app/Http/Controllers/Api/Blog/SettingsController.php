<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Blog\GeneralBlogSettingsResource;
use App\Settings\GeneralBlogSettings;

class SettingsController extends Controller
{
    /**
     * GET /api/blog/settings
     *
     * Returns the current blog settings (translatable fields resolved to request locale).
     */
    public function show(): GeneralBlogSettingsResource
    {
        $settings = app(GeneralBlogSettings::class);

        return new GeneralBlogSettingsResource($settings);
    }
}
