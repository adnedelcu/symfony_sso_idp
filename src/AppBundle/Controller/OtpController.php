<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/internal/v1/sso")
 */
class OtpController extends Controller
{
    /**
     * Index
     *
     * @Route("/", name="sso_otp")
     * @Method("GET")
     *
     * @param  Request $request
     *
     * @return JsonResponse
     */
    public function indexAction(Request $request)
    {
        /** @var \Krtv\SingleSignOn\Manager\OneTimePasswordManagerInterface */
        $otpManager = $this->get('sso_identity_provider.otp_manager');

        $pass = str_replace(' ', '+', $request->query->get('_otp'));

        /** @var \Krtv\SingleSignOn\Model\OneTimePasswordInterface */
        $otp = $otpManager->get($pass);

        $response = [
            'data' => [
                'created_at' => $otp->getCreated()->format('r'),
                'hash' => $otp->getHash(),
                'password' => $otp->getPassword(),
                'is_used' => $otp->getUsed(),
            ],
        ];

        return new JsonResponse($response);
    }
}
