<?php

namespace Fitness\MSCommon\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class Cms
{
    public function getApi($path, $params = null)
    {
        $cmsApiEndpoint = env('CMS_API_ENDPOINT', false);

        $url = rtrim($cmsApiEndpoint, '/') . '/' . ltrim($path, '/');
        $envToken = Config::get('app.accessToken');
        if (($response = Http::withHeaders([
                'ACCESS-TOKEN' => $envToken
            ])->get($url, $params))->status() === 200) {
            $responseString = $response->body();
            return json_decode($responseString, true) ?? $responseString;
        }

        return null;
    }

    public function cachedGetAPI($path, $params = [])
    {
        if ((getenv('CMS_CACHE') === false)) {
            return $this->getApi($path, $params);
        }
        if ($cachedResponse = Cache::store('cms-caches')->get($path)) {
            return $cachedResponse;
        }

        $response = $this->getApi($path, $params);

        if ($response === null) return null;

        Cache::store('cms-caches')->set($path, $response);
        return $response;
    }
}
