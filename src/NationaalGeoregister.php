<?php

declare(strict_types=1);

namespace Swis\Geocoder\NationaalGeoregister;

use Geocoder\Collection;
use Geocoder\Exception\InvalidServerResponse;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Model\AddressBuilder;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Psr\Http\Client\ClientInterface;

class NationaalGeoregister extends AbstractHttpProvider implements Provider
{
    /**
     * @var string
     */
    protected const ENDPOINT_URL_FREE = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/free?%s';

    /**
     * @var string
     */
    protected const ENDPOINT_URL_REVERSE = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/reverse?%s';

    /**
     * @var string
     */
    protected const ENDPOINT_URL_SUGGEST = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/suggest?%s';

    /**
     * @var string
     */
    protected const ENDPOINT_URL_LOOKUP = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/lookup?%s';

    /**
     * @var string[]
     */
    protected const BLACKLISTED_OPTIONS = [
        'fl',
        'rows',
        'type',
        'wt',
    ];

    /**
     * @var array
     */
    protected const DEFAULT_OPTIONS = [
        //'bq' => 'type:gemeente^0.5 type:woonplaats^0.5 type:weg^1.0 type:postcode^1.5 type:adres^1.5',
        'fl' => 'weergavenaam,id,type,centroide_ll,huis_nlt,huisnummer,straatnaam,postcode,woonplaatsnaam,gemeentenaam,gemeentecode,provincienaam,provinciecode,buurtnaam,buurtcode,wijknaam,wijkcode,geometrie_rd',
    ];

    /**
     * @var array
     */
    protected const DEFAULT_OPTIONS_SUGGEST_HOUSE = [
        'fl' => 'weergavenaam,id,type,centroide_ll,huis_nlt,huisnummer,straatnaam,postcode,woonplaatsnaam,gemeentenaam,gemeentecode,provincienaam,provinciecode,buurtnaam,buurtcode,wijknaam,wijkcode,geometrie_rd',
    ];

    /**
     * @var array
     */
    protected const DEFAULT_OPTIONS_SUGGEST_STREET = [
        'fl' => 'weergavenaam,id,type,centroide_ll,huis_nlt,huisnummer,straatnaam,postcode,woonplaatsnaam,gemeentenaam,gemeentecode,provincienaam,provinciecode,buurtnaam,buurtcode,wijknaam,wijkcode,geometrie_rd',
    ];

    /**
     * @var array
     */
    protected const DEFAULT_OPTIONS_SUGGEST_POSTCODE = [
        'fl' => 'weergavenaam,id,type,centroide_ll,huis_nlt,huisnummer,straatnaam,postcode,woonplaatsnaam,gemeentenaam,gemeentecode,provincienaam,provinciecode,buurtnaam,buurtcode,wijknaam,wijkcode,geometrie_rd',
    ];

    /**
     * @var array
     */
    protected const DEFAULT_OPTIONS_GEOCODE = [
        'bq' => 'type:gemeente^0.5 type:woonplaats^0.5 type:weg^1.0 type:postcode^1.5 type:adres^1.5',
    ];

    /**
     * @var array
     */
    protected const REQUIRED_OPTIONS_GEOCODE = [];

    /**
     * @var array
     */
    protected const DEFAULT_OPTIONS_REVERSE = [];

    /**
     * @var array
     */
    protected const REQUIRED_OPTIONS_REVERSE = [
        'type' => 'adres',
    ];

    /**
     * @var array
     */
    protected const REQUIRED_OPTIONS_SUGGEST = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param \Psr\Http\Client\ClientInterface $client  An HTTP adapter
     * @param array                            $options Extra query parameters (optional)
     */
    public function __construct(ClientInterface $client, array $options = [])
    {
        parent::__construct($client);

        $this->setOptions($options);
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_diff_key($options, array_fill_keys(self::BLACKLISTED_OPTIONS, true));
    }

    /**
     * @param \Geocoder\Query\GeocodeQuery $query
     *
     * @return \Geocoder\Collection
     * @throws \Geocoder\Exception\InvalidServerResponse
     *
     * @throws \Geocoder\Exception\UnsupportedOperation
     */
    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        // This API doesn't handle IPs.
        if (filter_var($query->getText(), FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The NationaalGeoregister provider does not support IP addresses.');
        }

        return $this->executeQuery(sprintf(self::ENDPOINT_URL_FREE, http_build_query($this->getGeocodeOptions($query))));
    }

    /**
     * @param \Geocoder\Query\GeocodeQuery $query
     *
     * @return array
     */
    protected function getGeocodeOptions(GeocodeQuery $query): array
    {
        return array_merge(
            static::DEFAULT_OPTIONS,
            static::DEFAULT_OPTIONS_GEOCODE,
            $this->options,
            array_diff_key($query->getAllData(), array_fill_keys(self::BLACKLISTED_OPTIONS, true)),
            static::REQUIRED_OPTIONS_GEOCODE,
            [
                'rows' => $query->getLimit(),
                'q' => $query->getText(),
            ]
        );
    }

    /**
     * @param \Geocoder\Query\ReverseQuery $query
     *
     * @return \Geocoder\Collection
     * @throws \Geocoder\Exception\InvalidServerResponse
     *
     */
    public function reverseQuery(ReverseQuery $query): Collection
    {
        return $this->executeQuery(sprintf(self::ENDPOINT_URL_REVERSE, http_build_query($this->getReverseOptions($query))));
    }

    /**
     * @param \Geocoder\Query\ReverseQuery $query
     *
     * @return array
     */
    protected function getReverseOptions(ReverseQuery $query): array
    {
        return array_merge(
            static::DEFAULT_OPTIONS,
            static::DEFAULT_OPTIONS_REVERSE,
            $this->options,
            array_diff_key($query->getAllData(), array_fill_keys(self::BLACKLISTED_OPTIONS, true)),
            static::REQUIRED_OPTIONS_REVERSE,
            [
                'rows' => $query->getLimit(),
                'lat' => $query->getCoordinates()->getLatitude(),
                'lon' => $query->getCoordinates()->getLongitude(),
            ]
        );
    }

    /**
     * @param \Geocoder\Query\GeocodeQuery $query
     * @param mixed $type
     *
     * @return \Geocoder\Collection
     * @throws \Geocoder\Exception\InvalidServerResponse
     *
     * @throws \Geocoder\Exception\UnsupportedOperation
     */
    public function suggestQuery(GeocodeQuery $query, $type = 'adres'): Collection
    {
        // This API doesn't handle IPs.
        if (filter_var($query->getText(), FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The NationaalGeoregister provider does not support IP addresses.');
        }

        switch ($type) {
            default:
            case 'adres':
                return $this->executeQuery(sprintf(self::ENDPOINT_URL_SUGGEST, http_build_query($this->getSuggestOptions($query, $type))));

            case 'weg':
                return $this->executeQuery(sprintf(self::ENDPOINT_URL_SUGGEST, http_build_query($this->getSuggestOptions($query, $type))));

            case 'postcode':
                return $this->executeQuery(sprintf(self::ENDPOINT_URL_SUGGEST, http_build_query($this->getSuggestOptions($query, $type))));
        }
    }

    /**
     * @param \Geocoder\Query\GeocodeQuery $query
     * @param mixed $type
     *
     * @return array
     */
    protected function getSuggestOptions(GeocodeQuery $query, $type = 'adres'): array
    {
        switch ($type) {
            default:
            case 'adres':
                return array_merge(
                    static::DEFAULT_OPTIONS_SUGGEST_HOUSE,
                    $this->options,
                    array_diff_key($query->getAllData(), array_fill_keys(self::BLACKLISTED_OPTIONS, true)),
                    static::REQUIRED_OPTIONS_SUGGEST,
                    [
                        'rows' => $query->getLimit(),
                        'q' => $query->getText() . ' and type:' . $type . ' and postcode:*',
                    ]
                );

            case 'weg':
                return array_merge(
                    static::DEFAULT_OPTIONS_SUGGEST_STREET,
                    $this->options,
                    array_diff_key($query->getAllData(), array_fill_keys(self::BLACKLISTED_OPTIONS, true)),
                    static::REQUIRED_OPTIONS_SUGGEST,
                    [
                        'rows' => $query->getLimit(),
                        'q' => $query->getText() . ' and type:' . $type,
                    ]
                );

            case 'postcode':
                return array_merge(
                    static::DEFAULT_OPTIONS_SUGGEST_POSTCODE,
                    $this->options,
                    array_diff_key($query->getAllData(), array_fill_keys(self::BLACKLISTED_OPTIONS, true)),
                    static::REQUIRED_OPTIONS_SUGGEST,
                    [
                        'rows' => $query->getLimit(),
                        'q' => $query->getText() . ' and type:' . $type,
                    ]
                );
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'nationaal_georegister';
    }

    /**
     * @param string $query
     *
     * @return \Geocoder\Model\AddressCollection
     * @throws \Geocoder\Exception\InvalidServerResponse
     *
     */
    protected function executeQuery(string $query): AddressCollection
    {
        $results = $this->getResultsForQuery($query);

        $addresses = [];
        foreach ($results->response->docs as $doc) {
            $position = explode(' ', trim(str_replace(['POINT(', ')'], '', $doc->centroide_ll)));

            $builder = new AddressBuilder($this->getName());

            $builder->setCoordinates((float)$position[1], (float)$position[0]);
            $builder->setStreetNumber($doc->huis_nlt ?? $doc->huisnummer ?? null);
            $builder->setStreetName($doc->straatnaam ?? null);
            $builder->setPostalCode($doc->postcode ?? null);
            $builder->setLocality($doc->woonplaatsnaam ?? null);
            if (isset($doc->buurtnaam)) {
                if (isset($doc->buurtcode)) {
                    $builder->addAdminLevel(5, $doc->buurtnaam, $doc->buurtcode);
                } else {
                    $builder->addAdminLevel(5, $doc->buurtnaam);
                }
            }
            if (isset($doc->wijknaam)) {
                if (isset($doc->wijkcode)) {
                    $builder->addAdminLevel(3, $doc->wijknaam, $doc->wijkcode);
                } else {
                    $builder->addAdminLevel(3, $doc->wijknaam);
                }
            }
            if (isset($doc->gemeentenaam)) {
                $builder->addAdminLevel(2, $doc->gemeentenaam, $doc->gemeentecode);
            }
            if (isset($doc->provincienaam)) {
                $builder->addAdminLevel(1, $doc->provincienaam, $doc->provinciecode);
            }
            $builder->setCountry('Netherlands');
            $builder->setCountryCode('NL');
            $builder->setTimezone('Europe/Amsterdam');

            /** @var PdokAddress $address */
            $address = $builder->build(PdokAddress::class);
            $address = $address->witType($doc->type);
            $address = $address->withId($doc->id);
            $address = $address->withAddress($doc->weergavenaam);
            $address = $address->withGeometry($doc->geometrie_rd);

            $addresses[] = $address;

        }

        return new AddressCollection($addresses);
    }

    /**
     * @param string $query
     *
     * @return \stdClass
     * @throws \Geocoder\Exception\InvalidServerResponse
     *
     */
    protected function getResultsForQuery(string $query): \stdClass
    {
        $content = $this->getUrlContents($query);

        $result = json_decode($content);

        if (json_last_error() === JSON_ERROR_UTF8) {
            $result = json_decode(utf8_encode($content));
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidServerResponse(sprintf('Could not execute query "%s": %s', $query, json_last_error_msg()));
        }

        return $result;
    }
}
