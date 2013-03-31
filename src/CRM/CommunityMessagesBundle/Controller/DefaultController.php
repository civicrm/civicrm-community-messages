<?php

namespace CRM\CommunityMessagesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        // ini_set('session.use_cookies', '0');

        // Parse arguments

        $validations = array(
            'prot' => '/^1$/',
            'sid' => '/^[a-zA-Z0-9]{16,64}$/',
            'uf' => '/^(Drupal|Drupal6|WordPress|Joomla|UnitTests)$/',
            'ver' => '/^([0-9\.]|alpha|beta|dev|rc){2,12}$/',
        );

        $params = array();
        foreach ($validations as $key => $regex) {
            if (!preg_match($regex, $this->getRequest()->get($key))) {
                return $this->renderJson($this->createErrorDocument("Error in $key"));
            }
            $params[$key] = $this->getRequest()->get($key);
        }

        // Construct response

        $document = array(
            'ttl' => 24 * 60 * 60, // 1 day
            'retry' => 1.5 * 60 * 60, // 1.5 hours
            'messages' => array(),
        );

        $document['messages'][] = array(
            'markup' => $this->renderView('CRMCommunityMessagesBundle:Default:stdalert.html.twig', $params),
            // 'perms' => array('administer CiviCRM'),  <== default to "require administer CiviCRM"
            // 'components' => array('CiviMail'), <== default to "no component filtering"
        );

        return $this->renderJson($document);
    }

    public function createErrorDocument($message)
    {
        // From client's perspective, this is an invalid document. It will be
        // discarded (and eventually client will retry).
        return array(
            'error' => $message,
        );
    }

    public function renderJson($document)
    {
        $response = new \Symfony\Component\HttpFoundation\Response(json_encode($document));
        $response->headers->set('Content-Type', 'application/json');
        return $response;

    }
}
