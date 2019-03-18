<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Form\RegistrationType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UsersController extends AbstractController
{
    /**
     * @Route("/inscription", name="user_inscription")
     */
    public function Inscription(Request $request, ObjectManager $manager, UserPasswordEncoderInterface $encoder) {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $hashed = $encoder->encodePassword($user, $user->getPassword());
            $user->setRole('utilisateur')
            ->setUserActived(0)
            ->setPassword($hashed);
            $manager->persist($user);
            $manager->flush();

            return $this->redirectToRoute('user_connexion');
        }

        return $this->render('users/inscription.html.twig', [
                'form' => $form->createView()
            ]);
    }

    /**
     * @Route("/connexion", name="user_connexion")
     */
    public function login() {
        return $this->render('users/connexion.html.twig');
    }

    /**
     * @Route("/deconnexion", name="user_deconnexion")
     */
    public function logout() {}
}
