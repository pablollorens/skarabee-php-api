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
	// The URL where you find the .wsdl file
	const API_URL = 'http://weblink.skarabee.com/weblink.asmx?wsdl';

	/**
	 * Password
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * Username
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * The instance of the SOAP client
	 *
	 * @var SoapClient
	 */
	protected $instance;

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
	 * Add contact message
	 *
	 * @todo Verify if it works.
	 * @param array $item
	 */
	public function addContactMessage($item)
	{
		// init parameters
		$parameters = array();

		// build parameters
		$parameters['PublicationID'] = $item['publication_id'];
		$parameters['ExternalReference'] = $item['external_reference'];
		if(isset($item['first_name'])) $parameters['FirstName'] = $item['first_name'];
		if(isset($item['last_name'])) $parameters['LastName'] = $item['last_name'];
		if(isset($item['cell_phone'])) $parameters['CellPhone'] = $item['cell_phone'];
		if(isset($item['phone'])) $parameters['Phone'] = $item['phone'];
		if(isset($item['email'])) $parameters['Email'] = $item['email'];
		if(isset($item['comments'])) $parameters['Comments'] = $item['comments'];
		if(isset($item['street'])) $parameters['Street'] = $item['street'];
		if(isset($item['house_number'])) $parameters['HouseNumber'] = $item['house_number'];
		if(isset($item['house_number_extension'])) $parameters['HouseNumberExtension'] = $item['house_number_extension'];
		if(isset($item['zip'])) $parameters['ZipCode'] = $item['zip'];
		if(isset($item['city'])) $parameters['City'] = $item['city'];

		// return call
		return $this->doCall('InsertContactMes', $parameters);
	}

	/**
	 * Call a certain method with its parameters
	 *
	 * @param string $method
	 * @param string $parameters
	 * @return SoapClient
	 */
	private function doCall($method, $parameters = array())
	{
		// first time we call SoapClient
		if(!$this->instance)
		{
			try
			{
				// define options
				$options = array(
					'login' => $this->username,
					'password' => $this->password
				);

				// define SOAP client
				$this->instance = new SoapClient(self::API_URL, $options);
			}
			catch(Exception $e)
			{
				// throw error
				throw new SkarabeeException($e);
			}
		}

		// define result method which contains the results
		$resultMethod = $method . 'Result';

		// return results from called method
		return json_decode(json_encode($this->instance->$method($parameters)->$resultMethod), true);
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
		return (isset($results['PublicationSummaries']['PublicationSummary'])) ? $results['PublicationSummaries']['PublicationSummary'] : array();
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
		return (isset($results['ProjectPublicationSummaries']['ProjectPublicationSummary'])) ? $results['ProjectPublicationSummaries']['ProjectPublicationSummary'] : array();
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

		// init parameters
		$parameters = array();

		// define parameters
		$parameters['PublicationID'] = (int) $id;
		$parameters['Status'] = $status;
		$parameters['StatusDescription'] = (string) $statusDescription;
		$parameters['ExternalID'] = (int) $internalId;
		$parameters['URL'] = (string) $internalURL;

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
