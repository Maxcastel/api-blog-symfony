<?php

namespace App\Controller;

use App\Repository\EmailRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Email;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as MimeEmail;

class EmailController extends AbstractController
{
    private $em;
    private $serializer;
    private $emailRepository;

    public function __construct(EmailRepository $emailRepository, EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->emailRepository = $emailRepository;
    }

    #[Route('/api/emails', methods:["POST"], name: 'email_send')]
    #[IsGranted('ROLE_ADMIN')]
    public function sendEmail(Request $request, MailerInterface $mailer): JsonResponse
    {
        try{
            $email = $this->serializer->deserialize($request->getContent(), Email::class, 'json');

            date_default_timezone_set('Europe/Paris');

            $email->setsendDate(new \DateTime());

            $this->em->persist($email);
            $this->em->flush();

            $data = json_decode($request->getContent(), true);

            $emailToSend = (new MimeEmail())
                ->from($data["email"])
                ->to('maxence.castel59@gmail.com')
                ->subject($data["subject"])
                ->html($data["message"]);

            $mailer->send($emailToSend);

            return $this->json([
                "status" => 201,
                "success" => true,
                "data" => $email,
                'message' => 'Email sent with success',
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
    
    #[Route('/api/emails', methods:["GET"], name: 'email_show_all')]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllEmails(): JsonResponse
    {
        try{
            return $this->json([
                "status" => 200,
                "success" => true,
                "data" => $this->emailRepository->findAll(),
                'message' => 'Operation completed with success',
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

    #[Route('/api/emails/{id}', methods:["GET"], name: 'email_show')]
    #[IsGranted('ROLE_ADMIN')]
    public function getEmail(int $id): JsonResponse
    {
        try{
            $email = $this->emailRepository->find($id);

            if(!$email){
                return $this->json([
                    "status" => 404,
                    "success" => false,
                    'message' => 'Email with id '.$id.' not found',
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                "status" => 200,
                "success" => true,
                "data" => $email,
                'message' => 'Operation completed with success',
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
    
    #[Route('/api/emails/{id}', methods:["DELETE"], name: 'email_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteEmail(int $id): JsonResponse
    {
        try{
            $email = $this->emailRepository->find($id);

            if(!$email){
                return $this->json([
                    "status" => 404,
                    "success" => false,
                    'message' => 'Email with id '.$id.' not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $this->em->remove($email);
            $this->em->flush();

            return $this->json([
                "status" => 200,
                "success" => true,
                'message' => 'Deleted with success',
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
