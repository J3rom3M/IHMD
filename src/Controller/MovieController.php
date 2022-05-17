<?php

namespace App\Controller;

use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Movie;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api", name="api_")
 */
class MovieController extends AbstractController
{
    /**
     * @Route("/movie", name="movie_list", methods={"GET"})
     */
    public function getAllMovies(MovieRepository $movieRepository): Response
    {
        $movies = $movieRepository->findAll();

        return $this->json($movies, Response::HTTP_OK, [], ['groups' => 'movies_get']);
    }

    /**
     * Get one movie
     *
     * @Route("/movie/{id}", name="movie_get_one", methods={"GET"})
     */
    public function getOneMovie(MovieRepository $movieRepository, $id): Response
    {
        $movie = $movieRepository->find($id);
        // 404 ?
        if ($movie === null) {
            // On envoie une vraie réponse en JSON
            return $this->json(['message' => 'Movie not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($movie, Response::HTTP_OK, [], ['groups' => 'movies_get']);
    }

    /**
     * Edit movie (PUT)
     *
     * @Route("/movies/{id<\d+>}", name="api_movies_put", methods={"PUT", "PATCH"})
     */
    public function putAndPatch(Movie $movie = null, EntityManagerInterface $em, SerializerInterface $serializer, Request $request, ValidatorInterface $validator)
    {
        // 1. On souhaite modifier le film dont l'id est transmis via l'URL

        // 404 ?
        if ($movie === null) {
            // On retourne un message JSON + un statut 404
            return $this->json(['error' => 'Film non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        // Notre JSON qui se trouve dans le body
        $jsonContent = $request->getContent();

        // 2. On va devoir associer les données JSON reçues sur l'entité existante
        // On désérialise les données reçues depuis le front ($request->getContent())...
        // ... dans l'objet Movie à modifier
        // @see https://symfony.com/doc/current/components/serializer.html#deserializing-in-an-existing-object
        $serializer->deserialize(
            $jsonContent,
            Movie::class,
            'json',
            // On a cet argument en plus qui indique au serializer quelle entité existante modifier
            [AbstractNormalizer::OBJECT_TO_POPULATE => $movie]
        );

        // Validation de l'entité désérialisée
        $errors = $validator->validate($movie);
        // Génération des erreurs
        if (count($errors) > 0) {
            // On retourne le tableau d'erreurs en Json au front avec un status code 422
            return $this->json($this->generateErrors($errors), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // On flush $movie qui a été modifiée par le Serializer
        $em->flush();

        return $this->json(['message' => 'Film modifié.'], Response::HTTP_OK);
    }

    /**
     * Génération des erreurs
     */
    private function generateErrors($errors): array
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
