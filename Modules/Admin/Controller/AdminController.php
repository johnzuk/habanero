<?php
namespace Admin\Controller;

use Habanero\Framework\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AdminController extends Controller
{
    public function indexAction()
    {
        return $this->render('admin.base.html.twig');
    }

    public function pagesAction()
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository('Front\Entity\Page');
        $pages = $repository->findAll();

        return $this->render('admin.pages.html.twig', [
            'pages' => $pages
        ]);
    }

    public function pageDeleteAction()
    {
        $response = [
            'status' => false
        ];

        if ($id = $this->request->request->get('id')) {
            $em = $this->getEntityManager();
            $repository = $em->getRepository('Front\Entity\Page');
            $page = $repository->find($id);

            if ($page) {
                $em->remove($page);
                $em->flush();
                $response['status'] = true;
            }
        }

        return new JsonResponse($response);
    }

    public function pageAction($pageId)
    {
        $form = $this->createFormBuilder()
            ->add('BLALBA', TextType::class, [
                'label' => 'test'
            ])
            ->getForm();

        return $this->render('admin.page.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function usersAction()
    {
        return $this->render('base.html.twig', [
            'name' => 'jan'
        ]);
        //return new Response("To jest moja pierwsza strona WWW");
    }
}