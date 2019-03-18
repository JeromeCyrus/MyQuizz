<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Categorie;
use App\Entity\Question;
use App\Entity\Reponse;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class PagesController extends AbstractController
{
    /**
     * @Route("/", name="accueil")
     */
    public function index()
    {
        $repo = $this->getDoctrine()->getRepository(Categorie::class);
        $categories = $repo->findAll();
        return $this->render('pages/index.html.twig', [
            'categories' => $categories
        ]);
    }

    /**
     * @Route("/quiz/{id}", name="quiz_show")
     */
    public function show(Categorie $categorie, Request $request)
    {
        $session = $request->getSession();
        $score = $session->get($categorie->getName());
        return $this->render('pages/show.html.twig', [
            'quizz' => $categorie,
            'score' => $score
        ]);
    }

    /**
     * @Route("/quiz/{id}/result", name="result")
     */
    public function result(Categorie $categorie, Request $request) {
        $session = $request->getSession();
        $score = $session->get($categorie->getName());
        return $this->render('pages/result.html.twig', [
            'quizz' => $categorie,
            'score' => $score,
        ]);
    }

    /* fonction de rendu */
    public function myrender($categorie, $questions, $count, $form, $result)
    {
        return $this->render('pages/quiz.html.twig', [
            'quizz' => $categorie,
            'question' => $questions[$count-1],
            'count' => $count,
            'formQuiz' => $form->createView(),
            'result' => $result
        ]);
    }

    /* fonction pour renvoyer le résultat et calculer le score */
    public function calcul_score($answer, $count, $categorie, $questions, $request) {
        if($answer == 1) {
            $session = $request->getSession();
            if($session->get($categorie->getName()) == "non fait") {
                $newvalue = 1;
            }
            if($session->get($categorie->getName()) != "non fait") {
                $newvalue = (int)$session->get($categorie->getName());
                $newvalue += 1;
            }
            $session->set($categorie->getName(), $newvalue);
            $result =  "Bonne réponse !!!";
        }
        else {
            $repository = $this->getDoctrine()->getRepository(Reponse::class);
            $soluce = $repository->findBy([
                'reponse_expected' => 1,
                'question' => $questions[$count-1]
                ]);
            $result =  "Mauvaise réponse !!! La bonne réponse était " . $soluce[0]->getReponse();
        }
        return $result;
    }

    public function beginQuiz($request, $categorie, $count) {
        if($count == 1) {
            $session = $request->getSession();
            $session->set($categorie->getName(), 'non fait');
        }

    }

    /**
     * @Route("/quiz/{id}/{count}", name="make_quiz")
     */
    public function makeQuiz(Request $request, Categorie $categorie, $count, Reponse $response)
    {
        $session = $request->getSession();
        $this->beginQuiz($request, $categorie, $count);
        $questions = $categorie->getQuestions();
        $reponses = $questions[$count-1]->getReponses();
        $result = '';
        $form = $this->createFormBuilder($response)
        ->add('reponse', ChoiceType::class, array(
            'label'  => $questions[$count-1]->getQuestion(),
            'choices'  => array(
                $reponses[0]->getReponse() => $reponses[0]->getReponse(),
                $reponses[1]->getReponse() => $reponses[1]->getReponse(),
                $reponses[2]->getReponse() => $reponses[2]->getReponse()
            ),
            'multiple'=> false,
            'expanded'=> true,
        ))
        ->getForm();
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $repository = $this->getDoctrine()->getRepository(Reponse::class);
            $verified = $repository->findBy(['reponse' => $form["reponse"]->getData()]);
            $soluce = $repository->findBy([
                'reponse_expected' => 1,
                'question' => $questions[$count-1]
                ]);
            $result = $this->calcul_score($verified[0]->getReponseExpected(), $count, $categorie, $questions, $request);

            return $this->myrender($categorie, $questions, $count, $form, $result);
        }

        return $this->myrender($categorie, $questions, $count, $form, $result);
    }

}
