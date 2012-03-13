<?php

/**
 * @defgroup citation_parser_freecite
 */

/**
 * @file classes/citation/parser/freecite/FreeciteRawCitationNlmCitationSchemaFilter.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FreeciteRawCitationNlmCitationSchemaFilter
 * @ingroup citation_parser_freecite
 *
 * @brief Parsing filter implementation that uses the Freecite web service.
 *
 */

import('lib.pkp.classes.citation.NlmCitationSchemaFilter');

define('FREECITE_WEBSERVICE', 'http://freecite.library.brown.edu/citations/create');

class FreeciteRawCitationNlmCitationSchemaFilter extends NlmCitationSchemaFilter {
	/*
	 * Constructor
	 */
	function FreeciteRawCitationNlmCitationSchemaFilter() {
		$this->setDisplayName('FreeCite');

		parent::NlmCitationSchemaFilter(NLM_CITATION_FILTER_PARSE);
	}

	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.classes.citation.parser.freecite.FreeciteRawCitationNlmCitationSchemaFilter';
	}

	/**
	 * @see Filter::process()
	 * @param $citationString string
	 * @return MetadataDescription
	 */
	function &process($citationString) {
		$nullVar = null;

		// Freecite requires a post request
		$postData = array('citation' => $citationString);
		if (is_null($resultDOM = $this->callWebService(FREECITE_WEBSERVICE, $postData, XSL_TRANSFORMER_DOCTYPE_DOM, 'POST'))) return $nullVar;

		// Transform the result into an array of meta-data
		if (is_null($metadata =& $this->transformWebServiceResults($resultDOM, dirname(__FILE__).DIRECTORY_SEPARATOR.'freecite.xsl'))) return $nullVar;

		// Extract a publisher from the place string if possible
		$metadata =& $this->fixPublisherNameAndLocation($metadata);

		// Convert the genre
		if (isset($metadata['genre'])) {
			$genre = $metadata['genre'];
			import('lib.pkp.classes.metadata.nlm.OpenUrlNlmCitationSchemaCrosswalkFilter');
			$genreMap = OpenUrlNlmCitationSchemaCrosswalkFilter::_getOpenUrlGenreTranslationMapping();
			$metadata['[@publication-type]'] = (isset($genreMap[$genre]) ? $genreMap[$genre] : $genre);
			unset($metadata['genre']);
		}

		// Convert article title to source for dissertations
		if (isset($metadata['[@publication-type]']) && $metadata['[@publication-type]'] == NLM_PUBLICATION_TYPE_THESIS && isset($metadata['article-title'])) {
			$metadata['source'] = $metadata['article-title'];
			unset($metadata['article-title']);
		}

		unset($metadata['raw_string']);

		return $this->getNlmCitationDescriptionFromMetadataArray($metadata);
	}
}
?>