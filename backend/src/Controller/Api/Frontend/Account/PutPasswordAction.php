<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Account\ChangePassword;
use App\Entity\Api\Status;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[
    OA\Put(
        path: '/frontend/account/password',
        operationId: 'changeMyPassword',
        description: 'Change the password of your account.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Api_Account_ChangePassword')
        ),
        tags: ['Accounts'],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class PutPasswordAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        protected Serializer $serializer,
        protected ValidatorInterface $validator
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $user = $request->getUser();

        /** @var ChangePassword $changePassword */
        $changePassword = $this->serializer->denormalize($request->getParsedBody(), ChangePassword::class);

        // Validate the UploadFile API record.
        $errors = $this->validator->validate($changePassword);
        if (count($errors) > 0) {
            throw ValidationException::fromValidationErrors($errors);
        }

        if (!$user->verifyPassword($changePassword->current_password)) {
            throw new InvalidArgumentException('Invalid current password.');
        }

        $user = $this->em->refetch($user);

        $user->setNewPassword($changePassword->new_password);
        $this->em->persist($user);
        $this->em->flush();

        return $response->withJson(Status::updated());
    }
}
