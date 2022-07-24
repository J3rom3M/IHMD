<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    /**
     * Get users
     *
     * @Route("/api/users", name="api_users_list", methods="GET")
     */
    public function getAll(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->json($users, Response::HTTP_OK, [], ['groups' => 'users']);
    }

    /** User profile
     *
     * @Route("/api/user/{id}", name="api_user_get_one", methods="GET")
     *
     */
    public function getOne(User $user = null): Response
    {
        // 404 ?
        if ($user === null) {
            // On envoie une vraie réponse en JSON
            return $this->json(['message' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'users']);
    }

    /**
     * Register user
     *
     * @Route("/api/user/registration", name="api_user_registration", methods="POST")
     * @param Request $request
     *
     */
    public function register(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserPasswordHasherInterface  $encoder)
    {
        // Notre JSON qui se trouve dans le body
        $jsonContent = $request->getContent();

        // On deserialize le JSON
        // => on transforme le JSON en entité User
        // @link https://symfony.com/doc/current/components/serializer.html
        $user = $serializer->deserialize($jsonContent, User::class, 'json');

        // Validation de l'entité désérialisée
        $errors = $validator->validate($user);
        // Génération des erreurs
        if (count($errors) > 0) {
            // On retourne le tableau d'erreurs en Json au front avec un status code 422
            return $this->json($this->generateErrors($errors), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $plainPassword = $user->getPassword();
        $encoded = $encoder->hashPassword($user, $plainPassword);

        $user->setPassword($encoded);

        $entityManager->persist($user);
        $entityManager->flush();

        // return $this->json(
        //     $user,
        //     Response::HTTP_CREATED,
        //     [
        //         'Location' => $this->generateUrl('api_users_get_one', ['id' => $user->getId()])
        //     ],
        // );
        return $this->json(['message' => 'Utilisateur créé avec succès !'], Response::HTTP_CREATED);
    }

    /**
     * Edit user (PUT)
     *
     * @Route("/api/user/edit/{id<\d+>}", name="api_user_edit", methods={"PUT", "PATCH"})
     */
    public function putAndPatch(User $user = null, EntityManagerInterface $entityManager, SerializerInterface $serializer, Request $request, ValidatorInterface $validator)
    {
        // 1. On souhaite modifier le user dont l'id est transmis via l'URL

        // 404 ?
        if ($user === null) {
            // On retourne un message JSON + un statut 404
            return $this->json(['error' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        // Notre JSON qui se trouve dans le body
        $jsonContent = $request->getContent();

        // 2. On va devoir associer les données JSON reçues sur l'entité existante
        // On désérialise les données reçues depuis le front ($request->getContent())...
        // ... dans l'objet User à modifier
        // @see https://symfony.com/doc/current/components/serializer.html#deserializing-in-an-existing-object
        $serializer->deserialize(
            $jsonContent,
            User::class,
            'json',
            // On a cet argument en plus qui indique au serializer quelle entité existante modifier
            [AbstractNormalizer::OBJECT_TO_POPULATE => $user]
        );

        // Validation de l'entité désérialisée
        $errors = $validator->validate($user);
        // Génération des erreurs
        if (count($errors) > 0) {
            // On retourne le tableau d'erreurs en Json au front avec un status code 422
            return $this->json($this->generateErrors($errors), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // On flush $movie qui a été modifiée par le Serializer
        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur modifié.'], Response::HTTP_OK);
    }

    /**
     * Delete user
     *
     * @Route("/api/user/delete/{id<\d+>}", name="api_user_delete", methods="DELETE")
     */
    public function delete(User $user = null, EntityManagerInterface $em)
    {
        // 404 ?
        if ($user === null) {
            // On retourne un message JSON + un statut 404
            return $this->json(['error' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'Utilisateur supprimé.'], Response::HTTP_OK);
    }

    /**
     * Génération des erreurs
     */
    private function generateErrors($errors)
    {
        // Si il y plus que 0 erreurs
        if (count($errors) > 0) {

            // On créé un tableau vide ou l'on stockera les erreurs
            $errorsList = [];

            // On boucle sur $errors pour extraire chaque erreur
            foreach ($errors as $error) {
                // On stock les erreurs (le champ en erreur en clé et le message en valeur)
                // (Tableau associatif)
                $errorsList[$error->getPropertyPath()] = $error->getMessage();
            }
        }
        return $errorsList;
    }
}
