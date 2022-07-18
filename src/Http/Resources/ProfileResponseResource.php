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

        $data['termsOfUseUrl'] = Config::get('app.termsOfUseUrl');
        $data['privacyPolicyUrl'] = Config::get('app.privacyPolicyUrl');
        $data['faqUrl'] = Config::get('app.faqUrl');
        $data['share']['link'] = 'https://link.dripfitness.app/invite-friends';
        $data['share']['title'] = __("Invite Friends");
        $data['share']['shareUrl'] = Config::get('app.websiteUrl');
        $data['share']['shareText'] = __("Try the Drip Fitness App to train with your friends");
        $data['share']['type'] = "shareButton";

        return $data;
    }
}
