<?php

namespace ControleOnline\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use ControleOnline\Entity\People;
use ControleOnline\Entity\User;


class DeleteUserAction
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



    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager     = $manager;
    }

    public function __invoke(People $data, Request $request): JsonResponse
    {
        $this->request = $request;

        try {
            $payload   = json_decode($this->request->getContent(), true);
            $result    = $this->deleteUser($data, $payload);

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


    private function deleteUser(People $person, array $payload): bool
    {
        try {
            $this->manager->getConnection()->beginTransaction();

            if (!isset($payload['id'])) {
                throw new \InvalidArgumentException('Document id is not defined');
            }

            $users   = $this->manager->getRepository(User::class)->findBy(['people' => $person]);
            if (count($users) == 1) {
                throw new \InvalidArgumentException('Deve existir pelo menos um usuário');
            }

            $user    = $this->manager->getRepository(User::class)->findOneBy(['id' => $payload['id'], 'people' => $person]);
            if (!$user instanceof User) {
                throw new \InvalidArgumentException('Person user was not found');
            }

            $this->manager->remove($user);

            $this->manager->flush();
            $this->manager->getConnection()->commit();

            return true;
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive())
                $this->manager->getConnection()->rollBack();

            throw new \InvalidArgumentException($e->getMessage());
        }
    }
}
