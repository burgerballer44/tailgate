<?php

namespace Tailgate\Domain\Service\User;

use Tailgate\Application\Command\User\ResetPasswordCommand;
use Tailgate\Application\Validator\ValidatorInterface;
use Tailgate\Domain\Model\User\User;
use Tailgate\Domain\Model\User\UserId;
use Tailgate\Domain\Model\User\UserRepositoryInterface;
use Tailgate\Domain\Service\PasswordHashing\PasswordHashingInterface;
use Tailgate\Domain\Service\Validatable;
use Tailgate\Domain\Service\ValidatableService;

class ResetPasswordHandler implements ValidatableService
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

    public function handle(ResetPasswordCommand $command)
    {
        $this->validate($command);
        
        $userId = $command->getUserId();
        $password = $command->getPassword();

        $user = $this->userRepository->get(UserId::fromString($userId));

        $user->updatePassword($this->passwordHashing->hash($password));

        $this->userRepository->add($user);
    }
}
