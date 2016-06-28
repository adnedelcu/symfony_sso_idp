<?php

namespace AppBundle\Controller;

use Everlution\Redlock\Model\Lock;
use Everlution\Redlock\Model\LockType;

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
        $lockManagerService = $this->get('app.lock_manager');
        $lockManager = $lockManagerService->getLockManager();
        $lock = null;

        /** @var \Krtv\SingleSignOn\Manager\OneTimePasswordManagerInterface */
        $otpManager = $this->get('sso_identity_provider.otp_manager');

        $password = str_replace(' ', '+', $request->query->get('_otp'));

        try {
            $lock = $lockManagerService->lockOrWait($lockManager, LockType::EXCLUSIVE, sprintf('otp_%d', $password), 10);

            if ($lock === null) {
                throw new \Exception(sprintf('Can\'t acquire lock after 10 attempts for OTP %d.', $password));
            }

            $otp = $otpManager->get($password);

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
            $lockManager->releaseLock($lock);
            $lock = null;
        } catch (\Exception $e) {
            throw $e;
        } finally {
            if ($lock instanceof Lock) {
                $lockManager->releaseLock($lock);
            }
        }

        return new JsonResponse($response);
    }
}
