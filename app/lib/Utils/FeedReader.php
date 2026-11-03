<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/FeedReader.php : RSS and Atom feed parser
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 * Fetches and parses RSS 1.0, RSS 2.0 and Atom feeds into arrays. Replaces Zend_Feed.
 */
class FeedReader {
	# -------------------------------------------------------
	/**
	 * Fetch and parse feed
	 *
	 * @param string $url
	 * @return array See FeedReader::parse()
	 * @throws Exception If the feed cannot be fetched or parsed
	 */
	static public function import(string $url) : array {
		if (!($content = caQueryExternalWebservice($url))) {
			throw new Exception(_t('Could not fetch feed %1', $url));
		}
		return self::parse($content);
	}
	# -------------------------------------------------------
	/**
	 * Parse feed. The returned array has keys title, link, description and items. Items is a list of feed entries,
	 * each with keys id, title, link, description and author.
	 *
	 * @param string $content Feed XML
	 * @return array
	 * @throws Exception If the content is not a valid feed
	 */
	static public function parse(string $content) : array {
		$prev = libxml_use_internal_errors(true);
		$xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
		libxml_clear_errors();
		libxml_use_internal_errors($prev);
		if ($xml === false) {
			throw new Exception(_t('Feed is not valid XML'));
		}

		switch(strtolower($xml->getName())) {
			case 'feed':		// Atom
				$xml->registerXPathNamespace('a', 'http://www.w3.org/2005/Atom');
				$feed = self::_atomEntry($xml);
				$entries = $xml->xpath('a:entry') ?: ($xml->entry ?? []);
				break;
			case 'rss':			// RSS 2.0
				$channel = $xml->channel;
				$feed = self::_rssItem($channel);
				$entries = $channel->item ?? [];
				break;
			case 'rdf':			// RSS 1.0
				$rss = $xml->children('http://purl.org/rss/1.0/');
				$feed = self::_rssItem($rss->channel);
				$entries = $rss->item ?? [];
				break;
			default:
				throw new Exception(_t('Content is not an RSS or Atom feed'));
		}

		$feed['items'] = [];
		foreach($entries as $entry) {
			$feed['items'][] = (strtolower($xml->getName()) === 'feed') ? self::_atomEntry($entry) : self::_rssItem($entry);
		}
		return $feed;
	}
	# -------------------------------------------------------
	/**
	 * Extract values from RSS channel or item
	 */
	static private function _rssItem(?SimpleXMLElement $node) : array {
		if (!$node) { return ['id' => null, 'title' => null, 'link' => null, 'description' => null, 'author' => null]; }
		$dc = $node->children('http://purl.org/dc/elements/1.1/');
		$author = trim((string)($node->author ?? '')) ?: trim((string)($dc->creator ?? ''));
		return [
			'id' => trim((string)($node->guid ?? '')) ?: trim((string)($node->link ?? '')),
			'title' => trim((string)($node->title ?? '')),
			'link' => trim((string)($node->link ?? '')),
			'description' => trim((string)($node->description ?? '')),
			'author' => strlen($author) ? $author : null
		];
	}
	# -------------------------------------------------------
	/**
	 * Extract values from Atom feed or entry
	 */
	static private function _atomEntry(SimpleXMLElement $node) : array {
		$a = $node->children('http://www.w3.org/2005/Atom');
		if (!sizeof($a)) { $a = $node; }	// feed without namespace

		$link = null;
		foreach($a->link ?? [] as $l) {
			$attr = $l->attributes();
			$rel = (string)$attr['rel'];
			if (!$rel || ($rel === 'alternate')) { $link = (string)$attr['href']; break; }
			if (!$link) { $link = (string)$attr['href']; }
		}
		$description = trim((string)($a->summary ?? '')) ?: trim((string)($a->content ?? '')) ?: trim((string)($a->subtitle ?? ''));
		$author = isset($a->author) ? trim((string)$a->author->name) : '';
		return [
			'id' => trim((string)($a->id ?? '')),
			'title' => trim((string)($a->title ?? '')),
			'link' => $link,
			'description' => $description,
			'author' => strlen($author) ? $author : null
		];
	}
	# -------------------------------------------------------
}
