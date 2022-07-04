<?php

namespace Fitness\MSCommon\Traits;

use Fitness\MSCommon\Models\User;
use Fitness\MSCommon\Services\Subscription;

use Auth0\SDK\Helpers\JWKFetcher;
use Auth0\SDK\Helpers\Tokens\AsymmetricVerifier;
use Auth0\SDK\Helpers\Tokens\IdTokenVerifier;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

trait UsesAuth0
{
    public function getAuth0UserIdFromAccessToken(): ?string
    {
        $auth0 = \App::make('auth0');
        $accessToken = request()->bearerToken();
        $accessTokenInfo = $auth0->decodeJWT($accessToken);
        return $accessTokenInfo['sub'];
    }

    /**
     * @throws IDTokenVerificationException
     */
    protected function verifyIDToken($idToken)
    {
        $token_issuer  = 'https://'.getenv('AUTH0_DOMAIN').'/';
        $jwks = $this->retrieveAuth0JWKSKeys($token_issuer, $this->kidFromToken($idToken));
        $signature_verifier = new AsymmetricVerifier($jwks);

        $errorString = null;
        $tokenArray = null;
        $androidId = getenv('AUTH0_CLIENT_ID_ANDROID');
        $iOSId = getenv('AUTH0_CLIENT_ID_IOS');

        // Verify token as an Android user first
        try {
            $token_verifier = new IdTokenVerifier($token_issuer, $androidId, $signature_verifier);
            $tokenArray = $token_verifier->verify($idToken);
            // Bail early if we have an Android user.
            // This prevents us from ever trying the iOS JWT unnecessarily.
            if ($tokenArray !== null) {
                return $tokenArray;
            }
        } catch (\Exception $e) {
            $errorString = $e->getMessage();
        }

        // Verify token as an iOS user first
        try {
            $token_verifier = new IdTokenVerifier($token_issuer, $iOSId, $signature_verifier);
            $tokenArray = $token_verifier->verify($idToken);
        } catch (\Exception $e) {
            $errorString = $e->getMessage();
        }
        if ($tokenArray) {
            return $tokenArray;
        } else {
            throw new IDTokenVerificationException($errorString);
        }
    }

    /**
     * @return string
     * @throws IDTokenVerificationException
     */
    protected function kidFromToken($token): string
    {
        $parts = explode('.', $token);
        $header = json_decode(base64_decode($parts[0]), true);

        if (!isset($header["kid"])) {
            throw new IDTokenVerificationException("No KID found in idToken");
        }

        return $header["kid"];
    }

    protected function retrieveAuth0JWKSKeys($token_issuer, $tokenKID): array
    {
        if( $cached_jwks = Cache::store('file')->get('jwksKeys')) {
            $jwks = $cached_jwks;

            // If the kID associated with the login token doesn't exist in our cached jwks file this could be a
            // situation where our Auth0 Keys have been rotated, the cached jwks has the current, previous & next
            // signing keys in rotation. It's possible if the keys have been rotated a number of times in quick succession,
            // then we won't have the appropriate key to decode our token.
            //
            // Need to re-query jwks.json from token issuer to be sure. But we need to be careful, add a minimum of
            // 5 minutes between queries, so that we don't unnecessarily request that file if we're being spammed by
            // invalid tokens
            if (!array_key_exists($tokenKID, $jwks) && $this->canAttemptAuth0KeysQuery()) {
                $jwks = $this->queryAndCacheAuth0Keys($token_issuer);
            }
        } else {
            $jwks = $this->queryAndCacheAuth0Keys($token_issuer);
        }

        return $jwks;
    }

    protected function canAttemptAuth0KeysQuery(): bool
    {
        if ($lastQueryAttempt = Cache::store('file')->get('jwksKeysQueriedTime')) {
            $queriedDate = Carbon::parse($lastQueryAttempt["date"]);

            if ($queriedDate->diffInMinutes(now()) < 5) {
                return false;
            }
        }

        Cache::store('file')->put('jwksKeysQueriedTime', ["date" => now()], now()->addDay());
        return true;
    }

    protected function queryAndCacheAuth0Keys($token_issuer): array
    {
        $jwks_fetcher = new JWKFetcher();
        $jwks         = $jwks_fetcher->getKeys($token_issuer.'.well-known/jwks.json');
        // Cache the xc5 file for 1 day, to avoid querying this everytime someone logs in
        Cache::store('file')->put('jwksKeys', $jwks, now()->addDay());
        return $jwks;
    }

    /** 
     * @return array
     * @throws IDTokenVerificationException
     */
    function extractUserIdFromIdTokenAndAccessToken(): string
    {
        // This is a HACK and Security risk to workaround not having enough access to development tooling
        // to simulate.

        $forcedUserId = Config::get('app.forceLoggedInUserId');
        $environment = Config::get('app.env');
        if (in_array($environment, ['local', 'testing']) && $forcedUserId !== null && $forcedUserId !== '') {
            return Config::get('app.forceLoggedInUserId');
        }

        // ID Token
        $idToken = request()->header('X-IDToken');
        if (!$idToken) throw new IDTokenVerificationException();

        // payload of id token
        $tokenArray = $this->verifyIDToken($idToken);
        if (!$tokenArray) throw new IDTokenVerificationException('Unable to extract payload from ID token');

        // Access Token
        $auth0 = \App::make('auth0');
        $accessToken = request()->bearerToken();
        $accessTokenInfo = $auth0->decodeJWT($accessToken);

        // N.B - email sign ups through Auth0 web interface don't give any name except nickname.
        $firstName = $tokenArray['given_name'] ?? $tokenArray['nickname'];
        $lastName = $tokenArray['family_name'] ?? '';
        $email = $tokenArray['email'];

        try {
            $userId = $this->getUserIdByEmail($email);
        } catch (\Exception $e) {
            $errorString = $e->getMessage();
        }

        return $userId;
    }

    /**
     * @return ?int
     */
    private function getUserIdByEmail($email): ?int
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            return $user->id;
        }
    }
}