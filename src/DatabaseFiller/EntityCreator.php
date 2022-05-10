<?php

namespace App\DatabaseFiller;

use App\Entity\Actor;
use App\Entity\Director;
use App\Entity\Movie;
use App\Entity\Type;
use App\Repository\ActorRepository;
use App\Repository\DirectorRepository;
use App\Repository\MovieRepository;
use App\Repository\TypeRepository;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class EntityCreator
{
    const DIRECTOR_JOB = 'director';

    /**
     * @var MovieDbClient
     */
    private MovieDbClient $movieDbClient;

    /**
     * @var TypeRepository
     */
    private TypeRepository $typeManager;

    /**
     * @var MovieRepository
     */
    private MovieRepository $movieManager;

    /**
     * @var ActorRepository
     */
    private ActorRepository $actorManager;

    /**
     * @var DirectorRepository
     */
    private DirectorRepository $directorManager;

    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @param MovieDbClient      $movieDbClient
     * @param TypeRepository     $typeManager
     * @param MovieRepository    $movieManager
     * @param ActorRepository    $actorManager
     * @param DirectorRepository $directorManager
     */
    public function __construct(
        MovieDbClient      $movieDbClient,
        TypeRepository     $typeManager,
        MovieRepository    $movieManager,
        ActorRepository    $actorManager,
        DirectorRepository $directorManager,
        Connection         $connection
    ) {
        $this->movieDbClient   = $movieDbClient;
        $this->typeManager     = $typeManager;
        $this->movieManager    = $movieManager;
        $this->actorManager    = $actorManager;
        $this->directorManager = $directorManager;
        $this->connection      = $connection;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function createAndSaveEntity(bool $truncate, int $pages, OutputInterface $output)
    {
        if (true === $truncate) {
            $this->truncate();
        }

        $responseListMovies = $this->movieDbClient->getMoviesListByHorrorType();
        if ($pages > 1) {
            for ($page = 2; $page <= $pages; $page++) {
                $pageResponseListMovies = $this->movieDbClient->getMoviesListByHorrorType($page);
                $responseListMovies = array_merge($responseListMovies, $pageResponseListMovies);
            }
        }

        $progressBar = new ProgressBar($output, count($responseListMovies));
        $progressBar->setBarCharacter('-');
        $progressBar->setEmptyBarCharacter(' ');
        $progressBar->setProgressCharacter('<fg=green;options=bold>8=></>');
        $progressBar->setBarWidth(50);

        foreach ($progressBar->iterate($responseListMovies) as $responseListMovie) {
            $movie = $this->movieManager->getByTitleAndReleaseDate($responseListMovie->title, $responseListMovie->release_date);
            $responseMovie = $this->movieDbClient->getMovieDetailsWithCasting($responseListMovie->id);
            if (null === $movie) {
                $movie = new Movie();
                $movie->setTitle($responseMovie->title);
                $movie->setSynopsis($responseMovie->overview);
                $movie->setLanguage($responseMovie->original_language);
                $movie->setImage($responseMovie->poster_path);
                $movie->setReleaseDate(new DateTime($responseMovie->release_date));
            }

            foreach ($responseMovie->genres as $responseType) {
                $type = $this->typeManager->getByName($responseType->name);
                if (null === $type) {
                    $type = new Type();
                    $type->setName($responseType->name);
                }
                if (false === $movie->getType()->contains($type)) {
                    $movie->addType($type);
                }
            }

            $actors = $responseMovie->credits->cast;
            $actorsQuantity = min(count($actors), 4);
            for ($i = 0; $i < $actorsQuantity; $i++) {
                $actor = $this->actorManager->getByName($actors[$i]->name);
                if (null === $actor) {
                    $actor = new Actor();
                    $actor->setName($actors[$i]->name);
                    $actor->setImage($actors[$i]->profile_path);
                }
                if (false === $movie->getActors()->contains($actor)) {
                    $movie->addActor($actor);
                }
            }

            foreach ($responseMovie->credits->crew as $crew) {
                if (self::DIRECTOR_JOB !== strtolower($crew->job)) {
                    continue;
                }
                $director = $this->directorManager->getByName($crew->name);
                if (null === $director) {
                    $director = new Director();
                    $director->setName($crew->name);
                    $director->setImage($crew->profile_path);
                }
                if (false === $movie->getDirectors()->contains($director)) {
                    $movie->addDirector($director);
                }
            }

            $movie->setBudget($responseMovie->budget);

            $this->movieManager->create($movie);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function truncate()
    {
        //TODO: Begin transaction
        $this->connection->executeQuery('SET foreign_key_checks = 0');
        $this->connection->executeQuery('TRUNCATE TABLE movie');
        $this->connection->executeQuery('TRUNCATE TABLE type');
        $this->connection->executeQuery('TRUNCATE TABLE actor');
        $this->connection->executeQuery('TRUNCATE TABLE director');
        $this->connection->executeQuery('TRUNCATE TABLE movie_director');
        $this->connection->executeQuery('TRUNCATE TABLE movie_actor');
        $this->connection->executeQuery('TRUNCATE TABLE movie_type');
    }
}
