<?php

namespace Softonic\GraphQL;

use Psr\Http\Message\ResponseInterface;

class ResponseBuilder
{
    public function build(ResponseInterface $httpResponse)
    {
        $body = $httpResponse->getBody();

        $normalizedResponse = $this->getNormalizedResponse($body);

        return new Response($normalizedResponse['data'], $normalizedResponse['errors']);
    }

    private function getNormalizedResponse(string $body)
    {
        $decodedResponse = $this->getJsonDecodedResponse($body);

        if (false === array_key_exists('data', $decodedResponse) && empty($decodedResponse['errors'])) {
            throw new \UnexpectedValueException(
                'Invalid GraphQL JSON response. Response body: ' . json_encode($decodedResponse)
            );
        }

        return [
            'data' => $decodedResponse['data'] ?? [],
            'errors' => $decodedResponse['errors'] ?? [],
        ];
    }

    private function getJsonDecodedResponse(string $body)
    {
        $response = json_decode($body, true);

        $error = json_last_error();
        if (JSON_ERROR_NONE !== $error) {
            throw new \UnexpectedValueException(
                'Invalid JSON response. Response body: ' . $body
            );
        }

        return $response;
    }
}
