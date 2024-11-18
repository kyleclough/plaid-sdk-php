<?php

namespace KyleClough\Plaid;

use Psr\Http\Client\ClientInterface;
use ReflectionClass;
use Shuttle\Shuttle;
use KyleClough\Plaid\Resources\AbstractResource;
use UnexpectedValueException;

/**
 * @property \KyleClough\Plaid\Resources\Accounts $accounts
 * @property \KyleClough\Plaid\Resources\Auth $auth
 * @property \KyleClough\Plaid\Resources\BankTransfers $bank_transfers
 * @property \KyleClough\Plaid\Resources\Categories $categories
 * @property \KyleClough\Plaid\Resources\Institutions $institutions
 * @property \KyleClough\Plaid\Resources\Investments	$investments
 * @property \KyleClough\Plaid\Resources\Items $items
 * @property \KyleClough\Plaid\Resources\Liabilities $liabilities
 * @property \KyleClough\Plaid\Resources\Tokens $tokens
 * @property \KyleClough\Plaid\Resources\Payments $payments
 * @property \KyleClough\Plaid\Resources\Processors $processors
 * @property \KyleClough\Plaid\Resources\Reports $reports
 * @property \KyleClough\Plaid\Resources\Sandbox $sandbox
 * @property \KyleClough\Plaid\Resources\Transactions $transactions
 * @property \KyleClough\Plaid\Resources\Webhooks $webhooks
 */
class Plaid
{
	const API_VERSION = "2020-09-14";

	/**
	 * Plaid client Id.
	 *
	 * @var string
	 */
	protected $client_id;

	/**
	 * Plaid client secret.
	 *
	 * @var string
	 */
	protected $client_secret;

	/**
	 * Plaid API host environment.
	 *
	 * @var string
	 */
	protected $environment = "production";

	/**
	 * Plaid API environments and matching hostname.
	 *
	 * @var array<string,string>
	 */
	protected $plaidEnvironments = [
		"production" => "https://production.plaid.com/",
		"development" => "https://development.plaid.com/",
		"sandbox" => "https://sandbox.plaid.com/",
	];

	/**
	 * ClientInterface instance.
	 *
	 * @var ClientInterface|null
	 */
	protected $httpClient;

	/**
	 * Resource instance cache.
	 *
	 * @var array<AbstractResource>
	 */
	protected $resource_cache = [];

	/**
	 * @param string $client_id
	 * @param string $client_secret
	 * @param string $environment Possible values are: production, development, sandbox
	 * @throws UnexpectedValueException
	 */
	public function __construct(
		string $client_id,
		string $client_secret,
		string $environment = "production")
	{
		if( !\array_key_exists($environment, $this->plaidEnvironments) ){
			throw new UnexpectedValueException("Invalid environment. Environment must be one of: production, development, or sandbox.");
		}

		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->environment = $environment;
	}

	/**
	 * Magic getter for resources.
	 *
	 * @param string $resource
	 * @throws UnexpectedValueException
	 * @return AbstractResource
	 */
	public function __get(string $resource): AbstractResource
	{
		if( !isset($this->resource_cache[$resource]) ){

			$resource = \str_replace([" "], "", \ucwords(\str_replace(["_"], " ", $resource)));

			$resource_class = "\\KyleClough\\Plaid\\Resources\\" . $resource;

			if( !\class_exists($resource_class) ){
				throw new UnexpectedValueException("Unknown Plaid resource: {$resource}");
			}

			$reflectionClass = new ReflectionClass($resource_class);

			/**
			 * @var AbstractResource $resource_instance
			 */
			$resource_instance = $reflectionClass->newInstanceArgs([
				$this->getHttpClient(),
				$this->client_id,
				$this->client_secret,
				$this->plaidEnvironments[$this->environment]
			]);

			$this->resource_cache[$resource] = $resource_instance;
		}

		return $this->resource_cache[$resource];
	}

	/**
	 * Set a specific ClientInterface instance to be used to make HTTP calls.
	 *
	 * @param ClientInterface $httpClient
	 * @return void
	 */
	public function setHttpClient(ClientInterface $httpClient): void
	{
		$this->httpClient = $httpClient;
	}

	/**
	 * Get the ClientInterface instance being used to make HTTP calls.
	 *
	 * @return ClientInterface
	 */
	public function getHttpClient(): ClientInterface
	{
		if( empty($this->httpClient) ){
			$this->httpClient = new Shuttle;
		}

		return $this->httpClient;
	}
}
