<?php

namespace Fitness\MSCommon\Traits;

use Illuminate\Support\Facades\Storage;
use Fitness\MSCommon\Services\Cms;

trait UsesCMSAPI
{
    public function getCmsApiV1($endpoint)
    {
        if (strpos($_SERVER['PHP_SELF'],'phpunit') !== false) {
            $fixture = Storage::disk('cms-mocks')->get($endpoint . '.json');
            return json_decode($fixture, true);
        }

        /** @var Cms $cmsService */
        $cmsService = app(Cms::class);

        return $cmsService->getApi($endpoint);
    }
}