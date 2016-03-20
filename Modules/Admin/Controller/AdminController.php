<?php
namespace Admin\Controller;

use Front\Entity\Page;
use Habanero\Framework\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

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

    public function pageAction($pageId = null)
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository('Front\Entity\Page');

        $page = new Page();
        $saveLabel = 'Create Page';
        if ($pageId !== null) {
            $page = $repository->find($pageId);
            $saveLabel = 'Save Page';
        }

        $form = $this->createFormBuilder($page)
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 4
                    ]),
                ],
            ])
            ->add('pageName', TextType::class, [
                'label' => 'Page Name',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 4
                    ]),
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => $saveLabel,
                'attr' => [
                    'class' => 'btn btn-success pull-right'
                ]
            ])
            ->getForm();
        $form->handleRequest();

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($page);
            $em->flush();
        }

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