<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route; 
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\UserRepository;
use App\Repository\ArticleRepository;
use App\Entity\Comment;

class CommentController extends AbstractController
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

    #[Route('/api/comments', methods:["POST"], name: 'comment_create')]
    public function createComment(Request $request): JsonResponse
    {
        try{
            $data = json_decode($request->getContent(), true);

            $user = $this->userRepository->find($data['userId']);
            $article = $this->articleRepository->find($data['articleId']);
            
            if(!$user && !$article){
                return $this->json([
                    "status" => 404,
                    "success" => false,
                    'message' => 'User with id '.$data['userId'].' and Article with id '.$data['articleId'].' not found',
                ], Response::HTTP_NOT_FOUND);
            }

            if(!$user){
                return $this->json([
                    "status" => 404,
                    "success" => false,
                    'message' => 'User with id '.$data['userId'].' not found',
                ], Response::HTTP_NOT_FOUND);
            }

            if(!$article){
                return $this->json([
                    "status" => 404,
                    "success" => false,
                    'message' => 'Article with id '.$data['articleId'].' not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $comment = $this->serializer->deserialize($request->getContent(), Comment::class, 'json');

            $comment->setArticle($article);
            $comment->setUser($user);
            date_default_timezone_set('Europe/Paris');
            $comment->setCreatedAt(new \DateTime());
            $comment->setIsvalid(false);

            $this->em->persist($comment);
            $this->em->flush();

            return $this->json([
                "status" => 201,
                "success" => true,
                "data" => $comment,
                'message' => 'Created with success',
            ], Response::HTTP_CREATED, [], ['groups' => 'getComment']);
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
