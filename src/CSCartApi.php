<?php
declare(strict_types=1);

class CSCartApi {

    const VERSION = '0.3';
    const ERROR_CURL_ERROR = 'HTTP error while calling the API. Error code and message: ';
	const ERROR_JSON_ERROR = 'JSON Error: ';

    private static $CURL_OPTS = [
        CURLOPT_CONNECTTIMEOUT  => 10,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 60,
        CURLOPT_USERAGENT       => 'csCart-api-php-' . self::VERSION,
    ];

    protected $apiKey;
    protected $userLogin;
    protected $apiUrl;
	/**
	 * @param $apiKey
	 * @return $this
	 */
	public function setApiKey($apiKey): self {
        $this->apiKey = $apiKey;
        return $this;
    }

	/**
	 * @param $userLogin
	 * @return $this
	 */
	public function setUserLogin($userLogin): self {
        $this->userLogin = $userLogin;
        return $this;
    }

	/**
	 * @param $apiUrl
	 * @return $this
	 */
	public function setApiUrl($apiUrl): self {
        $this->apiUrl = trim($apiUrl, '/').'/api/';
        return $this;
    }

    public function getApiKey() {
        return $this->apiKey;
    }

    public function getUserLogin() {
        return $this->userLogin;
    }

    public function getApiUrl() {
        return $this->apiUrl;
    }

	/**
	 * @param string $method
	 * @param string $url
	 * @param string $data
	 * @param array $params
	 * @return stdClass
	 * @throws cscartException
	 */
	public function api(string $method, string $url, string $data = '', $params = []): stdClass {
        return $this->makeRequest($url, $method, $data, $params);
    }

	/**
	 * @param string $objectUrl
	 * @param string $method
	 * @param $data
	 * @param array $params
	 * @return stdClass|null
	 * @throws cscartException
	 */
	protected function makeRequest(string $objectUrl, string $method, $data = '', $params = []) {

		$ch = curl_init();

        $opts = self::$CURL_OPTS;

        $this->setOpts($opts, CURLOPT_URL, $this->initUrl($objectUrl, $params));
		$this->setOpts($opts, CURLOPT_USERPWD, $this->getAuthString());
        $this->setHeader($opts, 'Content-Type: application/json');

        switch ($method) {
            case 'GET':
                break;
            case 'POST':
	        case 'PUT':

                $postData = json_encode($data);

				$this->setOpts($opts, CURLOPT_CUSTOMREQUEST, $method)
		            ->setOpts($opts, CURLOPT_RETURNTRANSFER, true)
		            ->setOpts($opts,CURLOPT_POSTFIELDS, $postData);
                $this->setHeader($opts, 'Content-Length: ' . strlen($postData));
                break;
            case 'DELETE':
				$this->setOpts($opts, CURLOPT_CUSTOMREQUEST, $method);
                break;

	        default:
				throw new cscartException('Unknown method: ' . $method);
        }

        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);

        if (empty($result)) {
            throw new cscartException(self::ERROR_CURL_ERROR . curl_errno($ch) . ' : ' . curl_error($ch));
        }

        curl_close($ch);

        $json = json_decode($result, false);

        if(json_last_error() === JSON_ERROR_NONE) {
			return $json;
        }

        throw new cscartException(self::ERROR_JSON_ERROR . json_last_error_msg());

    }

	/**
	 * @param string $url
	 * @param array $params
	 * @return string
	 */
	private function initUrl(string $url, array $params): string {

        $query  = http_build_query($params);
		$query = $query ? '?' . $query : '';

        return $this->getApiUrl() . $url . $query;

    }

	/**
	 * @return string
	 */
	protected function getAuthString(): string {
        return $this->userLogin . ":" . $this->apiKey;
    }

	/**
	 * @param $opts
	 * @param $headerString
	 */
	protected function setHeader(&$opts, $headerString) {
        $opts[CURLOPT_HTTPHEADER][] = $headerString;
    }

	/**
	 * @param array $opts
	 * @param $option
	 * @param $value
	 * @return $this
	 */
	private function setOpts(array &$opts, $option, $value): self {
		$opts[$option] = $value;
		return $this;
    }

	/**
	 * @param string $objectUrl
	 * @param array $params
	 * @return stdClass
	 * @throws cscartException
	 */
	public function get(string $objectUrl, array $params = []): stdClass {
        return $this->makeRequest($objectUrl, 'GET', '', $params);
    }

	/**
	 * @param string $objectUrl
	 * @param array $data
	 * @return stdClass
	 * @throws cscartException
	 */
	public function update(string $objectUrl, array $data): stdClass {
        return $this->makeRequest($objectUrl, 'PUT', $data);
    }

	/**
	 * @param string $objectUrl
	 * @param array $data
	 * @return stdClass
	 * @throws cscartException
	 */
	public function create(string $objectUrl, array $data): stdClass {
        return $this->makeRequest($objectUrl, 'POST', $data);
    }

	/**
	 * @param string $objectUrl
	 * @return stdClass
	 * @throws cscartException
	 */
	public function delete(string $objectUrl): stdClass {
        return $this->makeRequest($objectUrl, 'DELETE');
    }

	/**
	 * @return string
	 */
	public function getApiVersion(): string {
        return self::VERSION;
    }

	/**
	 * @return string
	 * @throws cscartException
	 */
	public function getCartVersion(): string {

		$version = $this->makeRequest($this->apiUrl.'?version', 'GET');
        return str_replace("CS-Cart: version ","", strip_tags($version));

    }

}
