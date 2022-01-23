<?php

// src/AppBundle/Controller/SecurityController.php

namespace Grypho\SecurityBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private function error2german($text)
    {
         if (!$text)
            return '';
        if (is_a($text, 'Symfony\Component\Security\Core\Exception\BadCredentialsException'))
            return 'Benutzername/Passwort falsch.';
        throw new \Exception('|'.$text.'|');
            return $text;
    }

    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request, AuthenticationUtils $authenticationUtils)
    {
        // Attention! Application_utils are available in symfony_2.6+
        // $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $this->error2german($authenticationUtils->getLastAuthenticationError());

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            '@GryphoSecurity/Security/login.html.twig',
            [
                // last username entered by the user
                'last_username' => $lastUsername,
                'error' => $error,
            ]
        );
    }

    /**
     * @Route("/login_check", name="login_check")
     */
    public function loginCheckAction()
    {
        // this controller will not be executed,
        // as the route is handled by the Security system
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
        // this controller will not be executed,
        // as the route is handled by the Security system
    }
}
