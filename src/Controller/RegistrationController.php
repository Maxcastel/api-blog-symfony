<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController extends AbstractController
{
    private $em;
    private $userPasswordHasher;

    public function __construct(EntityManagerInterface $em,UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->em = $em;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    #[Route('/api/register', methods: ['POST'], name: 'register')]
    public function register(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $user = new User();
            $user->setEmail($data['email']);
            $user->setFirstName($data['firstName']);
            $user->setLastName($data['lastName']);
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $data['password']
                )
            );
            $user->setRoles(['ROLE_USER']);
            
            $this->em->persist($user);
            $this->em->flush();

            return $this->json([
                "status" => 201,
                "success" => true,
                'message' => 'Registered with success',
            ], Response::HTTP_CREATED);
        }
        catch (\Exception $e) {
            return $this->json([
                "status" => 400,
                "success" => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
