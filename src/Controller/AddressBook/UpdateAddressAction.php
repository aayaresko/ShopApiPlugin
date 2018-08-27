<?php

declare(strict_types=1);

namespace Sylius\ShopApiPlugin\Controller\AddressBook;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use League\Tactician\CommandBus;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Core\Repository\AddressRepositoryInterface;
use Sylius\ShopApiPlugin\Command\UpdateAddress;
use Sylius\ShopApiPlugin\Factory\AddressBookViewFactoryInterface;
use Sylius\ShopApiPlugin\Factory\ValidationErrorViewFactoryInterface;
use Sylius\ShopApiPlugin\Model\Address;
use Sylius\ShopApiPlugin\Provider\LoggedInUserProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UpdateAddressAction
{
    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var CommandBus
     */
    private $bus;

    /**
     * @var ValidationErrorViewFactoryInterface
     */
    private $validationErrorViewFactory;

    /**
     * @var AddressBookViewFactoryInterface
     */
    private $addressBookViewFactory;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var LoggedInUserProviderInterface
     */
    private $currentUserProvider;

    /**
     * @param ViewHandlerInterface                $viewHandler
     * @param ValidatorInterface                  $validator
     * @param CommandBus                          $bus
     * @param ValidationErrorViewFactoryInterface $validationErrorViewFactory
     * @param AddressBookViewFactoryInterface     $addressViewFactory
     * @param AddressRepositoryInterface          $addressRepository
     * @param TokenStorageInterface               $tokenStorage
     * @param LoggedInUserProviderInterface       $currentUserProvider
     */
    public function __construct(
        ViewHandlerInterface $viewHandler,
        ValidatorInterface $validator,
        CommandBus $bus,
        ValidationErrorViewFactoryInterface $validationErrorViewFactory,
        AddressBookViewFactoryInterface $addressViewFactory,
        AddressRepositoryInterface $addressRepository,
        LoggedInUserProviderInterface $currentUserProvider
    ) {
        $this->viewHandler                = $viewHandler;
        $this->validator                  = $validator;
        $this->bus                        = $bus;
        $this->validationErrorViewFactory = $validationErrorViewFactory;
        $this->addressBookViewFactory     = $addressViewFactory;
        $this->addressRepository          = $addressRepository;
        $this->currentUserProvider        = $currentUserProvider;
    }

    public function __invoke(Request $request, $id): Response
    {
        $addressModel = Address::createFromRequest($request);

        $validationResults = $this->validator->validate($addressModel);

        if (0 !== count($validationResults)) {
            return $this->viewHandler->handle(
                View::create($this->validationErrorViewFactory->create($validationResults), Response::HTTP_BAD_REQUEST)
            );
        }

        try {
            /** @var ShopUserInterface $user */
            $user = $this->currentUserProvider->provide();
        } catch (TokenNotFoundException $exception) {
            return $this->viewHandler->handle(View::create(null, Response::HTTP_UNAUTHORIZED));
        }

        if ($user->getCustomer() !== null) {
            $this->bus->handle(new UpdateAddress($addressModel, $user->getEmail(), $id));

            /** @var AddressInterface $updatedAddress */
            $updatedAddress = $this->addressRepository->findOneBy(['id' => $id]);

            $view = View::create(
                $this->addressBookViewFactory->create($updatedAddress, $user->getCustomer()),
                Response::HTTP_OK
            );
        } else {
            $view = View::create(['message' => 'The user is not a customer'], Response::HTTP_BAD_REQUEST);
        }

        return $this->viewHandler->handle($view);
    }
}
