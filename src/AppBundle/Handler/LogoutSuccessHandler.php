<?php

namespace AppBundle\Handler;

use Krtv\Bundle\SingleSignOnIdentityProviderBundle\Manager\ServiceManager;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
* Class LogoutSuccessHandler
*/
class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    /**
     * @var ServiceManager;
     */
    protected $serviceManager;

    /**
     * @var UriSigner
     */
    protected $uriSigner;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var Router
     */
    protected $router;

    /**
     * Constructor
     *
     * @param ServiceManager   $serviceManager
     * @param UriSigner        $uriSigner
     * @param SessionInterface $session
     * @param Router           $router
     */
    public function __construct(
        ServiceManager $serviceManager,
        UriSigner $uriSigner,
        SessionInterface $session,
        Router $router
    ) {
        $this->serviceManager = $serviceManager;
        $this->uriSigner = $uriSigner;
        $this->session = $session;
        $this->router = $router;
    }

    /**
     * Logout success handler
     *
     * @param  Request        $request
     *
     * @return RedirectResponse|JsonResponse
     */
    public function onLogoutSuccess(Request $request)
    {
        $redirectUrl = $this->session->get('_security.main.target_path', '/');

        if ($request->query->has('_target_path')) {
            if ($this->uriSigner->check($request->query->get('_target_path'))) {
                $redirectUrl = $request->query->get('_target_path');
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
}
