<?php

namespace App\Controller;

use App\Entity\Article;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\UserRepository;
use App\Repository\ArticleRepository;

class ArticleController extends AbstractController
{
    private $em;
    private $serializer;
    private $userRepository;
    private $articleRepository;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer, UserRepository $userRepository, ArticleRepository $articleRepository)
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->userRepository = $userRepository;
        $this->articleRepository = $articleRepository;
    }

    #[Route('/api/articles', methods:["POST"], name: 'article_create')]
    public function createArticle(Request $request): JsonResponse
    {
        try{
            $data = json_decode($request->getContent(), true);
            $article = $this->serializer->deserialize($request->getContent(), Article::class, 'json');
            
            $article->setUser($this->userRepository->find($data['userId']));

            date_default_timezone_set('Europe/Paris');

            $article->setCreationDate(new \DateTime()); 
            
            $this->em->persist($article);
            $this->em->flush();
            
            return $this->json([
                "status" => 201,
                "success" => true,
                'message' => 'Created with success',
                "data" => $article,
            ], Response::HTTP_CREATED, [], ['groups' => 'getArticle']);
        }   
        catch(\Exception $e){
            return $this->json([
                "status" => 400,
                "success" => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/articles', methods:["GET"], name: 'article_show_all')]
    public function getAllArticles(): JsonResponse
    {
        try{
            return $this->json([
                "status" => 200,
                "success" => true,
                'message' => 'Operation completed with success',
                "data" => $this->articleRepository->findAll(),
            ], Response::HTTP_OK, [], ['groups' => 'getArticle']);
        }   
        catch(\Exception $e){
            return $this->json([
                "status" => 400,
                "success" => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
