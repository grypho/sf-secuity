<?php

namespace Grypho\SecurityBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Grypho\SecurityBundle\Entity\User;

/**
 * User controller.
 *
 * @Route("/admin/user")
 */
class UserController extends AbstractController
{
    /**
     * Lists all User entities.
     *
     * @Route("/", name="admin_user_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('GryphoSecurityBundle:User')->findAll();

        return $this->render('GryphoSecurityBundle:user:index.html.twig', array(
            'users' => $users,
        ));
    }

    /**
     * Finds and displays a User entity.
     *
     * @Route("/{id}", name="admin_user_show")
     * @Method("GET")
     */
    public function showAction(User $user)
    {

        return $this->render('GryphoSecurityBundle:user:show.html.twig', array(
            'user' => $user,
        ));
    }
}
