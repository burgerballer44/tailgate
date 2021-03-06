<?php

namespace Tailgate\Domain\Service\User;

use Tailgate\Application\Command\User\RegisterUserCommand;
use Tailgate\Application\Validator\ValidatorInterface;
use Tailgate\Domain\Model\User\User;
use Tailgate\Domain\Model\User\UserRepositoryInterface;
use Tailgate\Domain\Service\PasswordHashing\PasswordHashingInterface;
use Tailgate\Domain\Service\Validatable;
use Tailgate\Domain\Service\ValidatableService;

class RegisterUserHandler implements ValidatableService
{
    use Validatable;

    private $validator;
    private $userRepository;
    private $passwordHashing;

    public function __construct(
        ValidatorInterface $validator,
        UserRepositoryInterface $userRepository,
        PasswordHashingInterface $passwordHashing
    ) {
        $this->validator = $validator;
        $this->userRepository = $userRepository;
        $this->passwordHashing = $passwordHashing;
    }

    public function handle(RegisterUserCommand $command)
    {
        $this->validate($command);

        $email = $command->getEmail();
        $password = $command->getPassword();

        $user = User::create(
            $this->userRepository->nextIdentity(),
            $email,
            $this->passwordHashing->hash($password)
        );

        $this->userRepository->add($user);

        return [
            'userId'   => $user->getId(),
            'email'    => $user->getEmail(),
            'status'   => $user->getStatus(),
            'role'     => $user->getRole()
        ];
    }
}
