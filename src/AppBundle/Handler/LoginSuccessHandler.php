<?php

namespace AppBundle\Handler;

use Krtv\Bundle\SingleSignOnIdentityProviderBundle\Manager\ServiceManager;
use Krtv\Bundle\SingleSignOnIdentityProviderBundle\Manager\ServiceProviderInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Class LoginSuccessHandler.
 */
class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * @var ServiceManager
     */
    private $serviceManager;
    /**
     * @var UriSigner
     */
    private $uriSigner;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var Router
     */
    private $router;
    /**
     * @param ServiceManager $serviceManager
     * @param UriSigner $signer
     * @param SessionInterface $session
     * @param Router $router
     */
    public function __construct(
        ServiceManager $serviceManager,
        UriSigner $signer,
        SessionInterface $session,
        Router $router
    ) {
        $this->serviceManager = $serviceManager;
        $this->uriSigner = $signer;
        $this->session = $session;
        $this->router = $router;
    }
    /**
     * @param Request $request
     * @param TokenInterface $token
     *
     * @return RedirectResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $redirectUrl = $this->session->get('_security.main.target_path', '/');

        if ($request->query->has('_target_path')) {
            if ($this->uriSigner->check($request->query->get('_target_path'))) {
                $redirectUrl = $request->query->get('_target_path');
            }
        }

        if (strpos($redirectUrl, '/sso/login') === false) {
            $targetService = $this->serviceManager->getSessionService();

            if ($targetService != null) {
                $redirectUrl = $this->getSsoWrappedUrl($token, $targetService, $redirectUrl);
            } else {
                $redirectUrl = $this->router->generate('_passport_dashboard_index');
            }
        }

        $this->serviceManager->clear();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'status' => true,
                'location' => $redirectUrl,
            ]);
        }

        return new RedirectResponse($redirectUrl);
    }
    /**
     * @param TokenInterface $token
     * @param string $targetService
     * @param string $redirectUrl
     *
     * @return string
     */
    protected function getSsoWrappedUrl(TokenInterface $token, $targetService, $redirectUrl)
    {
        /** @var $serviceManager ServiceProviderInterface */
        $serviceManager = $this->serviceManager->getServiceManager($targetService);
        $owner = $token->getUser();

        $wrappedSsoUrl = $this->router->generate('sso_login_path', [
            '_target_path' => $serviceManager->getOTPValidationUrl([
                '_target_path' => $redirectUrl,
            ]),
            'service' => $targetService,
        ], Router::ABSOLUTE_URL);

        return $this->uriSigner->sign($wrappedSsoUrl);
    }
}
