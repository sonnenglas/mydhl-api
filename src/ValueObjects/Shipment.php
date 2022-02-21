<?php

declare(strict_types=1);

namespace Sonnenglas\MyDHL\ValueObjects;

use Sonnenglas\MyDHL\Exceptions\InvalidAddressException;

class Shipment
{
    public function __construct(
        private string $shipmentTrackingNumber,
        private string $cancelPickupUrl,
        private string $trackingUrl,
        private string $dispatchConfirmationNumber,
        private string $labelPdf,
        private array $warnings = [],
        private array $packages = [],
        private array $documents = [],
        private array $shipmentDetails = [],
        private array $shipmentCharges = [],
    ) {
    }

    /**
     * @return string
     */
    public function getLabelPdf(): string
    {
        return $this->labelPdf;
    }

    /**
     * @return array
     */
    public function getShipmentCharges(): array
    {
        return $this->shipmentCharges;
    }


    /**
     * @return string
     */
    public function getShipmentTrackingNumber(): string
    {
        return $this->shipmentTrackingNumber;
    }

    /**
     * @return string
     */
    public function getCancelPickupUrl(): string
    {
        return $this->cancelPickupUrl;
    }

    /**
     * @return string
     */
    public function getTrackingUrl(): string
    {
        return $this->trackingUrl;
    }

    /**
     * @return string
     */
    public function getDispatchConfirmationNumber(): string
    {
        return $this->dispatchConfirmationNumber;
    }

    /**
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * @return array
     */
    public function getPackages(): array
    {
        return $this->packages;
    }

    /**
     * @return array
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    /**
     * @return array
     */
    public function getShipmentDetails(): array
    {
        return $this->shipmentDetails;
    }

    public function __toString(): string
    {
        return json_encode($this->getAsArray(), JSON_THROW_ON_ERROR);
    }

    public function getAsArray(): array
    {
        $values = get_object_vars($this);

        // Remove labelPdf since it's duplicate of $values['documents'][0]['content']
        unset($values['labelPdf']);

        return $values;
    }
}
