<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Json\Route\Session;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Api\Json\AbstractApiMethod;
use Ampache\Module\Api\Json\ErrorHandling\Exception\LoginFailedException;
use Ampache\Module\Api\Json\ErrorHandling\Exception\LoginRestrictedException;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\Authentication\Jwt\JwtFactoryInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\EnvironmentInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Allows users to login an obtain a jwt
 *
 * Example: POST /session/login
 */
final class Login extends AbstractApiMethod
{
    private ConfigContainerInterface $configContainer;

    private UserRepositoryInterface $userRepository;

    private ModelFactoryInterface $modelFactory;

    private NetworkCheckerInterface $networkChecker;

    private AuthenticationManagerInterface $authenticationManager;

    private JwtFactoryInterface $jwtFactory;

    private LoggerInterface $logger;

    private EnvironmentInterface $environment;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UserRepositoryInterface $userRepository,
        ModelFactoryInterface $modelFactory,
        NetworkCheckerInterface $networkChecker,
        AuthenticationManagerInterface $authenticationManager,
        JwtFactoryInterface $jwtFactory,
        LoggerInterface $logger,
        EnvironmentInterface $environment
    ) {
        $this->configContainer       = $configContainer;
        $this->userRepository        = $userRepository;
        $this->modelFactory          = $modelFactory;
        $this->networkChecker        = $networkChecker;
        $this->authenticationManager = $authenticationManager;
        $this->jwtFactory            = $jwtFactory;
        $this->logger                = $logger;
        $this->environment           = $environment;
    }

    /**
     * @return mixed
     *
     * @throws LoginRestrictedException
     * @throws LoginFailedException
     */
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $arguments
    ) {
        $data     = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $this->logger->info(
            sprintf('Login attempt, IP:%s User:%s', $this->environment->getClientIp(), $username),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $result = $this->authenticationManager->login(
            $username,
            $password
        );

        if ($result['success'] === false) {
            $this->logger->warning(
                sprintf('Login failed, IP:%s User:%s', $this->environment->getClientIp(), $username),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            throw new LoginFailedException(
                sprintf('Login failed')
            );
        }

        $userId = $this->userRepository->findByUsername($username);

        if (!$this->networkChecker->check(AccessLevelEnum::TYPE_API, $userId, AccessLevelEnum::LEVEL_GUEST)) {
            $this->logger->warning(
                sprintf('Login is restricted, IP:%s User:%s', $this->environment->getClientIp(), $username),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            throw new LoginRestrictedException(
                sprintf('Login is restricted')
            );
        }

        $user = $this->modelFactory->createUser($userId);

        $token = $this->jwtFactory->createJwt()->encode([
            'iss' => 'ampache-api',
            'sub' => (string) $userId,
            'level' => (string) $user->access,
        ]);

        return [
            'jwt' => $token
        ];
    }
}
