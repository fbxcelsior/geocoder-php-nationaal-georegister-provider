<?php


declare(strict_types=1);

namespace Swis\Geocoder\NationaalGeoregister;

use Geocoder\Model\Address;

final class PdokAddress extends Address
{
    /**
     * @var string|null
     *
     */
    private $id;

    /**
     * @var string|null
     *
     */
    private $type;

    /**
     * @var string|null
     *
     */
    private $address;

    /**
     * @param string|null $id
     *
     * @return PdokAddress
     */
    public function withId(string $id = null): self
    {
        $new = clone $this;
        $new->id = $id;

        return $new;
    }

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string|null $type
     *
     * @return PdokAddress
     */
    public function witType(string $type = null): self
    {
        $new = clone $this;
        $new->type = $type;

        return $new;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string|null $address
     *
     * @return PdokAddress
     */
    public function withAddress(string $address = null): self
    {
        $new = clone $this;
        $new->address = $address;

        return $new;
    }

    /**
     * @return string|null
     */
    public function getAddress()
    {
        return $this->address;
    }


    public function toArray(): array
    {
        $rv = parent::toArray();

        $rv["type"] = $this->getType();
        $rv["id"] = $this->getId();
        $rv["address"] = $this->getAddress();


        return $rv;
    }


}

