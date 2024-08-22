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

class ArticleController extends AbstractController
{
    private $em;
    private $serializer;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    #[Route('/api/articles', methods:["POST"], name: 'article_create')]
    public function createArticle(Request $request): JsonResponse
    {
        try{
            $article = $this->serializer->deserialize($request->getContent(), Article::class, 'json');
            
            date_default_timezone_set('Europe/Paris');

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
