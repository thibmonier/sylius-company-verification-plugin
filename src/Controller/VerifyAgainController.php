<?php

declare(strict_types=1);

namespace Tmonier\SyliusCompanyVerificationPlugin\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Entity\CompanyInterface;
use Tmonier\SyliusCompanyAccountsPlugin\Repository\CompanyRepositoryInterface;
use Tmonier\SyliusCompanyVerificationPlugin\Verification\CompanyVerifier;

final class VerifyAgainController
{
    public const ROLE = 'ROLE_ADMINISTRATION_ACCESS';

    public static function tokenId(int $companyId): string
    {
        return 'tmonier_company_verification_' . $companyId;
    }

    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CompanyVerifier $verifier,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Request $request, int $id): RedirectResponse
    {
        if (!$this->authorizationChecker->isGranted(self::ROLE)) {
            throw new AccessDeniedHttpException();
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::tokenId($id), (string) $request->request->get('_csrf_token')))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $company = $this->companyRepository->find($id);
        if (!$company instanceof CompanyInterface) {
            throw new NotFoundHttpException();
        }

        $this->verifier->verify($company);

        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('success', $this->translator->trans('tmonier_company_verification.flash.done'));
        }

        return new RedirectResponse($this->urlGenerator->generate('tmonier_company_accounts_admin_company_update', ['id' => $id]));
    }
}
