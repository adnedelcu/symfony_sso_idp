<?php

namespace AppBundle\ServiceProviders;

use Krtv\Bundle\SingleSignOnIdentityProviderBundle\Manager\ServiceProviderInterface;

/**
* CoreServiceProvider
*/
class CoreServiceProvider implements ServiceProviderInterface
{
    public function getName()
    {
        return 'core_sp';
    }

    public function getServiceIndexUrl($parameters = [])
    {
        return 'http://sso-demo.cos/';
    }

    public function getServiceLogoutUrl($parameters = [])
    {
        return 'http://sso-demo.cos/en/logout';
    }
}
