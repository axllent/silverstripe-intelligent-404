<?php

namespace Axllent\Intelligent404;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;

/**
 * SilverStripe Intelligent 404
 * ============================
 *
 * Extension to add additional functionality to the existing 404 ErrorPage.
 * It tries to guess the intended page by matching up the last segment of
 * the url to all SiteTree pages (and optionally other DataObjects).
 * It also uses soundex to match similar sounding page links to find alternatives.
 */

class Intelligent404 extends Extension
{
    /**
     * @config
     * allow this to work in dev mode
     */
    private static $allow_in_dev_mode = false;

    /**
     * @config
     * auto-redirect if only one exact match is found
     */
    private static $redirect_on_single_match = true;

    /**
     * @config
     * auto-redirect if only one exact match is found
     */
    private static $data_objects = [
        '\\SilverStripe\\CMS\\Model\\SiteTree' => [
            'group' => 'Pages',
            'filter' => [],
            'exclude' => [
                'ClassName' => [
                    'SilverStripe\\CMS\\Model\\ErrorPage',
                    'SilverStripe\\CMS\\Model\\RedirectorPage',
                    'SilverStripe\\CMS\\Model\\VirtualPage'
                ]
            ]
        ]
    ];

    public function onAfterInit()
    {
        $error_code = $this->owner->failover->ErrorCode ? $this->owner->failover->ErrorCode : 404;

        if ($error_code != 404) {
            return; // we only deal with 404
        }

        // Make sure the SiteTree's 404 page isn't being called
        // (via `/dev/build`) to generate `assets/error-404.html`
        $request = !(empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : false;
        if (
            $request &&
            $error_page = ErrorPage::get()->filter('ErrorCode', $error_code)->first()
        ) {
            if ($error_page->Link() == $request) {
                return;
            }
        }

        if (
            !Director::isDev() ||
            Config::inst()->get('Axllent\\Intelligent404\\Intelligent404', 'allow_in_dev_mode')
        ) {
            $extract = preg_match('/^([a-z0-9\.\_\-\/]+)/i', $_SERVER['REQUEST_URI'], $rawString);

            if ($extract) {
                $uri = preg_replace('/\.(aspx?|html?|php[34]?)$/i', '', $rawString[0]); // skip known page extensions
                $parts = preg_split('/\//', $uri, -1, PREG_SPLIT_NO_EMPTY);
                $page_key = array_pop($parts);
                $sounds_like = soundex($page_key);
                $exact_matches = [];
                $possible_matches = [];
                $results_list = [];

                $data_objects = Config::inst()->get('Axllent\\Intelligent404\\Intelligent404', 'data_objects');

                if (!$data_objects || !is_array($data_objects)) {
                    return;
                }

                foreach ($data_objects as $class => $config) {
                    if (
                        !ClassInfo::exists($class) ||
                        !method_exists($class, 'Link')
                    ) {
                        continue; // invalid class (does not exist)
                    }

                    $group = !empty($config['group']) ? $config['group'] : 'Pages';

                    if (empty($results_list[$group])) {
                        $results_list[$group] = ArrayList::create();
                    }

                    $results = $class::get(); // all results

                    if (!empty($config['filter'])) {
                        $results = $results->filter($config['filter']); // filter
                    }

                    if (!empty($config['exclude'])) {
                        $results = $results->exclude($config['exclude']); // exclude
                    }

                    foreach ($results as $result) {
                        $link = $result->Link();

                        $rel_link = Director::makeRelative($link);

                        if (!$rel_link) {
                            continue; // no link or `/`
                        }

                        $url_parts = preg_split('/\//', $rel_link, -1, PREG_SPLIT_NO_EMPTY);

                        $url_segment = end($url_parts);

                        if ($url_segment == $page_key) {
                            $results_list[$group]->push($result);
                            $exact_matches[$link] = $link;
                        } elseif ($sounds_like == soundex($url_segment)) {
                            $results_list[$group]->push($result);
                            $possible_matches[$link] = $link;
                        }
                    }
                }

                $exact_count = count($exact_matches);
                $possible_count = count($possible_matches);

                $redirect_on_single_match = Config::inst()->get('Axllent\\Intelligent404\\Intelligent404', 'redirect_on_single_match');

                if ($exact_count == 1 && $redirect_on_single_match) {
                    return $this->RedirectToPage(array_shift($exact_matches));
                } elseif ($exact_count == 0 && $possible_count == 1 && $redirect_on_single_match) {
                    return $this->RedirectToPage(array_shift($possible_matches));
                } elseif ($exact_count > 0 || $possible_count > 0) {
                    $content = $this->owner->customise($results_list)->renderWith(
                        ['Intelligent404Options']
                    );
                    $this->owner->Content .= $content; // add to $Content
                }
            }
        }
    }

    /*
     * Internal redirect function
     * @param string
     * @return 301 response / redirect
     */
    private function RedirectToPage($url)
    {
        $response = new HTTPResponse();
        $response->redirect($url, 301);
        throw new HTTPResponse_Exception($response);
    }
}
