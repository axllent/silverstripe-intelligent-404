<?php
/**
 * SilverStripe Intelligent 404
 * ============================
 *
 * Extension to add additional functionality to the existing 404 ErrorPage.
 * It tries to guess the intended page by matching up the last segments of
 * the url to all SiteTree pages. It also uses soundex to match similar
 * sounding pages to find toher alternatives.
 *
 * Extract into your SilverStripe 3 installation directory.
 *
 * License: MIT-style license http://opensource.org/licenses/MIT
 * Authors: Techno Joy development team (www.technojoy.co.nz)
 */

class Intelligent404 extends Extension {

	public static $OptionsHeader = '<h3>Were you looking for one of the following?</h3>';

	public static $IgnoreClassNames = array('ErrorPage', 'RedirectorPage', 'VirtualPage');

	public function onAfterInit() {

		if (!Director::isDev()) { // Only on live site

			$errorcode = $this->owner->failover->ErrorCode ? $this->owner->failover->ErrorCode : 404;

			$extract = preg_match('/^([a-z0-9\.\_\-\/]+)/i', $_SERVER['REQUEST_URI'], $rawString);

			if ($errorcode == 404 && $extract) {
				$uri = preg_replace('/\.(aspx?|html?|php[34]?)$/', '', $rawString[0]);
				$parts = preg_split('/\//', $uri, -1, PREG_SPLIT_NO_EMPTY);
				$page_key = array_pop($parts);
				$sounds_like = soundex($page_key);

				$SiteTree = SiteTree::get()->exclude('ClassName', self::$IgnoreClassNames);

				$ExactMatches = new ArrayList();
				$PossibleMatches = new ArrayList();

				foreach ($SiteTree as $page) {
					if ($page->URLSegment == $page_key)
						$ExactMatches->push($page);
					else if ($sounds_like == soundex($page->URLSegment)) {
						$PossibleMatches->push($page);
					}
				}

				$ExactCount = $ExactMatches->Count();
				$PossibleCount = $PossibleMatches->Count();

				if ($ExactCount == 1)
					return $this->RedirectToPage($ExactMatches->First()->Link());

				else if ($ExactCount == 0 && $PossibleCount == 1)
					return $this->RedirectToPage($PossibleMatches->First()->Link());

				else if ($ExactCount > 1 || $PossibleCount > 1) {

					$this->owner->Content .= self::$OptionsHeader . '<ul>';

					$ExactMatches->merge($PossibleMatches);

					foreach ($ExactMatches as $p)
						$this->owner->Content .= '<li><a href="' . $p->Link() . '">
							<strong>' . htmlspecialchars($p->MenuTitle) . '</strong> -
							<i>' . $p->Link() . '</i></a>
						</li>';

					$this->owner->Content .= '</ul>';
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
		$response = new SS_HTTPResponse_Exception();
		$response->getResponse()->redirect($url, 301);
		$this->owner->popCurrent();
		throw $response;
	}

}