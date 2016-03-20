<?php
namespace Front\Controller;
use Front\Entity\User;
use Habanero\Exceptions\NotFoundException;
use Habanero\Framework\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
        $user = new User();
        $form = $this->createFormBuilder($user)
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('name', TextType::class, [
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
                'label' => 'Register',
                'attr' => [
                    'class' => 'btn btn-success pull-right'
                ]
            ])
            ->getForm();

        $form->handleRequest();

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getEntityManager();
            $password = password_hash($user->getPlainPassword(), PASSWORD_BCRYPT, [
                'cost' => 10
            ]);
            $user->setPassword($password);

            $em->persist($user);
            $em->flush();

            return new RedirectResponse('/login');
        }

        return $this->render('register.html.twig', [
            'title' => 'Rejestracja',
            'form' => $form->createView()
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