<?php

namespace Fitness\MSCommon\Traits;

use Fitness\MSCommon\Exceptions\EmailMissingException;
use Fitness\MSCommon\Exceptions\InvalidAccessTokenException;
use Fitness\MSCommon\Exceptions\MissingAccessTokenException;
use Fitness\MSCommon\Exceptions\MissingEnvVarException;
use Fitness\MSCommon\Exceptions\UserDoesNotExistException;
use Fitness\MSCommon\Models\User;
use Fitness\MSCommon\Models\AuthIds;
use Fitness\MSCommon\Exceptions\IDTokenVerificationException;

use Auth0\SDK\Helpers\JWKFetcher;
use Auth0\SDK\Helpers\Tokens\AsymmetricVerifier;
use Auth0\SDK\Helpers\Tokens\IdTokenVerifier;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

trait UsesAuth0
{

    /**
     * Gets the bearer token and decodes it, returns as array
     * @throws IDTokenVerificationException
    */
    function decodeAccessTokenToArray(): array
    {
        $auth0 = \App::make('auth0')->getSdk();
        $accessToken = request()->bearerToken();
        if (!isset($accessToken)) throw new IDTokenVerificationException();

        $decoded = $auth0->decode($accessToken);
        if (!isset($decoded)) throw new IDTokenVerificationException();

        return $decoded->toArray();
    }

    /**
     * Gets the payload from the bearer token, returns as array
     * @throws MissingAccessTokenException
     * @throws InvalidAccessTokenException
     */
    private function payloadFromBearerToken(): array
    {
        $accessToken = request()->bearerToken();
        if (!isset($accessToken)) throw new MissingAccessTokenException();
        $parts = explode('.', $accessToken);

        if (count($parts) != 3) throw new InvalidAccessTokenException();
        // Return the payload part of the token
        return json_decode(base64_decode($parts[1]), true);
    }

    /**
     * Returns the user associated with the access token, assumes the session
     * has already been logged in
     * @return string
     * @throws EmailMissingException
     * @throws UserDoesNotExistException
     * @throws MissingAccessTokenException
     * @throws MissingEnvVarException
     * @throws InvalidAccessTokenException
     */
    function extractUserFromAccessToken(): User
    {
        if ($forcedUserId = $this->forceUserId()) {
            return $forcedUserId;
        }

        $accessToken = $this->payloadFromBearerToken();
        $userDataNameSpace = env('AUTH0_ACCESS_TOKEN_NAMESPACE');

        if (!$userDataNameSpace) throw new MissingEnvVarException();

        if (!isset($accessToken[$userDataNameSpace . 'email'])) throw new EmailMissingException();

        $user = $this->getUserByEmail($accessToken[$userDataNameSpace . 'email']);
        if ($user) {
            return $user;
        } else {
            throw new UserDoesNotExistException();
        }
    }

    /**
     * Handles a new login session, creates/updates the user
     * and returns their user ID
     * @return string
     * @throws IDTokenVerificationException
     * @throws EmailMissingException
     * @throws MissingAccessTokenException
     * @throws MissingEnvVarException
     * @throws InvalidAccessTokenException
     */
    function userIdForNewLoginSession(): string
    {
        if ($forcedUserId = $this->forceUserId()) {
            return $forcedUserId;
        }

        // The API Gateway Authoriser has already ensured we have a valid
        // token, get the payload for user lookup
        $accessToken = $this->payloadFromBearerToken();
        // Name space used when injecting the user info into the access_token
        $userDataNameSpace = env('AUTH0_ACCESS_TOKEN_NAMESPACE');

        if (!$userDataNameSpace) throw new MissingEnvVarException();

        // N.B - email sign ups through Auth0 web interface don't give any name except nickname.
        $firstName = $accessToken[$userDataNameSpace . 'given_name'] ?? $accessToken[$userDataNameSpace . 'nickname'];
        $lastName = $accessToken[$userDataNameSpace . 'family_name'] ?? '';

        if (!isset($accessToken[$userDataNameSpace . 'email'])) throw new EmailMissingException();
        $email = $accessToken[$userDataNameSpace . 'email'];

        $user = $this->getUserByEmail($email);
        if (!$user) {
            $userId = $this->insertUser(
                $firstName,
                $lastName,
                $email,
                $accessToken[$userDataNameSpace . 'picture'],
                $accessToken['sub'],
                $accessToken['iss']
            );
        } else {
            $userId = $user->id;

            $this->updateUser($user,
                $firstName,
                $lastName,
                $accessToken[$userDataNameSpace . 'picture'],
                $accessToken['sub'],
                $accessToken['iss']
            );
        }

        return $userId;
    }

    protected function forceUserId(): ?string
    {
        // This is a HACK and Security risk to workaround not having enough access to development tooling
        // to simulate.
        $forcedUserId = Config::get('app.forceLoggedInUserId');
        $environment = Config::get('app.env');
        if (in_array($environment, ['local', 'testing']) && $forcedUserId !== null && $forcedUserId !== '') {
            return Config::get('app.forceLoggedInUserId');
        }

        return null;
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