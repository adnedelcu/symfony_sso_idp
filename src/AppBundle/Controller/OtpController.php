<?php

namespace AppBundle\Controller;

use Krtv\Bundle\SingleSignOnIdentityProviderBundle\Entity\OneTimePassword;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class OtpController extends Controller
{
    /**
     * Index
     *
     * @Route("/internal/v1/sso", name="sso_otp")
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

        if (!($otp instanceof OneTimePassword) || $otp->getUsed() === true) {
            throw new BadRequestHttpException('Invalid OTP password');
        }

        $response = [
            'data' => [
                'created_at' => $otp->getCreated()->format('r'),
                'hash' => $otp->getHash(),
                'password' => $otp->getPassword(),
                'is_used' => $otp->getUsed(),
            ],
        ];

        $otpManager->invalidate($otp);

        return new JsonResponse($response);
    }
}
