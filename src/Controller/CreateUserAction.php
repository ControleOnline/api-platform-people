<?php

namespace ControleOnline\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use ControleOnline\Entity\People;
use ControleOnline\Entity\User;


class CreateUserAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Request
     *
     * @var Request
     */
    private $request  = null;

    /**
     * Security
     *
     * @var Security
     */
    private $security = null;

    /**
     * Current user
     *
     * @var \ControleOnline\Entity\User
     */
    private $currentUser = null;

    /**
     * Password encoder
     *
     * @var UserPasswordEncoderInterface
     */
    private $encoder = null;

    public function __construct(EntityManagerInterface $manager, Security $security, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->manager     = $manager;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
        $this->encoder     = $passwordEncoder;
    }

    public function __invoke(People $data, Request $request): JsonResponse
    {
        $this->request = $request;

        try {
            $payload   = json_decode($this->request->getContent(), true);
            $result    = $this->addUser($data, $payload);

            return new JsonResponse([
                'response' => [
                    'data'    => $result,
                    'count'   => 1,
                    'error'   => '',
                    'success' => true,
                ],
            ], 200);
        } catch (\Exception $e) {

            return new JsonResponse([
                'response' => [
                    'data'    => [],
                    'count'   => 0,
                    'error'   => $e->getMessage(),
                    'success' => false,
                ],
            ]);
        }
    }

    private function addUser(People $person, array $payload): ?array
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            if (!isset($payload['username']) || empty($payload['username'])) {
                throw new \InvalidArgumentException('Username param is not valid');
            }

            if (!isset($payload['password']) || empty($payload['password'])) {
                throw new \InvalidArgumentException('Password param is not valid');
            }

            $company = $this->manager->getRepository(People::class)->find($person->getId());
            $user    = $this->manager->getRepository(User::class)->findOneBy(['username' => $payload['username']]);
            if ($user instanceof User) {
                throw new \InvalidArgumentException('O username já esta em uso');
            }

            $user = new User();
            $user->setUsername($payload['username']);
            $user->setHash($this->encoder->encodePassword($user, $payload['password']));
            $user->setPeople($company);

            $this->manager->persist($user);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return [
                'id' => $user->getId()
            ];
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }
    
    private function getUsers(People $person, ?array $payload = null): array
    {
        $members = [];
        $company = $this->manager->getRepository(People::class)->find($person->getId());
        $users   = $this->manager->getRepository(User::class)->findBy(['people' => $company]);

        foreach ($users as $user) {
            $members[] = [
                'id'       => $user->getId(),
                'username' => $user->getUsername(),
                'apiKey'   => $user->getApiKey(),
            ];
        }

        return [
            'members' => $members,
            'total'   => count($members),
        ];
    }
}
