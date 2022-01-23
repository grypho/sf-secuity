<?php

namespace Grypho\SecurityBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ActivityController extends AbstractController
{
    /**
     * @Route("/activity", name="activity")
     */
    public function activityAction()
    {
        return new \Symfony\Component\HttpFoundation\Response('', 204);
    }
}
