<?php

namespace App\Controller;

use App\Entity\Article;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/api/articles', methods:["POST"], name: 'article_create')]
    public function createArticle(Request $request): JsonResponse
    {
        try{
            $article = new Article();

            $data = json_decode($request->getContent(), true);

            $article->setTitle($data['title']);
            $article->setDescription($data['description']);
            $article->setContent("aaa");
            $article->setLink("https://www.google.com");
            $article->setImageUrl("https://www.google.com"); 
            $article->setCreationDate(new \DateTime()); 
            
            $this->em->persist($article);
            $this->em->flush();
            
            return $this->json([
                "status" => 201,
                "success" => true,
                'message' => 'Created with success',
                "data" => $article,
            ], Response::HTTP_CREATED);
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
