<?php

namespace Grypho\SecurityBundle\Controller;

use Grypho\SecurityBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Description of LoginWithFacebook.
 *
 * @see https://developers.facebook.com/docs/php/howto/example_facebook_login?locale=de_DE
 * @see http://stackoverflow.com/questions/15221230/how-to-insert-a-controller-in-twig-with-render-in-symfony-2-2
 */
class LoginWithFacebookController extends AbstractController
{
    /**
     * @Route("/aaa", name="nix")
     */
    public function signupAction(Request $request)
    {
        $user = new \Grypho\SecurityBundle\Entity\User();
    }

    private function getFacebookInstance(Request $request)
    {
        if(getenv('FACEBOOK_APPID')===false)
            return null;

        $fbconfig = $request->get('facebook'); // get grypho_security.facebook ; see GryphoSecurityExtension.php
        $fb = new \Facebook\Facebook([
            'app_id' => getenv('FACEBOOK_APPID'), // Replace {app-id} with your app id
            'app_secret' => getenv('FACEBOOK_SECRET'),
            'default_graph_version' => 'v2.2',
        ]);

        if($fb === null)
            throw new \Exception('Cannot create facebook instance');
            
            
        return $fb;
    }

    /**
     * @Route("/fb_login_button", name="facebook_login_button")
     */
    public function renderLoginButtonAction(Request $request)
    {
    return new \Symfony\Component\HttpFoundation\Response("");
        $this->get('session')->set('dummy', '1'); // Erzwingen, dass eine Session gestartet wird, sonstr gibts eine CSRF Exception von Facebook.

        $fb = $this->getFacebookInstance($request);
        $helper = $fb->getRedirectLoginHelper();
        $url = $this->generateUrl('facebook_login_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $permissions = ['public_profile'];
        $loginUrl = $helper->getLoginUrl($url, $permissions);

        // Ergebnis Rendern
        return $this->render(
            '@GryphoSecurity/LoginWithFacebook/button.html.twig',
            [
                'login_url' => $loginUrl,
            ]
        );
    }

    private function getAndVerifyAccessToken(\Facebook\Facebook $fb)
    {
        $helper = $fb->getRedirectLoginHelper();

        try {
          $accessToken = $helper->getAccessToken();
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
          // When Graph returns an error
          echo 'Graph returned an error: '.$e->getMessage();
          exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
          // When validation fails or other local issues
          echo 'Facebook SDK returned an error: '.$e->getMessage();
          exit;
        }

        if (!isset($accessToken)) {
          if ($helper->getError()) {
            header('HTTP/1.0 401 Unauthorized');
            echo 'Error: '.$helper->getError()."\n";
            echo 'Error Code: '.$helper->getErrorCode()."\n";
            echo 'Error Reason: '.$helper->getErrorReason()."\n";
            echo 'Error Description: '.$helper->getErrorDescription()."\n";
          } else {
            header('HTTP/1.0 400 Bad Request');
            echo 'Bad request';
          }
          exit;
        }

        return $accessToken;
    }

    private function getProfile(\Facebook\Facebook $fb, $token)
    {
        try {
          // Returns a `Facebook\FacebookResponse` object
          $response = $fb->get('/me?fields=id,name', $token);
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
          echo 'Graph returned an error: '.$e->getMessage();
          exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
          echo 'Facebook SDK returned an error: '.$e->getMessage();
          exit;
        }

        $user = $response->getGraphUser();

        return $user;
    }

    /**
     * @Route("/fb-callback", name="facebook_login_callback")
     */
    public function callbackAction(Request $request)
    {
        $this->get('session')->set('dummy', '1'); // Erzwingen, dass eine Session gestartet wird, sonstr gibts eine CSRF Exception von Facebook.

        $fb = $this->getFacebookInstance();

        $accessToken = $this->getAndVerifyAccessToken($fb);

        // Logged in
        echo '<h3>Access Token</h3>';
        var_dump($accessToken->getValue());

        // The OAuth 2.0 client handler helps us manage access tokens
        $oAuth2Client = $fb->getOAuth2Client();

        // Get the access token metadata from /debug_token
        $tokenMetadata = $oAuth2Client->debugToken($accessToken);
        echo '<h3>Metadata</h3>';
        var_dump($tokenMetadata);

        // Validation (these will throw FacebookSDKException's when they fail)
        $tokenMetadata->validateAppId(getenv('FACEBOOK_APPID')); // Replace {app-id} with your app id

        // If you know the user ID this access token belongs to, you can validate it here
        //$tokenMetadata->validateUserId('123');
        $tokenMetadata->validateExpiration();

        if (!$accessToken->isLongLived()) {
          // Exchanges a short-lived access token for a long-lived one
          try {
            $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
          } catch (Facebook\Exceptions\FacebookSDKException $e) {
            echo '<p>Error getting long-lived access token: '.$helper->getMessage()."</p>\n\n";
            exit;
          }
        }

        // Hier sind wir eingeloggt.

        // User in DB erzeugen/holen
        $user_id = $tokenMetadata->getUserId()."@facebook.com";
        // Check if user already exists in DB
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('GryphoSecurityBundle:User');

        $user = $repo->findOneByUsername($user_id);
        if(!$user)
        {
            $user = new User();
            $user->setUsername($user_id);
            $em->persist($user);

            $role = $em
                ->getRepository('GryphoSecurityBundle:Role')
                ->findOneByRole('ROLE_USER');
            $user->addRole($role);
        }

        //Namen updaten
        $fb_profile = $this->getProfile($fb, $accessToken);

        $np = explode(' ', $fb_profile->getName());
        $user->setNameFirst(array_shift($np));
        $user->setNameLast(array_pop($np));
        $user->setNameMiddle(implode(' ', $np));

        $user->setEmail($user_id);
        $user->setPassword(mt_rand());
        $user->setIsActive(1);
        $user->setOauthToken($accessToken);

        $em->flush();

        // User einloggen

        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, null, 'login_firewall', $user->getRoles());

        // Fire the login event
        $event = new InteractiveLoginEvent($request, $token);
        $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);

        $this->get('security.token_storage')->setToken($token);

        return $this->redirect($this->generateUrl('home'));
    }
}
