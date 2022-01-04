<?php

declare(strict_types=1);

namespace Sonnenglas\MyDHLApi\ValueObjects;

use Sonnenglas\MyDHLApi\Exceptions\InvalidAddressException;

class RateAddress
{
    /**
     * @throws InvalidAddressException
     */
    public function __construct(
        protected string $countryCode,
        protected string $postalCode,
        protected string $cityName,
    ) {
        $this->countryCode = strtoupper($this->countryCode);

        $this->validateData();
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCityName(): string
    {
        return $this->cityName;
    }

    /**
     * @throws InvalidAddressException
     */
    protected function validateData(): void
    {
        if (strlen($this->countryCode) !== 2) {
            throw new InvalidAddressException("Country Code must be 2 characters long. Entered: {$this->countryCode}");
        }

        if (strlen($this->postalCode) < 3) {
            throw new InvalidAddressException("Postal Code must be at least 3 characters long. Entered: {$this->postalCode}");
        }

        if (strlen($this->cityName) === 0) {
            throw new InvalidAddressException("City name must not be empty. Entered: {$this->cityName}");
        }
    }
}
