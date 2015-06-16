<?php
/**
 * SilverStripe Intelligent 404
 * ============================
 *
 * Extension to add additional functionality to the existing 404 ErrorPage.
 * It tries to guess the intended page by matching up the last segment of
 * the url to all SiteTree pages. It also uses soundex to match similar
 * sounding pages to find other alternatives.
 *
 * Extract into your SilverStripe 3 installation directory.
 *
 * License: MIT-style license http://opensource.org/licenses/MIT
 * Authors: Techno Joy development team (www.technojoy.co.nz)
 */

class Intelligent404 extends Extension {

	public function onAfterInit() {

		if (!Director::isDev()) { // Only on live site

			$errorcode = $this->owner->failover->ErrorCode ? $this->owner->failover->ErrorCode : 404;

			$extract = preg_match('/^([a-z0-9\.\_\-\/]+)/i', $_SERVER['REQUEST_URI'], $rawString);

			if ($errorcode == 404 && $extract) {
				$uri = preg_replace('/\.(aspx?|html?|php[34]?)$/i', '', $rawString[0]);
				$parts = preg_split('/\//', $uri, -1, PREG_SPLIT_NO_EMPTY);
				$page_key = array_pop($parts);
				$sounds_like = soundex($page_key);

				// extend ignored classes with child classes
				$ignoreClassNames = array();
				if ($configClasses = Config::inst()->get('Intelligent404', 'intelligent_404_ignored_classes')) {
					foreach ($configClasses as $class) {
						$ignoreClassNames = array_merge($ignoreClassNames, array_values(ClassInfo::subclassesFor($class)));
					}
				}

				// get all pages
				$SiteTree = SiteTree::get()->exclude('ClassName', $ignoreClassNames);

				// Translatable support
				if (class_exists('Translatable')) {
					$SiteTree = $SiteTree->filter('Locale', Translatable::get_current_locale());
				}

				// Multisites support
				if (class_exists('Multisites')) {
					$SiteTree = $SiteTree->filter('SiteID', Multisites::inst()->getCurrentSiteId());
				}

				$ExactMatches = new ArrayList();
				$PossibleMatches = new ArrayList();

				foreach ($SiteTree as $page) {
					if ($page->URLSegment == $page_key) {
						$ExactMatches->push($page);
					}
					elseif ($sounds_like == soundex($page->URLSegment)) {
						$PossibleMatches->push($page);
					}
				}

				$ExactCount = $ExactMatches->Count();
				$PossibleCount = $PossibleMatches->Count();

				if ($ExactCount == 1) {
					return $this->RedirectToPage($ExactMatches->First()->Link());
				}

				elseif ($ExactCount == 0 && $PossibleCount == 1) {
					return $this->RedirectToPage($PossibleMatches->First()->Link());
				}

				elseif ($ExactCount > 1 || $PossibleCount > 1) {
					$ExactMatches->merge($PossibleMatches);
					$content = $this->owner->customise(array(
					    'Pages' => $ExactMatches
					))->renderWith(
					    array('Intelligent404Options')
					);
					$this->owner->Content .= $content;
				}

			}

		}

	}

	/*
	 * Internal redirect function
	 * @param string
	 * @return 301 response / redirect
	 */
	public function RedirectToPage($url) {
		$response = new SS_HTTPResponse();
		$response->redirect($url, 301);
		throw new SS_HTTPResponse_Exception($response);
	}

}
