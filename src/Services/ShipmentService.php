<?php

declare(strict_types=1);

namespace Sonnenglas\MyDHL\Services;

use DateTimeImmutable;
use Sonnenglas\MyDHL\Client;
use Sonnenglas\MyDHL\Exceptions\MissingArgumentException;
use Sonnenglas\MyDHL\ResponseParsers\ShipmentResponseParser;
use Sonnenglas\MyDHL\Traits\ConvertBoolToString;
use Sonnenglas\MyDHL\ValueObjects\Account;
use Sonnenglas\MyDHL\ValueObjects\Address;
use Sonnenglas\MyDHL\ValueObjects\Contact;
use Sonnenglas\MyDHL\Exceptions\InvalidArgumentException;
use Sonnenglas\MyDHL\ValueObjects\Package;
use Sonnenglas\MyDHL\ValueObjects\Shipment;

class ShipmentService
{
    use ConvertBoolToString;

    private DateTimeImmutable $plannedShippingDateAndTime;

    private bool $isPickupRequested;
    private string $pickupCloseTime;
    private string $pickupLocation;
    private Address $pickupAddress;
    private Contact $pickupContact;
    private string $productCode;
    private string $localProductCode;
    private Address $shipperAddress;
    private Contact $shipperContact;
    private Address $receiverAddress;
    private Contact $receiverContact;
    private bool $getRateEstimates;

    /**
     * @var array<Account>
     */
    private array $accounts;

    /**
     * @var array<Package>
     */
    private array $packages;

    private array $requiredArguments = [
        'plannedShippingDateAndTime',
        'isPickupRequested',
        'productCode',
        'shipperAddress',
        'shipperContact',
        'receiverAddress',
        'receiverContact',
        'accounts',
        'packages',
    ];


    private const CREATE_SHIPMENT_URL = 'shipments';


    public function __construct(private Client $client)
    {
    }

    public function sendShipment(): Shipment
    {
        $this->validateParams();
        $query = $this->prepareQuery();
        $response = $this->client->post(self::CREATE_SHIPMENT_URL, $query);
        return (new ShipmentResponseParser())->parse($response);
    }

    public function setPlannedShippingDateAndTime(DateTimeImmutable $date): ShipmentService
    {
        $this->plannedShippingDateAndTime = $date;

        return $this;
    }

    /**
     * @param bool $isPickupRequested Please advise if a pickup is needed for this shipment
     * @param string $pickupCloseTime The latest time the location premises is available to dispatch the DHL Express shipment. (HH:MM)
     * @param string $pickupLocation Provides information on where the package should be picked up by DHL courier
     * @return $this
     */
    public function setPickup(bool $isPickupRequested, string $pickupCloseTime = '', string $pickupLocation = ''): ShipmentService
    {
        $this->isPickupRequested = $isPickupRequested;
        $this->pickupCloseTime = $pickupCloseTime;
        $this->pickupLocation = $pickupLocation;

        return $this;
    }

    public function setPickupDetails(Address $pickupAddress, Contact $pickupContact): ShipmentService
    {
        $this->pickupAddress = $pickupAddress;
        $this->pickupContact = $pickupContact;

        return $this;
    }

    public function setProductCode(string $productCode): ShipmentService
    {
        $this->productCode = $productCode;

        return $this;
    }

    public function setLocalProductCode(string $localProductCode): ShipmentService
    {
        $this->localProductCode = $localProductCode;

        return $this;
    }

    /**
     * @param array<Account> $accounts
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setAccounts(array $accounts): ShipmentService
    {
        foreach ($accounts as $account) {
            if (!$account instanceof Account) {
                throw new InvalidArgumentException("Array should contain values of type Account");
            }
        }

        $this->accounts = $accounts;

        return $this;
    }

    public function setShipperDetails(Address $shipperAddress, Contact $shipperContact): ShipmentService
    {
        $this->shipperAddress = $shipperAddress;
        $this->shipperContact = $shipperContact;

        return $this;
    }

    public function setReceiverDetails(Address $receiverAddress, Contact $receiverContact): ShipmentService
    {
        $this->receiverAddress = $receiverAddress;
        $this->receiverContact = $receiverContact;

        return $this;
    }

    public function setGetRateEstimates(bool $getRateEstimates): ShipmentService
    {
        $this->getRateEstimates = $getRateEstimates;

        return $this;
    }

    /**
     * @param array<Package> $packages
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setPackages(array $packages): ShipmentService
    {
        foreach ($packages as $package) {
            if (!$package instanceof Package) {
                throw new InvalidArgumentException("Array should contain values of type Package");
            }
        }

        $this->packages = $packages;

        return $this;
    }

    private function prepareQuery(): array
    {
        $query = [
            'plannedShippingDateAndTime' => $this->plannedShippingDateAndTime->format(DateTimeImmutable::ATOM),
            'accounts' => $this->prepareAccountsQuery(),
            'customerDetails' => [
                'shipperDetails' => [
                    'postalAddress' => $this->shipperAddress->getAsArray(),
                    'contactInformation' => $this->shipperContact->getAsArray(),
                ],
                'receiverDetails' => [
                    'postalAddress' => $this->receiverAddress->getAsArray(),
                    'contactInformation' => $this->receiverContact->getAsArray(),
                ],
            ],
            'content' => [
                'packages' => $this->preparePackagesQuery(),
            ],
            'getRateEstimates' => $this->convertBoolToString($this->getRateEstimates),
            'productCode' => $this->productCode,
        ];

        if ($this->localProductCode !== '') {
            $query['localProductCode'] = $this->localProductCode;
        }

        if ($this->receiverContact->getEmail() !== '') {
            $query['shipmentNotification'] = [
                'typeCode' => 'email',
                'languageCountryCode' => $this->receiverAddress->getCountryCode(),
                'receiverId' => $this->receiverContact->getEmail(),
            ];
        }

        if ($this->isPickupRequested) {
            $query['pickup'] = [
                'isRequested' => $this->convertBoolToString($this->isPickupRequested),
                'closeTime' => $this->pickupCloseTime,
                'location' => $this->pickupLocation,
            ];

            $query['pickupDetails'] = [
                'postalAddress' => $this->pickupAddress->getAsArray(),
                'contactInformation' => $this->pickupContact->getAsArray(),
            ];
        }

        return $query;
    }

    private function prepareAccountsQuery(): array
    {
        $accounts = [];

        /** @var Account $account */
        foreach ($this->accounts as $account) {
            $accounts[] = $account->getAsArray();
        }

        return $accounts;
    }

    private function preparePackagesQuery(): array
    {
        $packages = [];

        foreach ($this->packages as $package) {
            $packages[] = [
                'weight' => $package->getWeight(),
                'dimensions' => [
                   'length' => $package->getLength(),
                   'width' => $package->getWidth(),
                   'height' => $package->getHeight(),
                ],
            ];
        }

        return $packages;
    }

    /**
     * @return void
     * @throws MissingArgumentException
     */
    private function validateParams(): void
    {
        foreach ($this->requiredArguments as $param) {
            if (!isset($this->{$param})) {
                throw new MissingArgumentException("Missing argument: {$param}");
            }
        }
    }
}
