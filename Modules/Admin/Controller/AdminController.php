<?php
namespace Admin\Controller;

use Front\Entity\Page;
use Front\Entity\User;
use Habanero\Framework\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminController extends Controller
{
    public function indexAction()
    {
        if ($this->isLogin()) {
            return $this->render('admin.base.html.twig');
        }

        return new RedirectResponse('/login');
    }

    public function pagesAction()
    {
        if ($this->isLogin()) {
            $em = $this->getEntityManager();
            $repository = $em->getRepository('Front\Entity\Page');
            $pages = $repository->findAll();

            return $this->render('admin.pages.html.twig', [
                'pages' => $pages
            ]);
        }

        return new RedirectResponse('/login');
    }

    public function pageAction($pageId = null)
    {
        if ($this->isLogin()) {
            $em = $this->getEntityManager();
            $repository = $em->getRepository('Front\Entity\Page');

            $page = new Page();

            if ($pageId !== null) {
                $page = $repository->find($pageId);
            }
            $form = $this->getPageForm($page);

            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($page);
                $em->flush();
            }

            return $this->render('admin.page.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        return new RedirectResponse('/login');
    }

    public function pageDeleteAction()
    {
        if ($this->isLogin()) {
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

        return new RedirectResponse('/login');
    }

    public function usersAction()
    {
        if ($this->isLogin()) {
            $em = $this->getEntityManager();
            $repository = $em->getRepository('Front\Entity\User');
            $users = $repository->findAll();

            return $this->render('admin.users.html.twig', [
                'users' => $users
            ]);
        }

        return new RedirectResponse('/login');
    }

    public function userAction($userId = null)
    {
        if ($this->isLogin()) {
            $em = $this->getEntityManager();
            $repository = $em->getRepository('Front\Entity\User');

            $user = new User();

            if ($userId !== null) {
                $user = $repository->find($userId);
            }
            $form = $this->getUserForm($user);

            if ($form->isSubmitted() && $form->isValid()) {

                $password = password_hash($user->getPlainPassword(), PASSWORD_BCRYPT, [
                    'cost' => 10
                ]);
                $user->setPassword($password);

                $em->persist($user);
                $em->flush();
            }

            return $this->render('admin.page.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        return new RedirectResponse('/login');
    }

    public function userDeleteAction()
    {
        if ($this->isLogin()) {
            $response = [
                'status' => false
            ];

            if ($id = $this->request->request->get('id')) {
                $em = $this->getEntityManager();
                $repository = $em->getRepository('Front\Entity\User');
                $user = $repository->find($id);

                if ($user) {
                    $em->remove($user);
                    $em->flush();
                    $response['status'] = true;
                }
            }

            return new JsonResponse($response);
        }

        return new RedirectResponse('/login');
    }

    /**
     * @param User $user
     * @return \Symfony\Component\Form\Form
     */
    protected function getUserForm(User $user)
    {
        $form = $this->createFormBuilder($user)
            ->add('name', TextType::class, [
                'label' => 'User Name',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options'  => [
                    'label' => 'Password',
                    'attr' => [
                        'class' => 'form-control'
                    ]
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                    'attr' => [
                        'class' => 'form-control'
                    ]
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save',
                'attr' => [
                    'class' => 'btn btn-success pull-right'
                ]
            ])
            ->getForm();

        $form->handleRequest();

        return $form;
    }

    /**
     * @param Page $page
     * @return \Symfony\Component\Form\Form
     */
    protected function getPageForm(Page $page)
    {
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
                'label' => 'Save',
                'attr' => [
                    'class' => 'btn btn-success pull-right'
                ]
            ])
            ->getForm();
        $form->handleRequest();

        return $form;
    }

    /**
     * @return bool
     */
    protected function isLogin()
    {
        return ($this->getSession()->get('login') &&  $this->getSession()->get('id'));
    }
}