<?php

/**
 * @defgroup CBPPlatform
 */

/**
 * @file classes/CBPPlatform/rest/DigitalObject.inc.php
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CBPPlatformDigitalObject
 * @ingroup CBPPlatform
 *
 * @brief Operations for CBP Platform Fedora digital object and REST (Fedora API-M) functionality.
 */

	import('classes.CBPPlatform.rest.Pest');

	class CBPPlatformDigitalObject {
		
		/**
		 * Constructor.
		 * Set properties
		 */
		function __construct($resultFormat="xml"){
			$this->username = Config::getVar('general', 'repository_username');
			$this->password = Config::getVar('general', 'repository_password');
			$this->server = Config::getVar('general', 'repository_short_url');
			$this->hydraServer = Config::getVar('general', 'hydra_short_url');
			
			$this->rest = new Pest("http://$this->username:$this->password@$this->server");
			$this->resultFormat = $resultFormat;
			$this->primaryFormat = "application/pdf";
		}
		
		/**
		 * Set digital object namespace
		 * @param $ns str namespace
		 */
		function setNamespace($ns=null){
			if ($ns == null) $ns = Config::getVar('general', 'compiled_namespace');
			$this->obj->ns = $ns;
		}
		
		/**
		 * Set digital object pid
		 * @param $pid str pid
		 */
		function setPID($pid=""){
			$this->obj->PID = $pid;	
		}
		
		/**
		 * Set digital object label
		 * @param $label str label
		 */
		function setLabel($label=""){
			$this->obj->label = $label;	
		}
		
		/**
		 * Set digital object datastream
		 * @param $ds array Datastream to set
		 */
		function setDatastream(Array $ds){
			/**
			 * Uses 2D Array
			 *
			 * dsID
			 *   -Multipart File
			 *   -Label
			 * dsID2
			 *   - Multipart File 2
			 *   - Label 2
			 */
			$this->obj->ds[] = $ds;
		}
		
		/**
		 * Set primary format
		 * @param $format str primary format to set
		 */
		function setPrimaryFormat($format) {
			$this->primaryFormat = $format;
		}
		
		/**
		 * A dump of the object's properties
		 */
		function __toString(){
			var_dump($this);
		}
	
		/**
		 * Create Object
		 * @return string
		 */
		function createObject(){
			if (isset($this->obj->PID)) {	//if a PID is specified, use it
				$params = array(
					"label" => $this->obj->label
				);
				$this->rest->post("/" . $this->obj->ns . ":" . $this->obj->PID . "?" . http_build_query($params));
			} else { //otherwise, autogenerate a PID within the namespace
				$params = array(
					"namespace" => $this->obj->ns,
					"label" => $this->obj->label
				);
				$this->obj->PID = $response = $this->rest->post("/new?" . http_build_query($params));
			}
			return $this->obj->PID;
		}
		
		/**
		 * Create Object Datastreams
		 * @return bool
		 */
		function createObjectDatastreams($controlGroup="M", $dsState="A"){
			//expects first content-bearing DS to be PDF content...
			foreach($this->obj->ds as $ds) {
				$id = key($ds);
				if (isset($ds[$id]['id'])) {
					$dsId = $ds[$id]['id'];
				} else {
					$dsId = "content";
					if (isset($this->obj->pointer)) {
						$this->obj->pointer++;
						$dsId .= sprintf("%02d", $this->obj->pointer);
					}
					if (!isset($this->obj->pointer)) {
						$this->obj->pointer = 1;
					}
				}
				isset($ds[$id]['mimetype']) ? $mimetype = $ds[$id]['mimetype'] : $mimetype = mime_content_type($ds[$id]['file']);
				if ($mimetype == $this->primaryFormat) {
					$this->obj->hydra['filesize'] = filesize($ds[$id]['file']);
					$this->obj->hydra['mimetype'] = $mimetype;
					$this->obj->hydra['dsId'] = $dsId;
				}
				$properties = array(
					'size' => filesize($ds[$id]['file']),
					'mimetype' => $mimetype,
					'dsId' => $dsId
				);
				if (isset($ds[$id]['filetype'])) $properties['filetype'] = $ds[$id]['filetype'];
				$this->obj->hydra['obj'][] = $properties;
				$file = array(
						"file" => "@" . $ds[$id]['file'] . ";type=" . $mimetype,
					);
				$params = array(
						"dsLabel" => $ds[$id]['label'],
						"controlGroup" => $controlGroup,
						"dsState" => $dsState,
					);
					//'checksumType' => 'MD5'
				$response = $this->rest->post("/" . $this->obj->ns . ":" . $this->obj->PID . "/datastreams/$dsId?" . http_build_query($params), $file);
			}
			return $response;
		}
		
		/**
		 * Read Object Properties
		 * @return string
		 */
		function readObjectProperties(){
			return $response = $this->rest->get("?terms=" . $this->obj->ns . ":" . $this->obj->PID . "&resultFormat=" . $this->resultFormat . "&pid=true&subject=true&label=true");
		}
		
		/**
		 * Read Object Datastreams
		 * @return string
		 */
		function readObjectDatastreams(){
			return $response = $this->rest->get("/$this->obj->ns:$this->obj->PID/datastreams?format=$this->resultFormat");
		}
		
		/**
		 * Update Object Properties
		 * @return string
		 */
		function updateObjectProperties(){
			$params = array(
						"label" => $this->obj->label
					);
			return $response = $this->rest->put("/$this->obj->ns:$this->obj->PID?" . http_build_query($params));
		}
		
		/**
		 * Update Object Datastreams
		 * @return array
		 */
		 function updateObjectDatastreams($controlGroup="M", $dsState="A"){
		 	foreach($this->obj->ds as $ds) {
				$dsId = key($ds);				
				$file = array(
							"file" => "@" . $filename = $ds[$dsId]['file'] . ";type=" . mime_content_type($filename),
						);
				$params = array(
						"dsLabel" => $label = $ds[$dsId]['label'],
						"controlGroup" => $controlGroup,
						"dsState" => $dsState
					);
				$response = $this->rest->put("/$this->obj->ns:$this->obj->PID/datastreams/$dsId?" . http_build_query($params), $file);
			}
			return $response;
		 }
		
		/**
		 * Delete Object
		 * @return string
		 */
		function deleteObject(){
			return $response = $this->rest->delete("/$this->obj->ns:$this->obj->PID");
		}
		
		/**
		 * Delete Datastream
		 * @return array
		 */
		 function deleteDatastreams(){
		 	foreach($this->obj->ds as $ds) {
		 		$dsId = key($ds);
		 		$response[] = $this->rest->delete("/" . $this->obj->ns . ":" . $this->obj->PID . "/datastreams/$dsId");
		 	}
		 	return $response;
		 }
		
		 /**
		  * Makes the digital object Hydra compliant by setting /generating appropriate metadata and appending to object held in repository
		  * @param $type str default 'book'
		  * @param $title str title
		  * @param $author str author
		  * @param $relatedItems array items that are related to the object
		  * @return str response from repository server
		  */
		function makeHydraCompliant($type = "book", $title ="", $author = "", $relatedItems = array()) {
			//see the below file for a list of options that are modified/set
			include('MetadataOptions.inc.php');
			$this->setMetadata("dc", $dcOptions);
			$this->setMetadata("desc", array($descOptionsApply, $descOptions), "MODS", "constructAddDesc");
			$this->setMetadata("relsExt", $relsExtOptions);
			$this->setMetadata("rights", $rightsOptions);
			$this->setMetadata("content", $contentMetadata, null, "add");

			foreach($this->obj->hydraMeta as $dsId => $ds) {
				$tempFileName = tempnam(sys_get_temp_dir(), ".xml");
				$tempFile = fopen($tempFileName, 'w');
				fwrite($tempFile, $ds['content']);
				fseek($tempFile, 0); 
				$params = array(
						"dsLabel" => $ds['label'],
						"controlGroup" => 'M',
						"dsState" => 'A'
					);
				if ($dsId == 'RELS-EXT' || $dsId == 'rightsMetadata') $params['controlGroup'] = 'X';
				($dsId == 'RELS-EXT') ? $mimetype = "application/rdf+xml" : $mimetype = "text/xml";
				$file = array(
							"file" => "@" . $tempFileName . ";type=" . $mimetype,
						);
				$response = $this->rest->post("/" . $this->obj->ns . ":" . $this->obj->PID . "/datastreams/$dsId?" . http_build_query($params), $file);
				fclose($tempFile);
				unlink($tempFileName);
			}
			return $response;
		}
		
		/**
		 * Set selected XML metadata to create Hydra-compliant object
		 * @param $type str type of metadata to set
		 * @param $options array options to set
		 * @param $label label for ultimate XML file produced
		 * @param $func str function to perform
		 * @return xml resultant XML
		 */
		function setMetadata($type, $options = array(), $label = "", $func = null) {
			$xml = file_get_contents(dirname(realpath(__FILE__)) . "/hydra/" . $type . "Metadata.xml");
			if ($func == "constructAddDesc") {
				$metadata = $this->xml2array($xml);
				$metadata = $this->applyHydraOptions($options[0], $metadata);
				foreach ($metadata as $key => $value) {
					if ($key <= 47) {
						$data[] = $value;
					}
				}
				$xmlArray = array_merge($data, $options[1]);
			} elseif ($func == "add") {
				$xmlArray = $options;
			} else {
				$metadata = $this->xml2array($xml);
				$xmlArray = $this->applyHydraOptions($options, $metadata);
			}
			$xml = $this->array2xml($xmlArray);
			if ($type == "relsExt") {
				$this->obj->hydraMeta['RELS-EXT']['label'] = 'Fedora Object-to-Object Relationship Metadata';
				$this->obj->hydraMeta['RELS-EXT']['content'] = $xml;
			} else if ($type == "dc") {
				$this->obj->hydraMeta['DC']['content'] = $xml;
				$this->obj->hydraMeta['DC']['label'] = "Dublin Core Record for this object";
			} else {
				 $this->obj->hydraMeta[$type . "Metadata"]['content'] = $xml;
				if ($label != "") {
				 	$this->obj->hydraMeta[$type . "Metadata"]['label'] = $label . " metadata";
				} else {
					$this->obj->hydraMeta[$type . "Metadata"]['label'] = ucfirst($type) . " metadata";
				}
			}
			return $xml;
 		}

 		/**
 		 * Convert given XML to an array
 		 * @param $xml str XML file to convert to array
 		 * @return $aryXML array XML file as an array
 		 */
		function xml2array($xml) {
			$xml_parser = xml_parser_create( 'UTF-8' );
			xml_parser_set_option( $xml_parser, XML_OPTION_CASE_FOLDING, 0); 
			xml_parser_set_option( $xml_parser, XML_OPTION_SKIP_WHITE, 0); 
			xml_parse_into_struct( $xml_parser, $xml, $aryXML); 
			xml_parser_free($xml_parser);
			return $aryXML;
		}

		/**
		 * Apply Hydra options to an array
		 * @param $options array options to apply
		 * @param $xmlArray array array to apply options to
		 * @return array array with options applied
		 */
		function applyHydraOptions($options, $xmlArray) {
			foreach ($options as $option) {
				foreach ($xmlArray as &$value) {
					if ($value['type'] == "open") {
						if (isset($value['attributes'])) {
								foreach($value['attributes'] as $akey => $avalue) {
									$opentags[$value['tag']] .= "$akey=$avalue;";
								}
							} else {
								$opentags[$value['tag']] = "open";
							}
						}
						if ($value['type'] == "close" || $value['type'] == "complete") {
							unset($opentags[$value['tag']]);
						}
						if (array_key_exists($option['parent'], $opentags) && $option['tag'] == $value['tag']) {
							if (isset($option['parentAttributes']) || isset($option['hasAttribute'])) {
								if ($value['type'] != "cdata") {
									if (isset($value['attributes'])) {
										if (!in_array($option['parentAttributes'], $opentags) && array_key_exists($option['hasAttribute'], $value['attributes']) == false) {
											//do nowt!
										} else {
											if (isset($option['attribute'])) {
												if (isset($value['attributes'])) {
													if (array_key_exists($option['attribute'], $value['attributes'])) {
														$value['attributes'][$option['attribute']] = $option['value'];
													}
												}
											} else {
												$value['value'] = $option['value'];
											}
										}
									}
								}
							} else {
								if (isset($option['attribute'])) {
									if (isset($value['attributes'])) {
										if (array_key_exists($option['attribute'], $value['attributes'])) {
											$value['attributes'][$option['attribute']] = $option['value'];
										}
									}
								} else {
									$value['value'] = $option['value'];
								}
							}
						}
					}
				}
			return $xmlArray;
		}

		/**
		 * Convert array back to XML
		 * @param $array array array to convert to XML
		 * @return $o str XML
		 */
		function array2xml($array) {
			$o=''; 
			foreach($array as $tag ){ 
				if($tag['tag'] == 'textarea' && !isset($tag['value'])){ 
					$tag['value']=''; 
				}
				$t = ''; 
				for($i=1; $i < $tag['level'];$i++){ 
					$t.="\t"; 
				} 
				switch($tag['type']){ 
					case 'complete': 
					case 'open': 
						$o.=$t.'<'.$tag['tag']; 
						if(isset($tag['attributes'])){ 
							foreach($tag['attributes'] as $attr=>$aval){ 
								$o.=' '.$attr.'="'.$aval.'"'; 
							}
						}
						if($tag['type'] == 'complete'){ 
							if(!isset($tag['value'])){ 
								$o .= '></'.$tag['tag'].'>'."\n";
							} else { 
								$o .= '>'."\n".$t.$tag['value']."\n".$t.'</'.$tag['tag'].'>'."\n"; 
							} 
						}else{ 
							$o .= '>'."\n"; 
						} 
					break; 
					case 'close': 
						$o .= $t.'</'.$tag['tag'].'>'."\n"; 
					break; 
					case 'cdata': 
						$o .= $t.$tag['value']."\n"; 
					break; 
				}
			}
			return $o;
		}
	}