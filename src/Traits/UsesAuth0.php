<?php

namespace Fitness\MSCommon\Traits;

use Fitness\MSCommon\Models\User;
use Fitness\MSCommon\Models\AuthIds;
use Fitness\MSCommon\Services\Subscription;
use Fitness\MSCommon\Exceptions\IDTokenVerificationException;

use Auth0\SDK\Helpers\JWKFetcher;
use Auth0\SDK\Helpers\Tokens\AsymmetricVerifier;
use Auth0\SDK\Helpers\Tokens\IdTokenVerifier;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

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
        $idTokenString = request()->header('X-IDToken');
        if (!$idTokenString) throw new IDTokenVerificationException();


        // Access Token
        $auth0 = \App::make('auth0')->getSdk();
        $token = $auth0->decode($idTokenString);
        if (!$token) throw new IDTokenVerificationException('Unable to extract payload from ID token');

        $tokenArray = $token->toArray();
        // N.B - email sign ups through Auth0 web interface don't give any name except nickname.
        $firstName = $tokenArray['given_name'] ?? $tokenArray['nickname'];
        $lastName = $tokenArray['family_name'] ?? '';

        if (!isset($tokenArray['email'])) throw new EmailMissingException();
        $email = $tokenArray['email'];

        $user = $this->getUserByEmail($email);
        if (!$user) {
            $userId = $this->insertUser(
                $firstName,
                $lastName,
                $email,
                $tokenArray['picture'],
                $tokenArray['sub'],
                $tokenArray['iss']
            );
        } else {
            $userId = $user->id;
            $requestPath = request()->getPathInfo();
            // TODO: Update the user info only on the initial login request
            // Cuts down on DB activity. So we're not doing this on
            // almost every request.
            // if ($requestPath == Auth0LoginController::LOGIN_PATH) {
            //     $this->updateUser($user,
            //         $firstName,
            //         $lastName,
            //         $tokenArray['picture'],
            //         $tokenArray['sub'],
            //         $tokenArray['iss']
            //     );
            // }
        }

        return $userId;
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
     * @return ?User
     */
    private function getUserByEmail($email): ?User
    {
        return User::whereEmail($email)->first();
    }

        /**
     * @param $firstName // e.g - Jane
     * @param $lastName // e.g - Blogs
     * @param $email // e.g - jane@blog.com
     * @param $profilePictureUrl // e.g - https://my-social-service.com/image.jpg
     * @param $providerId // e.g - google-oauth2|109992762175512512345 or auth0|1231091230912312311
     * @param $issuer // e.g - https://drip-fitness-dev.au.auth0.com/
     * @return array
     */
    protected function insertUser(
        $firstName,
        $lastName,
        $email,
        $profilePictureUrl,
        $providerId,
        $issuer
    ): string
    {
        $user = new User;
        $user->uuid = Str::uuid()->toString();
        $user->email = $email;
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->profile_picture_url = $profilePictureUrl;
        $user->save();

        $this->insertAuthIDs($user->id, $providerId, $issuer);
        return $user->id;
    }

    protected function insertAuthIDs($user_id, $providerId, $issuer)
    {
        $authId = new AuthIds;
        $authId->provider_id = $providerId;
        $authId->issuer = $issuer;
        $authId->user_id = $user_id;
        $authId->save();
    }
}