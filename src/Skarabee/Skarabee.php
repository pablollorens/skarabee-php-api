<?php

/**
 * Skarabee
 *
 * This Skarabee PHP Wrapper class connects to the Skarabee SOAP API, called Weblink.
 *
 * @author Jeroen Desloovere <jeroen@siesqo.be>
 */
class Skarabee
{
	// internal constant to enable/disable debugging
	const DEBUG = false;

	// the url for the api
	const WSDL_URL = 'http://weblink.skarabee.com/weblink.asmx?wsdl';

	// current version
	const VERSION = '1.0.0';

	/**
	 * The password that will be used for authenticating
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * The SOAP-client
	 *
	 * @var	SoapClient
	 */
	protected $soapClient;

	/**
	 * The timeout
	 *
	 * @var	int
	 */
	private $timeOut = 60;
	
	/**
	 * User agent
	 *
	 * @var string
	 */
	protected $userAgent;

	/**
	 * The username that will be used for authenticating
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * Construct the Skarabee API
	 *
	 * @param string $username
	 * @param string $password
	 */
	public function __construct($username, $password)
	{		
		// define variables
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * Add contact message - Attention: delay on server, messages arrive 1 minute after each hour.
	 *
	 * @param array $item
	 */
	public function addContactMessage($item)
	{
		// init parameters
		$parameters = array();

		// define required fields
		$requiredFields = array(
			'comments', 'first_name', 'last_name', array('phone', 'cell_phone', 'email')
		);

		// check if fields are set
		$this->checkFields($item, $requiredFields);

		// build required parameters
		$parameters['FirstName'] = (string) $item['first_name'];
		$parameters['LastName'] = (string) $item['last_name'];
		$parameters['Comments'] = (string) $item['comments'];
		$parameters['Email'] = (string) $item['email'];
		$parameters['HouseNumber'] = (isset($item['house_number'])) ? (int) $item['house_number'] : '';

		// add optional parameters
		if(isset($item['publication_id'])) $parameters['PublicationID'] = (int) $item['publication_id'];
		if(isset($item['external_reference'])) $parameters['ExternalReference'] = (string) $item['external_reference'];
		if(isset($item['cell_phone'])) $parameters['CellPhone'] = (string) $item['cell_phone'];
		if(isset($item['phone'])) $parameters['Phone'] = (string) $item['phone'];
		if(isset($item['street'])) $parameters['Street'] = (string) $item['street'];
		if(isset($item['house_number_extension'])) $parameters['HouseNumberExtension'] = (string) $item['house_number_extension'];
		if(isset($item['zip'])) $parameters['ZipCode'] = (string) $item['zip'];
		if(isset($item['city'])) $parameters['City'] = (string) $item['city'];

		// redefine parameters
		$parameters = array('ContactMes' => $parameters);

		// return call
		return $this->doCall('InsertContactMes', $parameters);
	}

	/**
	 * Check fields if they are set
	 *
	 * @param array $item
	 * @param array $requiredFields
	 */
	protected function checkFields($item, $requiredFields)
	{
		// loop required fields
		foreach($requiredFields as $field)
		{
			// init exists
			$exists = false;

			// one of multiple fields required
			if(is_array($field))
			{
				// loop all fields
				foreach($field as $arrayField)
				{
					// field exists in loop
					if(isset($item[$arrayField]))
					{
						// redefine exists
						$exists = true;

						// skip other fields in loop
						break;	
					}
				}

				// make them inline
				if(!$exists) $field = 'one of "' . implode(', ', $field) . '"';
			}

			// define is exists or not
			else $exists = isset($item[$field]);

			// throw error
			if(!$exists)
			{
				throw new SkarabeeException('Field is required: ' . $field);
			}
		}	
	}

	/**
	 * Call a certain method with its parameters
	 *
	 * @param string $method
	 * @param string $parameters
	 * @return SoapClient
	 */
	protected function doCall($method, $parameters = array())
	{
		// first time we call SoapClient
		if(!$this->soapClient)
		{
			try
			{
				// throw error
				if(empty($this->username) && empty($this->password))
				{
					throw new SkarabeeException('Username and password are empty.');
				}

				// define options
				$options = array(
					'login' => $this->username,
					'password' => $this->password,
					'soap_version'   => SOAP_1_1,
					'trace' => self::DEBUG,
					'exceptions' => false,
					'connection_timeout' => $this->getTimeOut(),
					'user_agent' => $this->getUserAgent()
				);

				// define SOAP client
				$this->soapClient = new SoapClient(self::WSDL_URL, $options);
			}
			catch(Exception $e)
			{
				// throw error
				throw new SkarabeeException($e);
			}
		}

		// define result method which contains the results
		$resultMethod = $method . 'Result';

		// define result
		$result = $this->soapClient->$method($parameters)->$resultMethod;

		// return results from called method
		return json_decode(json_encode($result), true);
	}

	/**
	 * Get a publication by id.
	 *
	 * @param int $id The publication ID.
	 * @return array
	 */
	public function get($id)
	{
		// init parameters
		$parameters = array();

		// define parameters
		$parameters['PublicationId'] = (int) $id;

		// define result
		$result = $this->doCall('GetPublication', $parameters);

		// return item
		return $result['Publication'];
	}

	/**
	 * Get all publications with optional filtering on date or type.
	 *
	 * @param string $sinceDate [optional] If no date given, all publications will be returned.
	 * @param mixed $types [optional] If no types are given, all allowed types will be returned.
	 * @return array Returns all changed PublicationID values since date given.
	 */
	public function getAll($sinceDate = null, $types = null)
	{
		// init parameters
		$parameters = array();

		// define parameters
		if($sinceDate !== null) $parameters['LastModified'] = $sinceDate;
		if($types !== null) $parameters['RequestedPropertyTypes'] = (array) $types;

		// define results
		$results = $this->doCall('GetPublicationSummaries', $parameters);

		// return items
		if(isset($results['PublicationSummaries']['PublicationSummary']))
		{
			// define return value
			$return = $results['PublicationSummaries']['PublicationSummary'];

			// we should return array of items, not one item array
			if(isset($return['ID'])) $return = array($return);

			// return
			return $return;
		}
		else return array();
	}

	/**
	 * Get all projects with optional filtering on date or type.
	 *
	 * @param string $sinceDate [optional] If no date given, all publications will be returned.
	 * @param array $types [optional] If no types are given, all allowed types will be returned.
	 * @return array Returns all changed PublicationID values since date given.
	 */
	public function getAllProjects($sinceDate = null, $types = null)
	{
		// init parameters
		$parameters = array();

		// define parameters
		if(!$sinceDate) $parameters['LastModified'] = $sinceDate;
		if(!$types) $parameters['RequestedPropertyTypes'] = $types;

		// define results
		$results = $this->doCall('GetProjectSummaries', $parameters);

		// return items
		if(isset($results['ProjectPublicationSummaries']['ProjectPublicationSummary']))
		{
			// define return value
			$return = $results['ProjectPublicationSummaries']['ProjectPublicationSummary'];

			// we should return array of items, not one item array
			if(isset($return['ID'])) $return = array($return);

			// return
			return $return;
		}
		else return array();
	}

	/**
	 * Get contact info
	 *
	 * @return array(Id, Username, Company, Reference, Contact => array(
	 *		Email, Website, Phone, Fax, Street, HouseNumber, HouseNumberExtension, ZipCode, City, Country)
	 *	)
	 */
	public function getContactInfo()
	{
		// define results
		$results = $this->doCall('GetContactInfo');

		// return item
		return (isset($results['UserSummaries']['UserSummary'])) ? $results['UserSummaries']['UserSummary'] : array();
	}

	/**
	 * Get the timeout that will be used
	 *
	 * @return	int
	 */
	public function getTimeOut()
	{
		return (int) $this->timeOut;
	}

	/**
	 * Get the useragent that will be used. Our version will be prepended to yours.
	 * It will look like: "PHP Skarabee Member/<version> <your-user-agent>"
	 *
	 * @return	string
	 */
	public function getUserAgent()
	{
		return (string) 'PHP Skarabee Member/' . self::VERSION . ' ' . $this->userAgent;
	}

	/**
	 * Set the timeout
	 * After this time the request will stop. You should handle any errors triggered by this.
	 *
	 * @return	void
	 * @param	int $seconds	The timeout in seconds
	 */
	public function setTimeOut($seconds)
	{
		$this->timeOut = (int) $seconds;
	}
	
	/**
	 * Set the user-agent for you application
	 * It will be appended to ours, the result will look like: "PHP Skarabee Member/<version> <your-user-agent>"
	 *
	 * @return	void
	 * @param	string $userAgent	Your user-agent, it should look like <app-name>/<app-version>
	 */
	public function setUserAgent($userAgent)
	{
		$this->userAgent = (string) $userAgent;
	}

	/**
	 * Ping to Skarabee.
	 *
	 * @todo Verify if it works.
	 * @param int $id The PublicationID you have received from Skarabee for a property.
	 * @param int $status The status of the property.
	 * @param int $statusDescription The description of the property.
	 * @param int $internalId The ID for this property in our website.
	 * @param int $internalURL The URL for this property in our website.
	 */
	public function pingBack($id, $status, $statusDescription = 'description', $internalId, $internalURL)
	{
		// redefine status
		$status = (string) $status;

		// available statusses
		$values = array('AVAILABLE', 'DELETED', 'AGENT_NOT_ACTIVE', 'ERROR');

		// error checking
		if(!in_array($status, $values))
		{
			throw new SkarabeeException('The given \'status\' : ' . $status . ' is not one of the following values: ' . implode(', ', $values) . '.');
		}

		// init feedback
		$feedback = array();

		// define parameters
		$feedback['PublicationID'] = (int) $id;
		$feedback['Status'] = $status;
		$feedback['StatusDescription'] = (string) $statusDescription;
		$feedback['ExternalID'] = (int) $internalId;
		$feedback['URL'] = (string) $internalURL;

		// init parameters
		$parameters['FeedbackList']['FeedbackList']['Feedback'] = $feedback;

		// call feedback
		return $this->doCall('Feedback', $parameters);
	}
}


/**
 * Skarabee Exception class
 *
 * @author Jeroen Desloovere <jeroen@siesqo.be>
 */
class SkarabeeException extends Exception {}
