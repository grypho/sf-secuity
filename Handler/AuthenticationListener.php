<?php

namespace Grypho\SecurityBundle\Handler;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Description of LoginHandler.
 *
 * @author cschumann
 *
 * @see http://www.webtipblog.com/create-authentication-listener-symfony-2/
 * @see http://stackoverflow.com/questions/8823560/access-to-database-in-a-listener-in-symfony-2
 * @see http://stackoverflow.com/questions/22678106/how-can-i-inject-doctrine-into-symfony2-service
 *
 * Alternatives:
 * @see http://www.reecefowell.com/2011/10/26/redirecting-on-loginlogout-in-symfony2-using-loginhandlers/
 * @see http://stackoverflow.com/questions/15908856/do-something-just-after-symfony2-login-success-and-before-redirect
 * @see http://symfony.com/blog/new-in-symfony-2-4-customize-the-security-features-with-ease
 */
class AuthenticationListener
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $token = $event->getAuthenticationToken();
        if (is_a($token, "Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken")) {
            // If username is username of valid user, increase number of failed logins
            $user = $this->em->getRepository('GryphoSecurityBundle:User')->findOneByUsername($token->getUsername());
            if ($user) {
                $user->setLoginFailedCount($user->getLoginFailedCount() + 1);
                $this->em->flush();
            }
        }
    }

    public function onAuthenticationSuccess(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        $this->em->merge($user); // Ensure user is managed by entity manager
        $user->setLoginFailedCount(0);
        $user->setLoginCount($user->getLoginCount() + 1);
        $user->setLastactivityAt(new \DateTime());
        $this->em->flush();
    }
}
