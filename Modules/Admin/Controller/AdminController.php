<?php
namespace Admin\Controller;
use \Habanero\Framework\Controller;
use Symfony\Component\HttpFoundation\Response;
use Admin\Entity\Page;

class AdminController extends Controller
{
    public function usersAction()
    {
        /*return $this->render('base.html.twig', [
            'name' => 'jan'
        ]);*/
        return new Response("To jest moja pierwsza strona WWW");
    }
}