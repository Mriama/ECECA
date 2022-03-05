<?php

namespace App\Utils;


class getEtablissementsGest {
	public $httpHeaders; // headersWrapper
	public $typeExtraction; // gestExtractEnum
	public $nomGroupe; // string
}
class headersWrapper {
	public $header; // headerWrapper
}
class headerWrapper {
	public $key; // string
	public $value; // string
}
class getEtablissementsGestResponse {
	public $etablissements; // etablissementsWrapper
}
class etablissementsWrapper {
	public $etablissement; // etablissement
}
class etablissement {
	public $activite; // string
	public $appliDelegation; // string
	public $codeRne; // string
	public $codeRneRattachement; // string
	public $codeTNA; // string
	public $codeTTY; // string
	public $fonctionExercee; // string
	public $secteur; // string
	public $typeEtablissement; // string
	public $uaaUaj; // string
}
class ComposantSecuriteException {
	public $message; // string
}
class getEtablissementsDeleg {
	public $httpHeaders; // headersWrapper
	public $typeExtraction; // delegExtractEnum
	public $nomApp; // string
	public $resource; // string
	public $modeFim; // boolean
}
class getEtablissementsDelegResponse {
	public $etablissements; // etablissementsWrapper
}
class getEtablissementsResp {
	public $httpHeaders; // headersWrapper
	public $typeExtraction; // respExtractEnum
}
class getEtablissementsRespResponse {
	public $etablissements; // etablissementsWrapper
}
class getProxy {
	public $httpHeaders; // headersWrapper
}
class getProxyResponse {
	public $proxy; // string
}
class getVersion {
}
class getVersionResponse {
	public $version; // string
}
class getUtilisateur {
	public $httpHeaders; // headersWrapper
}
class getUtilisateurResponse {
	public $utilisateur; // utilisateurWrapper
}
class utilisateurWrapper {
	public $academie; // string
	public $codeCommune; // string
	public $dateNaissance; // string
	public $etabAffectation; // string
	public $etabFrEduRne; // etablissementsWrapper
	public $etabFrEduRneResp; // etablissementsWrapper
	public $fonction; // string
	public $fonctionAdm; // string
	public $groupes; // groupesWrapper
	public $mail; // string
	public $nom; // string
	public $nomFamille; // string
	public $numen; // string
	public $prenom; // string
	public $typeEnsi; // string
	public $uid; // string
}
class groupesWrapper {
	public $groupe; // string
}
class gestExtractEnum {
}
class delegExtractEnum {
}
class respExtractEnum {
}

class ClearTrustSoapClient 
{

	private $wsdl_uri;

	
	public function getWsdlUri() {
		return $this->wsdl_uri;
	}
	
	public function setWsdlUri($wsdl_uri) {
		$this->wsdl_uri = $wsdl_uri;
		return $this;
	}
	
	private static $classmap = array (
			'getEtablissementsGest' => 'getEtablissementsGest',
			'headersWrapper' => 'headersWrapper',
			'headerWrapper' => 'headerWrapper',
			'getEtablissementsGestResponse' => 'getEtablissementsGestResponse',
			'etablissementsWrapper' => 'etablissementsWrapper',
			'etablissement' => 'etablissement',
			'ComposantSecuriteException' => 'ComposantSecuriteException',
			'getEtablissementsDeleg' => 'getEtablissementsDeleg',
			'getEtablissementsDelegResponse' => 'getEtablissementsDelegResponse',
			'getEtablissementsResp' => 'getEtablissementsResp',
			'getEtablissementsRespResponse' => 'getEtablissementsRespResponse',
			'getProxy' => 'getProxy',
			'getProxyResponse' => 'getProxyResponse',
			'getVersion' => 'getVersion',
			'getVersionResponse' => 'getVersionResponse',
			'getUtilisateur' => 'getUtilisateur',
			'getUtilisateurResponse' => 'getUtilisateurResponse',
			'utilisateurWrapper' => 'utilisateurWrapper',
			'groupesWrapper' => 'groupesWrapper',
			'gestExtractEnum' => 'gestExtractEnum',
			'delegExtractEnum' => 'delegExtractEnum',
			'respExtractEnum' => 'respExtractEnum'
	);

	public function ClearTrustSoapClient($wsdl_location = null, $options = array()) {
		foreach ( self::$classmap as $key => $value ) {
			if (! isset ( $options ['classmap'] [$key] )) {
				$options ['classmap'] [$key] = $value;
			}
		}
		//parent::__construct ( $wsdl_location, $options );
	}
	
	/**
	 *
	 * @param getVersion $parameters
	 * @return getVersionResponse
	 */
	public function getVersion(getVersion $parameters) {
		$client = new \SoapClient($this->wsdl_uri);
		return $client->__soapCall ( 'getVersion', array (
				$parameters
		), array (
				'uri' => $this->wsdl_uri,
				'soapaction' => ''
		) );
	}
	
	/**
	 *
	 * @param getEtablissementsDeleg $parameters
	 * @return getEtablissementsDelegResponse
	 */
	public function getEtablissementsDeleg(getEtablissementsDeleg $parameters) {
		$client = new \SoapClient($this->wsdl_uri);
		return $client->__soapCall ( 'getEtablissementsDeleg', array (
				$parameters
		), array (
				'uri' => $this->wsdl_uri,
				'soapaction' => ''
		) );
	}
	
	/**
	 *
	 * @param getEtablissementsResp $parameters
	 * @return getEtablissementsRespResponse
	 */
	public function getEtablissementsResp(getEtablissementsResp $parameters) {
		$client = new \SoapClient($this->wsdl_uri);
		return $client->__soapCall ( 'getEtablissementsResp', array (
				$parameters
		), array (
				'uri' => $this->wsdl_uri,
				'soapaction' => ''
		) );
	}
	
	/**
	 *
	 * @param getEtablissementsGest $parameters
	 * @return getEtablissementsGestResponse
	 */
	public function getEtablissementsGest(getEtablissementsGest $parameters) {
		$client = new \SoapClient($this->wsdl_uri);
		return $client->__soapCall ( 'getEtablissementsGest', array (
				$parameters
		), array (
				'uri' => $this->wsdl_uri,
				'soapaction' => ''
		) );
	}
	
	/**
	 *
	 * @param getUtilisateur $parameters
	 * @return getUtilisateurResponse
	 */
	public function getUtilisateur(getUtilisateur $parameters) {
		$client = new \SoapClient($this->wsdl_uri, ["trace" => 1]);
		dd($client);
		return $client->__soapCall ( 'getUtilisateur', array (
				$parameters
		), array (
				'uri' => $this->wsdl_uri,
				'soapaction' => ''
		) );
	}
	
	/**
	 *
	 * @param getProxy $parameters
	 * @return getProxyResponse
	 */
	public function getProxy(getProxy $parameters) {
		$client = new \SoapClient($this->wsdl_uri);
		return $client->__soapCall ( 'getProxy', array (
				$parameters
		), array (
				'uri' => $this->wsdl_uri,
				'soapaction' => ''
		) );
	}
}