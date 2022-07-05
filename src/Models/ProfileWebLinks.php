<?php

namespace Fitness\MSCommon\Models;

class ProfileWebLinks
{
    public string $termsOfUseUrl;
    public string $privacyPolicyUrl;
    public string $faqUrl;
    public string $shareUrl;

    public function __construct(string $termsOfUseUrl, string $privacyPolicyUrl, string $faqUrl, string $shareUrl) {
        $this->termsOfUseUrl = $termsOfUseUrl;
        $this->privacyPolicyUrl = $privacyPolicyUrl;
        $this->faqUrl = $faqUrl;
        $this->shareUrl = $shareUrl;
    }
}