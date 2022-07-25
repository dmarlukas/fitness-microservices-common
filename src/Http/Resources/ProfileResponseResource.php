<?php

namespace Fitness\MSCommon\Http\Resources;


use Fitness\MSCommon\Services\Subscription;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class ProfileResponseResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $user = $this->resource;

        $data['user'] = [
            "userId" => $user->uuid,
            "firstName" => $user->first_name,
            "lastName" => $user->last_name,
            "email" => $user->email,
            "profilePictureUrl" => $user->profile_picture_url
        ];
        $userId = $user->id;
        $data['subscription'] = Subscription::fetchSubscriptionArray($userId);

        $data['termsOfUseUrl'] = env('TERMS_OF_USE_LINK', '');
        $data['privacyPolicyUrl'] = env('PRIVACY_POLICY_LINK', '');
        $data['faqUrl'] = env('FAQ_LINK', '');
        $data['share']['link'] = 'https://link.templfitness.app/invite-friends';
        $data['share']['title'] = __("Invite Friends");
        $data['share']['shareUrl'] = env('WEBSITE_SHARE_LINK', '');
        $data['share']['shareText'] = __("Share text");
        $data['share']['type'] = "shareButton";

        return $data;
    }
}
