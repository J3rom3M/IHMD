<?php

namespace App\DatabaseFiller;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MovieDbClient
{
    const BASE_URI = 'https://api.themoviedb.org/3/';
    const HORROR_TYPE_ID = '27';

    /**
     * @var HttpClientInterface
     */
    private HttpClientInterface $client;

    /**
     * @var string
     */
    private string $apiKey;

    /**
     * @param HttpClientInterface $client
     * @param string              $apiKey
     */
    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * @see https://developers.themoviedb.org/3/discover/movie-discover
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getMoviesListByHorrorType(int $resultQuantity = 1): array
    {
        $params = [
            'with_genres' => self::HORROR_TYPE_ID,
            'page'        => strval($resultQuantity),
        ];

        $jsonResponse       = $this->client->request('GET', $this->constructUri('discover/movie', $params));
        $serializedResponse = json_decode($jsonResponse->getContent());

        return $serializedResponse->results;
    }

    /**
     * @param int $movieId
     *
     * @see https://developers.themoviedb.org/3/movies/get-movie-details
     * @see https://developers.themoviedb.org/3/getting-started/append-to-response
     *
     * @return mixed
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getMovieDetailsWithCasting(int $movieId)
    {
        $jsonResponse = $this->client->request('GET', $this->constructUri("movie/$movieId", ['append_to_response' => 'credits']));

        return json_decode($jsonResponse->getContent());
    }

    /**
     * @param string $request
     * @param array  $params
     * @param string $language
     *
     * @return string
     * @see https://developers.themoviedb.org/3/getting-started/introduction
     *
     */
    private function constructUri(string $request, array $params = [], string $language = 'fr'): string
    {
        $uri = self::BASE_URI.$request.'?api_key='.$this->apiKey.'&language='.$language;
        foreach ($params as $paramName => $paramValue) {
            $uri .= '&'.$paramName.'='.$paramValue;
        }

        return $uri;
    }
}
