<?php

namespace CRM\CommunityMessagesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
      // ini_set('session.use_cookies', '0');
      $response = new \Symfony\Component\HttpFoundation\Response(json_encode(array('name' => "Fiddly wink")));
      $response->headers->set('Content-Type', 'application/json');
      return $response;
    }
}
