<?php

namespace Grypho\SecurityBundle\Controller;

use Grypho\SecurityBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class ForgetPasswordController extends AbstractController
{
    /**
     * @Route("/password_recovery", name="login_forget_password")
     */
    public function forgetPasswordRequestAction(Request $request, \Swift_Mailer $mailer)
    {
        $emailForm = new \Grypho\SecurityBundle\Entity\Email();

        $form = $this->createFormBuilder($emailForm)
        ->setAction($this->generateUrl('login_forget_password'))
        ->add('email', EmailType::class, ['label' => 'Mailadresse bei Anmeldung:'])
        ->add('Absenden', SubmitType::class)
        ->getForm()
        ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $emailForm->email;

            $em = $this->getDoctrine()->getManager();
            $repo = $em->getRepository('GryphoSecurityBundle:User');

            $user = $repo->findOneByEmail($email);
            if ($user) {
                $user->generateOneTimeToken();
                $em->flush();
                $this->sendEmail($user, $mailer);
            }

            return $this->render(
                '@GryphoSecurity/ForgetPassword/confirmationCodeSent.html.twig',
                [
                    'email' => $email,
                ]
            );
        }

        // Ergebnis Rendern
        return $this->render(
            '@GryphoSecurity/ForgetPassword/enterEmailAddress.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    private function sendEmail(User $user, \Swift_Mailer $mailer)
    {
        $email_settings = $this->getParameter('gsb_email');
        $message = (new \Swift_Message($email_settings['recover_subject']))
            ->setFrom([$email_settings['recover_sender_email'] => $email_settings['recover_sender']])
            ->setTo($user->getEmail())
            ->setBody(
                $this->renderView(
                    // app/Resources/views/Emails/registration.html.twig
                    '@GryphoSecurity/ForgetPassword/recoveryEmail.html.twig',
                    [
                        'user' => $user,
                        'sender' => $email_settings['recover_sender'],
                    ]
                ),
                'text/plain'
            );
        $mailer->send($message);
    }

    /**
     * @Route("/password_reset/{token}", name="login_forget_confirm")
     *
     * @param mixed $token
     */
    public function forgetConfirmAction(Request $request, $token)
    {
        $password = new \Grypho\SecurityBundle\Entity\Password();

        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('GryphoSecurityBundle:User');

        $user = $repo->findOneByOneTimeToken($token);

        if (!$user) {
            return $this->render('@GryphoSecurity/ForgetPassword/tokenError.html.twig');
        }

        $form = $this->createFormBuilder($password)
        ->setAction($this->generateUrl('login_forget_confirm', ['token' => $token]))
        ->add('password', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'Passwortfelder stimmen nicht überein!',
            'options' => ['attr' => ['class' => 'password-field']],
            'required' => true,
            'first_options' => ['label' => 'Gewünschtes Passwort:'],
            'second_options' => ['label' => 'Passwort wiederholen:'],
        ])
        ->add('submit', SubmitType::class, ['label' => 'Passwort ändern'])
        ->getForm()
        ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->clearOneTimeToken();
            $user->setPasswordEncrypt($password->password);
            $em->flush();

            // Ummelden von Usern klappt nicht, daher an dieser Stelle Weiterleitung zum Login-Formular.
            $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user, $password->password, 'login_firewall', $user->getRoles());
//            $this->get('security.token_storage')->setToken(null);
            $this->get('security.token_storage')->setToken($token);

            $this->addFlash('notice', 'Das Passwort wurde erfolgreich geändert. Du bist jetzt eingeloggt.');

            return $this->redirectToRoute('home');
        }

        // Ergebnis Rendern
        return $this->render(
            '@GryphoSecurity/ForgetPassword/enterNewPassword.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
