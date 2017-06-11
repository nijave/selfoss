<?php

namespace spouts\rss;

/**
 * Plugin for fetching the news from pro-linux with the full text.
 * Based on heise.php
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 * @author     Sebastian Gibb <mail@sebastiangibb.de>
 */
class prolinux extends feed {
    /** @var string name of spout */
    public $name = 'News: Pro-Linux';

    /** @var string description of this source type */
    public $description = 'This feed fetches the pro-linux news with full content (not only the header as content)';

    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * - Values for type: text, password, checkbox, select
     * - Values for validation: alpha, email, numeric, int, alnum, notempty
     *
     * When type is "select", a new entry "values" must be supplied, holding
     * key/value pairs of internal names (key) and displayed labels (value).
     * See /spouts/rss/heise for an example.
     *
     * e.g.
     * array(
     *   "id" => array(
     *     "title"      => "URL",
     *     "type"       => "text",
     *     "default"    => "",
     *     "required"   => true,
     *     "validation" => array("alnum")
     *   ),
     *   ....
     * )
     *
     * @var bool|mixed
     */
    public $params = [
        'section' => [
            'title' => 'Section',
            'type' => 'select',
            'values' => [
                'main' => 'Alles',
                'news' => 'Nachrichten/Artikel',
                'polls' => 'Umfragen',
                'security' => 'Sicherheitsmeldungen',
                'lugs' => 'Linux User Groups (LUGs)',
                'comments' => 'Kommentare'
            ],
            'default' => 'main',
            'required' => true,
            'validation' => []
        ]
    ];

    /**
     * addresses of feeds for the sections
     */
    private $feedUrls = [
        'main' => 'http://www.pro-linux.de/NB3/rss/1/4/atom_alles.xml',
        'news' => 'http://www.pro-linux.de/NB3/rss/2/4/atom_aktuell.xml',
        'polls' => 'http://www.pro-linux.de/NB3/rss/3/4/atom_umfragen.xml',
        'security' => 'http://www.pro-linux.de/NB3/rss/5/4/atom_sicherheit.xml',
        'lugs' => 'http://www.pro-linux.de/rss/7/4/atom_lugs.xml',
        'comments' => 'http://www.pro-linux.de/NB3/rss/6/4/atom_kommentare.xml'
    ];

    /**
     * delimiters of the article text
     *
     * elements: start tag, attribute of start tag, value of start tag attribute, end
     */
    private $textDivs = [
        ['div', 'id', 'news', '<div class="endline"></div>'],        // news
        ['div', 'id', 'polldetail', '<div class="endline"></div>'],  // polls
        ['table', 'id', 'secdetail', '</table>'],                    // security
        ['div', 'class', 'descr', '</div>'],                         // comments
        ['div', 'id', 'comments', '<div class="tail">']              // comments
    ];

    /**
     * loads content for given source
     *
     * @param string $url
     *
     * @return void
     */
    public function load($params) {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    /**
     * returns the xml feed url for the source
     *
     * @param mixed $params params for the source
     *
     * @return string url as xml
     */
    public function getXmlUrl($params) {
        return $this->feedUrls[$params['section']];
    }

    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if ($this->items !== false && $this->valid()) {
            $originalContent = file_get_contents($this->getLink());
            foreach ($this->textDivs as $div) {
                $content = $this->getTag($div[1], $div[2], $originalContent, $div[0], $div[3]);
                if (is_array($content) && count($content) >= 1) {
                    $content = $content[0];
                    $content = preg_replace_callback(',<a([^>]+)href="([^>"\s]+)",i', function($matches) {
                        return "<a\1href=\"" . \spouts\rss\prolinux::absolute("\2", 'http://www.pro-linux.de') . '"';
                    }, $content);
                    $content = preg_replace_callback(',<img([^>]+)src="([^>"\s]+)",i', function($matches) {
                        return "<img\1src=\"" . \spouts\rss\prolinux::absolute("\2", 'http://www.pro-linux.de') . '"';
                    }, $content);

                    return $content;
                }
            }
        }

        return parent::getContent();
    }

    /**
     * get tag by attribute
     * taken from http://www.catswhocode.com/blog/15-php-regular-expressions-for-web-developers
     *
     * @return string content
     * @return string $attr attribute
     * @return string $value necessary value
     * @return string $xml data string
     * @return string $tag optional tag
     */
    private function getTag($attr, $value, $xml, $tag = null, $end = null) {
        if (is_null($tag)) {
            $tag = '\w+';
        } else {
            $tag = preg_quote($tag);
        }

        if (is_null($end)) {
            $end = '</\1>';
        } else {
            $end = preg_quote($end);
        }

        $attr = preg_quote($attr);
        $value = preg_quote($value);
        $tag_regex = '|<(' . $tag . ')[^>]*' . $attr . '\s*=\s*([\'"])' . $value . '\2[^>]*>(.*?)' . $end . '|ims';
        preg_match_all($tag_regex, $xml, $matches, PREG_PATTERN_ORDER);

        return $matches[3];
    }

    /**
     * convert relative url to absolute
     *
     * @return string absolute url
     * @return string $relative url
     * @return string $absolute url
     */
    public static function absolute($relative, $absolute) {
        if (preg_match(',^(https?://|ftp://|mailto:|news:),i', $relative)) {
            return $relative;
        }

        return $absolute . $relative;
    }
}
