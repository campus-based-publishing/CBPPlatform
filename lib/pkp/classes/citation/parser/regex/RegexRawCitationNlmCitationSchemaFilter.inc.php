<?php

/**
 * @defgroup citation_parser_regex
 */

/**
 * @file classes/citation/parser/regex/RegexRawCitationNlmCitationSchemaFilter.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegexRawCitationNlmCitationSchemaFilter
 * @ingroup citation_parser_regex
 *
 * @brief A simple regex based citation parsing filter. Uses regexes to break a
 *  citation string into metadata elements. Works best on ICMJE/Vancouver-type
 *  journal citations.
 *
 *  TODO: Rewrite this filter so that it becomes easy to extend with custom
 *  expressions.
 */

import('lib.pkp.classes.citation.NlmCitationSchemaFilter');

class RegexRawCitationNlmCitationSchemaFilter extends NlmCitationSchemaFilter {
	/*
	 * Constructor
	 */
	function RegexRawCitationNlmCitationSchemaFilter() {
		$this->setDisplayName('RegEx');

		parent::NlmCitationSchemaFilter(NLM_CITATION_FILTER_PARSE);
	}

	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.classes.citation.parser.regex.RegexRawCitationNlmCitationSchemaFilter';
	}

	/**
	 * @see Filter::process()
	 * @param $citationString string
	 * @return MetadataDescription
	 */
	function &process($citationString) {
		// Initialize the parser result array
		$matches = array();
		$metadata = array();

		// Parse out any embedded URLs
		$urlPattern = '(<?(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.,]*(\?[^\s>]+)?)?)?)>?)';
		if (String::regexp_match_get($urlPattern, $citationString, $matches)) {
			// Assume that the URL is a link to the resource.
			$metadata['uri'] = $matches[1];

			// Remove the URL from the citation string
			$citationString = String::regexp_replace($urlPattern, '', $citationString);

			// If the URL is a link to PubMed, save the PMID
			$pmIdExpressions = array(
				'/list_uids=(?P<pmId>\d+)/i',
				'/pubmed.*details_term=(?P<pmId>\d+)/i',
				'/pubmedid=(?P<pmId>\d+)/i'
			);
			foreach ($pmIdExpressions as $pmIdExpression) {
				if (String::regexp_match_get($pmIdExpression, $matches[1], $pmIdMatches) ) {
					$metadata['pub-id[@pub-id-type="pmid"]'] = $pmIdMatches['pmId'];
					break;
				}
			}
		}

		// Parse out an embedded PMID and remove from the citation string
		$pmidPattern = '/pmid:?\s*(\d+)/i';
		if (String::regexp_match_get($pmidPattern, $citationString, $matches) ) {
			$metadata['pub-id[@pub-id-type="pmid"]'] = $matches[1];
			$citationString = String::regexp_replace($pmidPattern, '', $citationString);
		}

		// Parse out an embedded DOI and remove it from the citation string
		$doiPattern = '/doi:?\s*(\S+)/i';
		if (String::regexp_match_get($doiPattern, $citationString, $matches) ) {
			$metadata['pub-id[@pub-id-type="doi"]'] = $matches[1];
			$citationString = String::regexp_replace($doiPattern, '', $citationString);
		}

		// Parse out the access date if we have one and remove it from the citation string
		$accessDatePattern = '/accessed:?\s*([\s\w]+)/i';
		if (String::regexp_match_get($accessDatePattern, $citationString, $matches)) {
			$metadata['access-date'] = $matches[1];
			$citationString = String::regexp_replace($accessDatePattern, '', $citationString );
		}

		// Clean out square brackets
		$citationString = String::regexp_replace('/\[(\s*(pubmed|medline|full text)\s*)*]/i', '', $citationString);

		// Book citation
		$unparsedTail = '';
		if (String::regexp_match_get("/\s*(?P<authors>[^\.]+)\.\s*(?P<source>.*?)\s*(?P<publisherLoc>[^\.]*):\s*(?P<publisherName>[^:]*?);\s*(?P<date>\d\d\d\d.*?)(?P<tail>.*)/", $citationString, $matches)) {
			$metadata['[@publication-type]'] = NLM_PUBLICATION_TYPE_BOOK;
			$metadata['author'] = $matches['authors'];
			$metadata['source'] = $matches['source'];
			$metadata['publisher-loc'] = $matches['publisherLoc'];
			$metadata['publisher-name'] = $matches['publisherName'];
			$metadata['date'] = $matches['date'];
			$unparsedTail = $matches['tail'];

		// Journal citation
		} elseif (String::regexp_match_get("/\s*(?P<authors>[^\.]+)\.\s*(?P<titleSource>.*)\s*(?P<date>\d\d\d\d.*?);(?P<volumeAndIssue>[^:]+):(?P<tail>.*)/", $citationString, $matches)) {
			$metadata['[@publication-type]'] = NLM_PUBLICATION_TYPE_JOURNAL;
			$metadata['author'] = $matches['authors'];

			$titleSource = array();
			if (String::regexp_match_get("/(.*[\.!\?])(.*)/", trim($matches['titleSource'], " ."), $titleSource)) {
				$metadata['article-title'] = $titleSource[1];
				$metadata['source'] = $titleSource[2];
			}
			$metadata['date'] = $matches['date'];

			$volumeAndIssue = array();
			if (String::regexp_match_get("/([^\(]+)(\(([^\)]+)\))?/", $matches['volumeAndIssue'], $volumeAndIssue)) {
				$metadata['volume'] = $volumeAndIssue[1];
				if (isset($volumeAndIssue[3])) $metadata['issue'] = $volumeAndIssue[3];
			}

			$unparsedTail = $matches['tail'];

		// Web citation with or without authors
		} elseif (String::regexp_match_get("/\s*(?P<citationSource>.*?)\s*URL:\s*(?P<tail>.*)/", $citationString, $matches)) {
			$unparsedTail = $matches['tail'];

			$citationParts = explode(".", trim($matches['citationSource'], '. '));
			switch (count($citationParts)) {
				case 0:
					// This case should never occur...
					assert(false);
					break;

				case 1:
					// Assume this to be a title for the web site.
					$metadata['article-title'] = $citationParts[0];
					break;

				case 2:
					// Assume the format: Authors. Title.
					$metadata['author'] = $citationParts[0];
					$metadata['article-title'] = $citationParts[1];
					break;

				default:
					// Assume the format: Authors. Article Title. Journal Title.
					$metadata['author'] = array_shift($citationParts);
					// The last part is assumed to be the journal title
					$metadata['source'] = array_pop($citationParts);
					// Everything in between is assumed to belong to the article title
					$metadata['article-title'] = implode('.', $citationParts);
			}
		}

		// TODO: Handle in-ref titles, eg. with editor lists

		// Extract page numbers if possible
		$pagesPattern = "/^[:p\.\s]*(?P<fpage>[Ee]?\d+)(-(?P<lpage>\d+))?/";
		if (!empty($unparsedTail) && String::regexp_match_get($pagesPattern, $unparsedTail, $matches)) {
			$metadata['fpage'] = $matches['fpage'];
			if (isset($matches['lpage'])) $metadata['lpage'] = $matches['lpage'];

			// Add the unparsed part of the citation string as a comment so it doesn't get lost.
			$comment = String::trimPunctuation(String::regexp_replace($pagesPattern, '', $unparsedTail));
			if (!empty($comment)) $metadata['comment'] = $comment;
		}

		// Make the meta-data fully NLM citation compliant
		$metadata =& $this->postProcessMetadataArray($metadata);

		// Create the NLM citation description
		return $this->getNlmCitationDescriptionFromMetadataArray($metadata);
	}
}
?>