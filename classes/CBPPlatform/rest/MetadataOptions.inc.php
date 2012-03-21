<?php

$fileFormat = explode("/", $this->obj->hydra['mimetype']);

$rightsOptions = array(
		array('tag' => 'human', 'parent' => 'copyright', 'value' => 'Creative Commons Licence: Attribution-Noncommercial-Share Alike 2.0 UK: England and Wales. See: http://creativecommons.org/licenses/by-nc-sa/2.0/uk/'),
		array('tag' => 'human', 'parent' => 'access', 'parentAttributes' => 'type=discover;', 'value' => 'contentAccessTeam'),
		array('tag' => 'group', 'parent' => 'access', 'parentAttributes' => 'type=discover;', 'value' => 'contentAccessTeam'),
		array('tag' => 'human', 'parent' => 'access', 'parentAttributes' => 'type=read;', 'value' => 'contentAccessTeam'),
		array('tag' => 'group', 'parent' => 'access', 'parentAttributes' => 'type=read;', 'value' => 'contentAccessTeam')
);

$dcOptions = array(
	array(
		'tag' => 'dc:title', 
			'parent' => 'oai_dc:dc', 
			'value' => $title
	),
	array(
		'tag' => 'dc:identifier', 
			'parent' => 'oai_dc:dc', 
			'value' => $this->obj->ns . ":" . $this->obj->PID
	),
	array(
		'tag' => 'dc:type', 
			'parent' => 'oai_dc:dc', 
			'value' => 'book'
	),
	array(
		'tag' => 'dc:date', 
			'parent' => 'oai_dc:dc', 
			'value' => date("Y-m-d")
	),
);

$descOptionsApply = array(
	array(
		'tag' => 'title', 
			'parent' => 'titleInfo', 
			'value' => $title
		),
	array(
		'tag' => 'namePart', 
			'parent' => 'name', 
			'value' =>  $author
		),
	array(
		'tag' => 'extent', 
			'parent' => 'physicalDescription', 
			'value' => 'Filesize: ' . number_format(round($this->obj->hydra['filesize'] / 1024), 0, '.', ',') . 'KB'
		),
	array(
		'tag' => 'internetMediaType', 
			'parent' => 'physicalDescription', 
			'value' => $this->obj->hydra['mimetype']
		),
	array(
		'tag' => 'topic',
			'parent' => 'subject',
			'value' => 'Creative writing'
	),
	array(
		'tag' => 'identifier',
			'parent' => 'mods',
			'value' => $this->obj->ns . ":" . $this->obj->PID
	)
);
foreach ($relatedItems as $relatedItem) {
	$app = array(
		array(
	            'tag' => 'relatedItem',
	            'type' => 'open',
	            'level' => '2',
	            'attributes' => array(
	                    'ID' => 'CBPPlatformObject'
	                ),
	            'value' => ''
		),
	    array(
	            'tag' => 'identifier',
	            'type' => 'complete',
	            'level' => '3',
	            'attributes' => array(
	                    'type' => 'fedora',
	                ),
	            'value' => 'hull-private:2844'
	        ),
	    array(
	            'tag' => 'relatedItem',
	            'value' => '',
	            'type' => 'cdata',
	            'level' => '2'
	    ),
	    array(
	            'tag' => 'relatedItem',
	            'type' => 'close',
	            'level' => '2'
	    )
	);
	
	foreach($app as &$value) {
		if ($value['tag'] == 'identifier') $value['value'] = $relatedItem;
		$descOptions[] = $value;
	}
}

$descOptionsAppend = array(
	array(
            'tag' => 'mods',
            'value' => '',
            'type' => 'cdata',
            'level' => '1',
			),
    array(
            'tag' => 'location',
            'type' => 'open',
            'level' => '2',
            'value' => '',
        ),
    array(
            'tag' => 'url',
            'type' => 'complete',
            'level' => '3',
            'attributes' => array(
                    'usage' => 'primary display',
                    'access' => 'object in context'
                ),
            'value' => "http://" . $this->hydraServer . "/resources/" . $this->obj->ns . ":" . $this->obj->PID
        ),
    array(
            'tag' => 'location',
            'value' => '',
            'type' => 'cdata',
            'level' => '2'
        ),
    array(
            'tag' => 'url',
            'type' => 'complete',
            'level' => '3',
            'attributes' => array(
                    'access' => 'raw object'
                ),
            'value' => "http://" . $this->hydraServer . "/assets/" . $this->obj->ns . ":" . $this->obj->PID . "/" . $this->obj->hydra['dsId']
        ),
    array(
            'tag' => 'location',
            'value' => '',
            'type' => 'cdata',
            'level' => '2'
        ),
    array(
            'tag' => 'location',
            'type' => 'close',
            'level' => '2'
        ),
    array(
            'tag' => 'mods',
            'value' => '',
            'type' => 'cdata',
            'level' => '1'
        ),
    array(
            'tag' => 'accessCondition',
            'type' => 'complete',
            'level' => '2',
            'attributes' => array(
                    'type' => 'useAndReproduction'
                ),
            'value' => 'Creative Commons Licence: Attribution-Noncommercial-Share Alike 2.0 UK: England and Wales. See: http://creativecommons.org/licenses/by-nc-sa/2.0/uk/'
        ),
    array(
            'tag' => 'mods',
            'value' => '',
            'type' => 'cdata',
            'level' => '1'
        ),
    array(
            'tag' => 'recordInfo',
            'type' => 'open',
            'level' => '2',
            'value' => ''
        ),
    array(
            'tag' => 'recordContentSource',
            'type' => 'complete',
            'level' => '3',
            'value' => 'Campus-based Publishing Platform, University of Hull'
        ),
    array(
            'tag' => 'recordInfo',
            'value' => '',
            'type' => 'cdata',
            'level' => '2'
        ),
    array(
            'tag' => 'recordCreationDate',
            'type' => 'complete',
            'level' => '3',
            'attributes' => array(
                    'encoding' => 'w3cdtf'
                ),
            'value' => date("Y-m-d")
        ),
    array(
            'tag' => 'recordInfo',
            'value' => '',
            'type' => 'cdata',
            'level' => '2'
        ),
    array(
     		'tag' => 'recordChangeDate',
            'type' => 'complete',
            'level' => '3',
            'attributes' => array(
                    'encoding' => 'w3cdtf'
                )
        ),
    array(
            'tag' => 'recordInfo',
            'value' => '',
            'type' => 'cdata',
            'level' => '2'
        ),
    array(
            'tag' => 'languageOfCataloging',
            'type' => 'open',
            'level' => '3',
            'value' => ''

        ),
    array(
            'tag' => 'languageTerm',
            'type' => 'complete',
            'level' => '4',
            'attributes' => array(
                    'authority' => 'iso639-2b'
                ),
            'value' => 'eng'
        ),
    array(
            'tag' => 'languageOfCataloging',
            'value' => '',
            'type' => 'cdata',
            'level' => '3'
        ),
    array(
            'tag' => 'languageOfCataloging',
            'type' => 'close',
            'level' => '3'
        ),
    array(
            'tag' => 'recordInfo',
            'value' => '',
            'type' => 'cdata',
            'level' => '2'
        ),
    array(
            'tag' => 'recordInfo',
            'type' => 'close',
            'level' => '2'
        ),
    array(
            'tag' => 'mods',
            'value' => '',
            'type' => 'cdata',
            'level' => '1'
        ),
    array(
            'tag' => 'mods',
            'type' => 'close',
            'level' => '1'
        )
);

foreach ($descOptionsAppend as $value) {
	$descOptions[] = $value;
}

$contentOptions = array(
	array(
		'tag' => 'resource', 
			'parent' => 'contentMetadata', 
			'attribute' => 'objectID',
			'value' => $this->obj->ns . ":" . $this->obj->PID
	),
	array(
		'tag' => 'resource', 
			'parent' => 'contentMetadata', 
			'attribute' => 'dsID',
			'value' => $this->obj->hydra['dsId']
	),
	array(
		'tag' => 'location', 
			'parent' => 'file', 
			'value' => "http://" . $this->hydraServer . "/" . $this->obj->ns . ":" . $this->obj->PID . "/" . $this->obj->hydra['dsId']
	),
	array(
		'tag' => 'file',
			'parent' => 'resource',
			'attribute' => 'format',
			'value' => $fileFormat[1]
	),
	array(
		'tag' => 'file',
			'parent' => 'resource',
			'attribute' => 'mimeType',
			'value' => $this->obj->hydra['mimetype']
	),
	array(
		'tag' => 'file',
			'parent' => 'resource',
			'attribute' => 'size',
			'value' => $this->obj->hydra['filesize']
	),
);


//
$relsExtOptions = array(
	array(
		'tag' => 'rdf:Description', 
			'parent' => 'rdf:RDF', 
			'attribute' => 'rdf:about',
			'value' => 'info:fedora/' . $this->obj->ns . ":" . $this->obj->PID
	),
	array(
		'tag' => 'rdf:Description', 
			'parent' => 'rdf:RDF', 
			'attribute' => 'rdf:about',
			'value' => 'info:fedora/' . $this->obj->ns . ":" . $this->obj->PID
	)
);

$contentMetadata = array(
	array(
		'tag' => 'contentMetadata',
		'type' => 'open',
		'level' => '1',
		'attributes' => array('type' => 'book', 'xmlns' => 'http://hydra-collab.hull.ac.uk/schemas/contentMetadata/v1'),
		'value' => ''
	)
);

$i = 1;
foreach ($this->obj->hydra['obj'] as $hydraObj) {
	
	$contentMetadataVar = array(
		array(
			'tag' => 'resource',
            'type' => 'open',
            'level' => '2',
            'attributes' => array(
                    'sequence' => '1',
                    'id' => 'book',
                    'displayLabel' => 'Book',
                    'serviceDef' => 'hull-sDef:book',
                    'serviceMethod' => 'getContent',
                    'objectID' => 'hull:4239',
                    'dsID' => 'content',
                    'contains' => 'content'
                ),
            'value' => ''
		),
		array(
            'tag' => 'file',
            'type' => 'open',
            'level' => '3',
            'attributes' => array(
                    'id' => 'content',
                    'format' => 'pdf',
                    'mimeType' => 'application/pdf',
                    'size' => '2968255'
             ),
            'value' => ''
	    ),
	    array(
	        'tag' => 'location',
            'type' => 'complete',
            'level' => '4',
            'attributes' => array(
                    'type' => 'url'
             ),
            'value' => 'http://hydra.hull.ac.uk/assets/hull:4239/content'
	    ),
		array(
            'tag' => 'file',
            'value' => '',
            'type' => 'cdata',
            'level' => '3'
	    ),
	    array(
            'tag' => 'file',
            'type' => 'close',
            'level' => '3'
	    ),
		array(
            'tag' => 'resource',
            'value' => '',
            'type' => 'cdata',
            'level' => '2'
	    ),
	    array(
            'tag' => 'resource',
            'type' => 'close',
            'level' => '2'
	    )
	);

	foreach($contentMetadataVar as &$value) {
		if (isset($value['attributes']['sequence'])) $value['attributes']['sequence'] = $i;
		if ($i > 1 && isset($value['attributes']['serviceDef'])) { $value['attributes']['serviceDef'] = "hull-sDef:compoundContent"; }
		if (isset($value['attributes']['objectID'])) $value['attributes']['objectID'] = $this->obj->ns . ":" . $this->obj->PID;
		if (isset($value['attributes']['dsID'])) $value['attributes']['dsID'] = $hydraObj['dsId'];
		if (isset($value['attributes']['serviceMethod']) && $i == 1) $value['attributes']['serviceMethod'] = "get" . ucfirst($hydraObj['dsId']);
		if (isset($value['attributes']['serviceMethod']) && $i > 1) $value['attributes']['serviceMethod'] = "getContent?dsID=" . $hydraObj['dsId'];
		
		//$fileFormat = explode("/", $hydraObj['mimetype']);
		if (isset($value['attributes']['id'])) $value['attributes']['id'] = $hydraObj['dsId'];
		if (isset($value['attributes']['format'])) $value['attributes']['format'] = $hydraObj['filetype'];
		if (isset($value['attributes']['mimeType'])) $value['attributes']['mimeType'] = $hydraObj['mimetype'];
		if (isset($value['attributes']['size'])) $value['attributes']['size'] = $hydraObj['size'];
		
		if(isset($value['value']) && $value['tag'] == "location") $value['value'] =  "http://" . $this->hydraServer . "/" . $this->obj->ns . ":" . $this->obj->PID . "/" . $hydraObj['dsId'];
		
		$contentMetadata[] = $value;
	}
	$i++;
}

$contentMetadataAppend = array(
    array(
        'tag' => 'contentMetadata',
        'value' => '',
        'type' => 'cdata',
        'level' => '1'
    ),
    array(
        'tag' => 'contentMetadata',
        'type' => 'close',
        'level' => '1'
    )
);

foreach ($contentMetadataAppend as $value) {
	$contentMetadata[] = $value;
}