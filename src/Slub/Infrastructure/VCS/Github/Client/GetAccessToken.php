<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Client;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class GetAccessToken
{
    /** @var Client */
    private $httpClient;

    /** @var string */
    private $githubAppId;

    /** @var string */
    private $githubPrivateKey;

    public function __construct(Client $httpClient, string $githubAppId, string $githubPrivateKey)
    {
        $this->httpClient = $httpClient;
        $this->githubAppId = $githubAppId;
        $this->githubPrivateKey = $githubPrivateKey;
    }

    public function fetch(string $installationId): string
    {
        $accessTokenUrl = sprintf('/app/installations/%s/access_tokens', $installationId);
        $response = $this->fetchAccessToken($accessTokenUrl);

        return $this->accessToken($response, $accessTokenUrl);
    }

    private function fetchAccessToken(string $accessTokenUrl): ResponseInterface
    {
        $headers = GithubAPIHelper::acceptMachineManPreviewHeader();
        $headers = array_merge($headers, GithubAPIHelper::authorizationHeader($this->jwt()));

        return $this->httpClient->get($accessTokenUrl, ['headers' => $headers]);
    }

    private function accessToken(ResponseInterface $response, string $accessTokenUrl): string
    {
        $content = json_decode($response->getBody()->getContents(), true);
        if (null === $content) {
            throw new \RuntimeException(
                sprintf('There was a problem when fetching the access token for url "%s"', $accessTokenUrl)
            );
        }

        return (string) $content['token'];
    }

    private function jwt(): string
    {
        return JWT::encode(['iss' => $this->githubAppId], $this->githubPrivateKey, 'RS256');
    }
}
