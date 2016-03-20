<?php
namespace Front\Controller;
use Habanero\Exceptions\NotFoundException;
use Habanero\Framework\Controller;
use Front\Entity\Page;

class FrontController extends Controller
{
    public function indexAction()
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository('Front\Entity\Page');
        $pages = $repository->findAll();

        return $this->render('base.html.twig', [
            'title' => 'Habanero home page',
            'pages' => $pages
        ]);
    }

    public function loginAction()
    {
        return $this->render('login.html.twig', [
            'title' => 'Login'
        ]);
    }

    public function loginCheckAction()
    {
        var_dump($this->request->request);
        exit();
    }

    public function logOutAction()
    {
        //logout
    }

    public function registerAction()
    {
        return $this->render('base.html.twig', [
            'title' => 'Rejestracja'
        ]);
    }

    public function pageAction($pageName)
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository('Front\Entity\Page');
        $page = $repository->findOneBySlug($pageName);
        $pages = $repository->findAll();

        if ($page === null) {
            throw new NotFoundException(sprintf("Page '%s' not found", $pageName));
        }
        return $this->render('page.html.twig', [
            'title' => $page->getTitle(),
            'content' => $page->getContent(),
            'pages' => $pages
        ]);
    }
}