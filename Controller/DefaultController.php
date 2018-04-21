<?php

namespace MiguelAlcaino\IuguBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('MiguelAlcainoIuguBundle:Default:index.html.twig');
    }
}
