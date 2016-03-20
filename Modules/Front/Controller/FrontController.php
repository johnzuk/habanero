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
        $em = $this->getEntityManager();
        $repository = $em->getRepository('Front\Entity\User');
        $form = $this->getLoginForm();

        if ($form->isSubmitted() && $form->isValid()) {

            $username = $form->getData()['login'];
            $password = $form->getData()['password'];

            $user = $repository->createQueryBuilder('u')
                ->where('u.name = :username OR u.email = :email')
                ->setParameter('username', $username)
                ->setParameter('email', $username)
                ->getQuery()
                ->getOneOrNullResult();

            if ($user !== null) {
                if (password_verify($password, $user->getPassword())) {
                    return $this->logIn($user);
                }
            }
        }

        return $this->render('login.html.twig', [
            'title' => 'Login',
            'form' => $form->createView()
        ]);
    }

    public function logOutAction()
    {
        $this->getSession()->clear();

        return new RedirectResponse('/');
    }

    public function registerAction()
    {
        $user = new User();
        $form = $this->getRegisterForm($user);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getEntityManager();
            $password = password_hash($user->getPlainPassword(), PASSWORD_BCRYPT, [
                'cost' => 10
            ]);
            $user->setPassword($password);

            $em->persist($user);
            $em->flush();

            $this->sendRegisterEmail($user);
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

    protected function sendRegisterEmail(User $user)
    {
        $mail = $this->getMailer();

        $mail->setFrom('contact@habanero.dev', 'Habanero');
        $mail->addAddress($user->getEmail(), $user->getName());
        $mail->isHTML(true);

        $mail->Subject = 'New account created in Habanero.dev';
        $mail->Body = 'Welcome in habanero.dev';

        $mail->send();
    }

    /**
     * @param User $user
     * @return \Symfony\Component\Form\Form
     */
    protected function getRegisterForm(User $user)
    {
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

        return $form;
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function getLoginForm()
    {
        $form = $this->createFormBuilder()
            ->add('login', TextType::class, [
                'label' => 'Login',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('Sign in', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-lg btn-primary btn-block'
                ]
            ])
            ->getForm();

        $form->handleRequest();

        return $form;
    }

    protected function logIn(User $user)
    {
        $this->getSession()->set('login', true);
        $this->getSession()->set('name', $user->getName());
        $this->getSession()->set('id', $user->getId());

        return new RedirectResponse('/admin');
    }
}