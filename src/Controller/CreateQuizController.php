<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Categorie;
use App\Entity\Question;
use App\Entity\Reponse;
use App\Form\CreateQuizType;
use App\Form\CreateQuestionType;
use App\Form\CreateAnswerType;

class CreateQuizController extends AbstractController
{
    /**
     * @Route("/create/intro", name="create_quiz_intro")
     */
    public function index(Request $request)
    {
        $session = $request->getSession();
        $session->set('countcreate', 0);
        return $this->render('create_quiz/index.html.twig');
    }


    /**
     * @Route("/create/quiz", name="create_quiz")
     */
    public function createQuiz(Request $request)
    {
        $session = $request->getSession();
        $quiz = new Categorie();
        $form = $this->createForm(CreateQuizType::class, $quiz);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $session->set('quiz', $quiz->getName());
            $session->set('countcreate', 0);
            return $this->redirectToRoute('create_question');

        }
        return $this->render('create_quiz/createQuiz.html.twig', [
                'form' => $form->createView(),
            ]);
    }

    /**
     * @Route("/create/question", name="create_question")
     */
     public function createQuestion(Request $request)
     {
         $session = $request->getSession();
         $question = new Question();
         $form  = $this->createForm(CreateQuestionType::class, $question);

         $form->handleRequest($request);

         if($form->isSubmitted() && $form->isValid()) {

             $array[0] = $question->getQuestion();
             $array[1] = $_POST['good_answer'];
             $array[2] = $_POST['bad_answer1'];
             $array[3] = $_POST['bad_answer2'];
             $session->set('question' . $session->get('countcreate'), $array);
             $session->set('countcreate', $session->get('countcreate')+1);

             if($session->get('countcreate') == 10) {
                 return $this->render('create_quiz/createQuestion.html.twig', [
                         'form' => $form->createView(),
                         'info' => 10
                     ]);
             }
             return $this->redirectToRoute('create_question');
         }

         return $this->render('create_quiz/createQuestion.html.twig', [
                 'form' => $form->createView(),
             ]);
     }

     /**
      * @Route("/create/validate_quiz", name="validate")
      */
     public function validate(Request $request, ObjectManager $manager)
     {
         $session = $request->getSession();
         $quiz = new Categorie();
         $question = new Question();
         $reponse = new Reponse();

         $quiz->setName($session->get('quiz'));
         $manager->persist($quiz);
         $manager->flush();

         $i = 0;
         for($i; $i < 10; $i++) {
             $question = new Question();
             $this->addQuestion($session, $question, $manager, $quiz, $i);
         }

         return $this->render('create_quiz/finalCreate.html.twig', [
             'quizid' => $quiz->getId()
         ]);
     }

     public function addQuestion($session, $question, $manager, $quiz, $count)
     {
        $question->setQuestion($session->get('question' . $count)[0])
                    ->setCategorie($quiz);
        $manager->persist($question);
        $manager->flush();

        $i = 0;
        for($i; $i < 3; $i++) {
            $reponse = new Reponse();
            $this->addAnswer($session, $reponse, $question, $manager, $count, $i);
        }
     }

     public function addAnswer($session, $reponse, $question, $manager, $count, $i)
     {
         if($i == 0) {
             $expect = 1;
         }
         else {
             $expect = 0;
         }
         $reponse->setReponse($session->get('question' . $count)[$i+1])
                 ->setReponseExpected($expect)
                 ->setQuestion($question);
         $manager->persist($reponse);
         $manager->flush();
     }
}
