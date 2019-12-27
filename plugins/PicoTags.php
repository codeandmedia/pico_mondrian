<?php

/**
 * PicoTags
 *
 * Adds page tagging functionality to Pico.
 *
 * @author Brice Boucard
 * @link https://github.com/bricebou/PicoTags/
 * @license http://bricebou.mit-license.org/
 *
 * Originally made by [Dan Reeves](https://github.com/danreeves/picotags),
 * based on [Pico Tags by Szymon Kaliski](https://github.com/szymonkaliski/Pico-Tags-Plugin),
 * but only uses the provided hooks and leaves the Pico core alone.
 */
class PicoTags extends AbstractPicoPlugin {
    const API_VERSION = 2;

    protected $enabled = null;

    public $is_tag;
    public $current_tag;
    public $tag_list = array();

    /*
        Declaring two functions for sorting tags with special chars
        Thanks to Olivier Laviale (https://github.com/olvlvl)
        http://www.weirdog.com/blog/php/trier-les-cles-accentuees-dun-tableau-associatif.html
    */
    private function wd_remove_accents($str, $charset='utf-8')
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères

        return $str;
    }
    private function wd_unaccent_compare_ci($a, $b)
    {
        return strcmp(strtolower($this->wd_remove_accents($a)), strtolower($this->wd_remove_accents($b)));
    }

    public function tags_sorting(&$array)
    {
        $array = array_flip($array);
        uksort($array, 'PicoTags::wd_unaccent_compare_ci');
        $array = array_flip($array);
    }

    public function onConfigLoaded(array &$config)
    {
        if (isset($config['ptags']['nbcol']))
        {
            $this->ptags_nbcol = $config['ptags']['nbcol'];
        }
        if (isset($config['ptags']['asort']))
        {
            $this->ptags_sort = $config['ptags']['asort'];
        }
        if (isset($config['ptags']['delunique']))
        {
            $this->ptags_delunique = $config['ptags']['delunique'];
        }
        if (isset($config['ptags']['template']))
        {
            $this->ptags_template = $config['ptags']['template'];
        }
        if (isset($config['ptags']['excluded_templates']))
        {
            $this->ptags_exclude = explode("|", $config['ptags']['excluded_templates']);
        }
    }

    public function onRequestUrl(&$url)
    {
        // Set is_tag to true if the first four characters of the URL are 'tag/'
        $this->is_tag = (substr($url, 0, 4) === 'tag/');
        // If the URL does start with 'tag/', grab the rest of the URL
        if ($this->is_tag) $this->current_tag = urldecode(substr(strip_tags($url), 4));
    }

    public function onMetaHeaders(array &$headers)
    {
        // Define tags variable to avoid
        // PHP Notice:  Undefined index: Tags
        $headers['tags'] = 'Tags';
    }

    public function onMetaParsed(array &$meta)
    {
        // Parses meta.tags to ensure it is an array
        if (isset($meta['Tags']) && !is_array($meta['Tags']) && $meta['Tags'] !== '') {
            $meta['Tags'] = explode(',', $meta['Tags']);
            // Sort alphabetically the tags for articles/blog posts
            if (isset($this->ptags_sort) and $this->ptags_sort === true)
            {
                $this->tags_sorting($meta['Tags']);
            }
        }
    }

    public function exclude_from_tag_list(&$lapage)
    {
        if(isset($this->ptags_exclude) && $lapage["meta"]["template"] != "")
        {
            if(!in_array($lapage["meta"]["template"], $this->ptags_exclude))
                return false;
            else
                return true;
        }
        else
            return false;
    }

    public function onPagesLoaded(array &$pages)
    {
        // If the URL starts with 'tag/' do this different logic
        if ($this->is_tag === true) 
        {
            // Init $new_pages array
            $new_pages = array();

            foreach ($pages as $page)
            {
                if ($page['meta']['Tags'] and $this->exclude_from_tag_list($page) == false)
                {
                    if (!is_array($page['meta']['Tags']))
                    {
                        $page['meta']['Tags'] = explode(',', $page['meta']['Tags']);
                        // Sort alphabetically the tags for tag pages
                        // (works on my OVH server)

                        if (isset($this->ptags_sort) and $this->ptags_sort === true)
                        {
                            $this->tags_sorting($page['meta']['Tags']);
                        }

                        foreach ($page['meta']['Tags'] as $tag)
                        {   
                            // And add them to the tag_list array
                            $tag_list[] = $tag;
                            // If the tag matches the current_tag
                            if ($tag === $this->current_tag)
                            {
                                // Add that page to the new_pages
                                $new_pages[] = $page;
                            }
                        }
                    }
                }
            }
            // Removing from the tags list the tags with only one reference
            // Change the value to $config['ptags_delunique'] = true; in the config.php

            if (isset($this->ptags_delunique) and $this->ptags_delunique === true)
            {
                foreach(array_count_values($tag_list) as $val => $occ)
                {
                    if($occ == 1)
                    {
                        $key = array_search($val, $tag_list);
                        unset($tag_list[$key]);
                    }
                }
            }   

            // Sort alphabetically, case insensitive
            // Change the value to $config['ptags_sort'] = true; in the config.php

            if (isset($this->ptags_sort) and $this->ptags_sort === true)
            {

                $tag_list_sorted = array();
                $tag_list_sorted = $tag_list;
                $tag_list = array();
                $this->tags_sorting($tag_list_sorted);
                foreach ($tag_list_sorted as $key => $value) {
                    $tag_list[] = $value;
                }
                // Add the tag list to the class scope, taking out duplicate or empty values
                $this->tag_list = array_unique(array_filter($tag_list));
            }
            else {
                // Add the tag list to the class scope, taking out duplicate or empty values
                $this->tag_list = array_unique(array_filter($tag_list));
            }
            $this->tag_pages = $new_pages;
        }
        else
        { // Workaround
            $new_pages = array();
            foreach ($pages as $page) {
                if (!is_array($page['meta']['Tags'])) {
                    $page['meta']['Tags'] = explode(',', $page['meta']['Tags']);
                }
                // Loop through the tags
                foreach ($page['meta']['Tags'] as $tag) {
                    // And add them to the tag_list array
                    $tag_list[] = $tag;
                }

                $new_pages[] = $page;
            }
            // Removing from the tags list the tags with only one reference
            // Change the value to $config['ptags_delunique'] = true; in the config.php

            if (isset($this->ptags_delunique) and $this->ptags_delunique === true)
            {
                foreach(array_count_values($tag_list) as $val => $occ)
                {
                    if($occ == 1)
                    {
                        $key = array_search($val, $tag_list);
                        unset($tag_list[$key]);
                    }
                }
            }
            $this->tag_pages = $new_pages;
            if (!empty($tag_list)) {
            	$this->tag_list = array_unique(array_filter($tag_list));
            }
        }
    }

    public function onPageRendering(&$templateName, array &$twigVariables)
    {
        if ($this->is_tag && in_array($this->current_tag, $this->tag_list))
        {
        	/* Avoiding / Overriding 404 errors on tag pages
        		Thanks to Dan Reeves first version of PicoTags
        		https://github.com/danreeves/picotags
        	*/
        	header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
            if (isset($this->ptags_template) && $this->ptags_template != '')
            {
                $templateName = $this->ptags_template;
            }
            // Set page title to #TAG
            $twigVariables['meta']['title'] = "#" . $this->current_tag;
            $twigVariables['tag_pages']= $this->tag_pages;
        }
        $twigVariables['current_tag'] = $this->current_tag;
        $twigVariables['tag_list'] = $this->tag_list; // {{ tag_list }} in an array
        if (isset($this->ptags_nbcol) && $this->ptags_nbcol != '')
        {
            $nbtags = sizeof($this->tag_list);
            $nbtagscol = ceil ($nbtags/$this->ptags_nbcol);
            $tag_list_cut = array();
            $tag_list_sorted_cut = array();
            for ($i=0;$i<$this->ptags_nbcol;$i++)
            {
                $this->tag_list_cut = array_slice($this->tag_list, $i*$nbtagscol, $nbtagscol);
                $twig_vars['tag_list_'.$i] = $this->tag_list_cut;
            }
        }
    }
}
?>