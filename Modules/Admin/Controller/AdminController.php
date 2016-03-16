<?php
namespace Admin\Controller;
use \Habanero\Framework\Controller;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    public function usersAction()
    {
        return new Response("To jest moja pierwsza strona WWW");
    }
}