<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ApiLoginController extends AbstractController
{
//    /**
//     * @Route("/api/login", name="api_login", methods={"POST"})
//     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
//     */
//    public function index(User $user): Response
//    {
//        if (null === $user) {
//            return $this->json([
//                'message' => 'missing credentials',
//            ], Response::HTTP_UNAUTHORIZED);
//        }
//        $token = "cSs1doO5xpq0dpSd"; // somehow create an API token for $user
//        return $this->json([
//            'user'  => $user->getUserIdentifier(),
//            'token' => $token,
//        ]);
//    }

//    /**
//     * @Route("/api/login", name="api_login")
//     */
//    public function apiLogin()
//    {
//        return $this->json($this->getUser(), 200, [], [
//            'groups' => ['user:read']
//        ]);
//    }

    /**
     * @Route("/api/login", name="api_login", methods={"POST"})
     */
    public function login(Request $request): Response
    {
        $user = $this->getUser();

        return $this->json([
            'username' => $user, 200, [], [
//            'groups' => ['user:read'],
            'roles' => $user->getRoles(),]
        ]);
    }

//    public function index(#[CurrentUser] ?User $user): Response
//    {
//
//    }
}
