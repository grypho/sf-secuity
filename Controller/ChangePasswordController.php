<?php

namespace Grypho\SecurityBundle\Controller;

use Grypho\SecurityBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ChangePasswordController extends AbstractController
{
    /**
     * @Route("/password_change", name="login_change_password")
     */
    public function forgetConfirmAction(Request $request)
    {
        $password = new \Grypho\SecurityBundle\Entity\Password();

        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('GryphoSecurityBundle:User');

        $user = $this->getUser();

        if(!$user)
            throw new \Exception("Not logged in");


        $form = $this->createFormBuilder($password)
        ->setAction($this->generateUrl('login_change_password'))
        ->add('password', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'Passwortfelder stimmen nicht überein!',
            'options' => ['attr' => ['class' => 'password-field']],
            'required' => true,
            'first_options' => ['label' => 'Gewünschtes Passwort:'],
            'second_options' => ['label' => 'Passwort wiederholen:'],
        ])
        ->add('submit', SubmitType::class, ['label'=>'Passwort ändern'])
        ->getForm()
        ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $user->setPasswordEncrypt($password->password);
            $em->flush();
            $this->addFlash('notice', 'Das Passwort wurde erfolgreich geändert.');

            return $this->redirect($this->generateUrl('home'));
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
