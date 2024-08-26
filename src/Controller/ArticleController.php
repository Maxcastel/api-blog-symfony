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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

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
    #[IsGranted('ROLE_ADMIN')]
    public function createArticle(Request $request): JsonResponse
    {
        try{
            $data = json_decode($request->getContent(), true);
            $article = $this->serializer->deserialize($request->getContent(), Article::class, 'json');
            
            $user = $this->userRepository->find($data['userId']);

            if(!$user){
                return $this->json([
                    "status" => 404,
                    "success" => false,
                    'message' => 'User with id '.$data['userId'].' not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $article->setUser($user);

            date_default_timezone_set('Europe/Paris');

            $article->setCreationDate(new \DateTime()); 
            
            $this->em->persist($article);
            $this->em->flush();

            $slugger = new AsciiSlugger();

            $article->setLink($slugger->slug($data['title'])->lower().'-'.$article->getId());

            $this->em->flush();
            
            return $this->json([
                "status" => 201,
                "success" => true,
                "data" => $article,
                'message' => 'Created with success',
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

    #[Route('/api/articles/{slug}', methods:["GET"], name: 'article_show')]
    public function getArticle(string $slug): JsonResponse
    {
        try{
            $article = $this->articleRepository->findOneBy(['link' => $slug]);

            if(!$article){
                return $this->json([
                    "status" => 404,
                    "success" => false,
                    'message' => 'Article with link '.$slug.' not found',
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                "status" => 200,
                "success" => true,
                "data" => $article,
                'message' => 'Operation completed with success',
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
    
    #[Route('/api/articles/{id}', methods:["PUT"], name: 'article_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function editArticle(int $id, Request $request): JsonResponse
    {
        try{
            $article = $this->articleRepository->find($id);

            if(!$article){
                return $this->json([
                    "status" => 404,
                    "success" => false,
                    'message' => 'Article with id '.$id.' not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            $user = $this->userRepository->find($data['userId']);

            if(!$user){
                return $this->json([
                    "status" => 404,
                    "success" => false,
                    'message' => 'User with id '.$data['userId'].' not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $article->setTitle($data['title']);
            $article->setDescription($data['description']);
            $article->setContent($data['content']);
            $article->setImageUrl($data['imageUrl']);

            date_default_timezone_set('Europe/Paris');

            $article->setCreationDate($article->getCreationDate()); 
            $article->setLastUpdate(new \DateTime());
            
            $article->setUser($user);

            $slugger = new AsciiSlugger();

            $article->setLink($slugger->slug($data['title'])->lower().'-'.$article->getId());

            $this->em->flush();

            return $this->json([
                "status" => 200,
                "success" => true,
                "data" => $article,
                'message' => 'Operation completed with success',
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

    #[Route('/api/articles/{id}', methods:["DELETE"], name: 'article_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteArticle(int $id): JsonResponse
    {
        try{
            $article = $this->articleRepository->find($id);

            if(!$article){
                return $this->json([
                    "status" => 404,
                    "success" => false,
                    'message' => 'Article with id '.$id.' not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $this->em->remove($article);
            $this->em->flush();

            return $this->json([
                "status" => 200,
                "success" => true,
                'message' => 'Article with id '.$id.' deleted with success',
            ], Response::HTTP_OK);
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
