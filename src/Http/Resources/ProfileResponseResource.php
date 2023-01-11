<?php

namespace Fitness\MSCommon\Http\Resources;


use Fitness\MSCommon\Services\Subscription;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class ProfileResponseResource extends JsonResource
{
    public static $wrap = null;

    public string $providerId;

    public function __construct($resource, string $providerId = null)
    {
        parent::__construct($resource);
        $this->providerId = $providerId;
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request, string $providerId = null)
    {
        $user = $this->resource;

        $data['user'] = [
            "id" => $user->uuid,
            "firstName" => $user->first_name,
            "lastName" => $user->last_name,
            "email" => $user->email,
            "profilePictureUrl" => $user->profile_picture_url,
            "isOnboarded" => boolval($user->is_onboarded),
            "goal" => $user->goal,
            "targetArea" => $user->targetArea,
            "enrolledProgram" => $user->enrolled_program,
        ];

        $userId = $user->id;
        $data['subscription'] = Subscription::fetchSubscriptionArray($userId);
        $data['settings'] = $this->settingsLinks();

        $data['share']['link'] = 'https://link.templfitness.app/invite-friends';
        $data['share']['title'] = __("Invite Friends");
        $data['share']['shareUrl'] = env('WEBSITE_SHARE_LINK', '');
        $data['share']['shareText'] = __("Share text");
        $data['share']['type'] = "shareButton";

        $data['signinMethod'] = null;
        if ($this->providerId !== null) {
            $id = strtolower($this->providerId);
            // Either apple, google, usernamepassword
            if (substr( $id, 0, 5 ) === 'apple') {
                $data['signinMethod'] = 'apple';
            } elseif (substr( $id, 0, 6 ) === 'google') {
                $data['signinMethod'] = 'google';
            } elseif (substr( $id, 0, 5 ) === 'auth0') {
                $data['signinMethod'] = 'usernamepassword';
            }
        }

        return $data;
    }

    private function settingsLinks(): array
    {
        $universalUrlBase = env('UNIVERSAL_BASE_URL', false);
        return [
            [
                "title" => "Shop Apparel",
                "type" => "callToActionButton",
                "link" => $universalUrlBase ."shop-apparel"
            ],
            [
                "title" => "Push Notifications",
                "type" => "callToActionButton",
                "link" => $universalUrlBase . "push-notifications"
            ],
            [
                "title" => "Terms of Use",
                "type" => "callToActionButton",
                "link" => env('TERMS_OF_USE_LINK', '')
            ],
            [
                "title" => "Privacy Policy",
                "type" => "callToActionButton",
                "link" => env('PRIVACY_POLICY_LINK', '')
            ],
            [
                "title" => "Frequently Asked Questions",
                "type" => "callToActionButton",
                "link" => env('FAQ_LINK', '')
            ],
            [
                "title" => "Contact Us",
                "type" => "callToActionButton",
                "link" => $universalUrlBase . "contact-us"
            ],
            [
                "title" => "Log Out",
                "type" => "callToActionButton",
                "link" => $universalUrlBase . "log-out"
            ]
        ];
    }
}
