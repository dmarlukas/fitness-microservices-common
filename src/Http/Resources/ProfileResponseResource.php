<?php

namespace Fitness\MSCommon\Http\Resources;


use App\Services\Subscription;
use Fitness\MSCommon\Models\ProfileWebLinks;
use Fitness\MSCommon\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class ProfileResponseResource extends JsonResource
{
    public static $wrap = null;

    protected User $user;
    protected array $subscriptionInfo;
    protected ProfileWebLinks $links;

    public function __construct(User $user, $subscriptionInfo, ProfileWebLinks $links)
    {
        $this->user = $user;
        $this->subscriptionInfo = $subscriptionInfo;
        $this->links = $links;
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $user = $this->user;

        $data['user'] = [
            "userId" => $user->uuid,
            "firstName" => $user->first_name,
            "lastName" => $user->last_name,
            "email" => $user->email,
            "profilePictureUrl" => $user->profile_picture_url
        ];

        $data['subscription'] = $this->subscriptionInfo;
        $data['termsOfUseUrl'] = $this->links->termsOfUseUrl;
        $data['privacyPolicyUrl'] = $this->links->privacyPolicyUrl;
        $data['faqUrl'] = $this->links->faqUrl;
        $data['share']['link'] = 'https://link.dripfitness.app/invite-friends';
        $data['share']['title'] = __("Invite Friends");
        $data['share']['shareUrl'] = $this->links->shareUrl;
        $data['share']['shareText'] = __("Try the Drip Fitness App to train with your friends");
        $data['share']['type'] = "shareButton";

        return $data;
    }
}
