<?php
//
// +----------------------------------------------------------------------+
// | Stealth ISP QOS system                                               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006-2007 Ing. Lukas Dziadkowiec                       |
// +----------------------------------------------------------------------+
// | This source file is part of Stealth ISP QOS system,                  |
// | see LICENSE for licence details.                                     |
// +----------------------------------------------------------------------+
// | Authors: Lukas Dziadkowiec <stealth.home@seznam.cz>                  |
// +----------------------------------------------------------------------+

/**
 * @author  Lukas Dziadkowiec <stealth.home@seznam.cz>
 */

/** ensure this file is being included by a parent file */
defined( 'VALID_MODULE' ) or die(_("Direct access into this section is not allowed"));

/**
* Page navigation support class
*/
class PageNav {
    /** @var int The record number to start dislpaying from */
    var $limitstart = null;
    /** @var int Number of rows to display per page */
    var $limit = null;
    /** @var int Total number of rows */
    var $total = null;
    var $suffix = null;

    public function __construct( $total, $limitstart, $limit, $suffix="" ) {
        $this->total = intval( $total );
        $this->limitstart = max( $limitstart, 0 );
        $this->limit = max( $limit, 1 );
        if ($this->limit > $this->total) {
            $this->limitstart = 0;
        }
        if (($this->limit-1)*$this->limitstart > $this->total) {
            $this->limitstart -= $this->limitstart % $this->limit;
        }
        $this->suffix = $suffix;
    }
    /**
    * @return string The html for the limit # input box
    */
    function getLimitBox () {
        $limits = array(5,10,15,20,25,30,50,100,200,500);
        $ohtml = "";
        for ($i=0, $n = count($limits); $i < $n; $i++) {
            if ($limits[$i] == $this->limit) {
                $ohtml .= "<option value=\"$limits[$i]\" selected=\"selected\">$limits[$i]</option>\n";
            } else {
                $ohtml .= "<option value=\"$limits[$i]\">$limits[$i]</option>\n";
            }
        }

        // build the html select list
        $html  = "\n<select name=\"limit".$this->suffix."\" class=\"inputbox\" size=\"1\" onchange=\"document.adminForm.submit();\">\n";
        $html .= $ohtml;
        $html .= "</select>\n";
        $html .= "<input type=\"hidden\" name=\"limitstart".$this->suffix."\" value=\"0\" />\n";
        return $html;
    }
    /**
    * @return string The html for the pages counter, eg, Results 1-10 of x
    */
    function getPagesCounter() {
        $html = '';
        $from_result = $this->limitstart+1;
        if ($this->limitstart + $this->limit < $this->total) {
            $to_result = $this->limitstart + $this->limit;
        } else {
            $to_result = $this->total;
        }
        if ($this->total > 0) {
            $html .= sprintf(_("Records %d to %d from %d"), $from_result, $to_result, $this->total);
        } else {
            $html .= _("No record found");
        }
        return $html;
    }
    /**
    * @return string The html links for pages, eg, previous, next, 1 2 3 ... x
    */
    function getPagesLinks() {
        $html = '';
        $displayed_pages = 10;
        $total_pages = ceil( $this->total / $this->limit );
        $this_page = ceil( ($this->limitstart+1) / $this->limit );
        $start_loop = (floor(($this_page-1)/$displayed_pages))*$displayed_pages+1;
        if ($start_loop + $displayed_pages - 1 < $total_pages) {
            $stop_loop = $start_loop + $displayed_pages - 1;
        } else {
            $stop_loop = $total_pages;
        }

        if ($this_page > 1) {
            $page = ($this_page - 2) * $this->limit;
            $html .= "\n<a href=\"#beg\" class=\"pagenav\" title=\""._("First page")."\" onclick=\"javascript: document.adminForm.limitstart".$this->suffix.".value=0; document.adminForm.submit();return false;\">&lt;&lt; "._("First")."</a>";
            $html .= "\n<a href=\"#prev\" class=\"pagenav\" title=\""._("Previous page")."\" onclick=\"javascript: document.adminForm.limitstart".$this->suffix.".value=$page; document.adminForm.submit();return false;\">&lt; "._("Previous")."</a>";
        } else {
            $html .= "\n<span class=\"pagenav\">&lt;&lt; "._("First")."</span>";
            $html .= "\n<span class=\"pagenav\">&lt; "._("Previous")."</span>";
        }

        for ($i=$start_loop; $i <= $stop_loop; $i++) {
            $page = ($i - 1) * $this->limit;
            if ($i == $this_page) {
                $html .= "\n<span class=\"pagenav\"> $i </span>";
            } else {
                $html .= "\n<a href=\"#$i\" class=\"pagenav\" onclick=\"javascript: document.adminForm.limitstart".$this->suffix.".value=$page; document.adminForm.submit();return false;\"><strong>$i</strong></a>";
            }
        }

        if ($this_page < $total_pages) {
            $page = $this_page * $this->limit;
            $end_page = ($total_pages-1) * $this->limit;
            $html .= "\n<a href=\"#next\" class=\"pagenav\" title=\""._("Next page")."\" onclick=\"javascript: document.adminForm.limitstart".$this->suffix.".value=$page; document.adminForm.submit();return false;\">"._("Next")." &gt;</a>";
            $html .= "\n<a href=\"#end\" class=\"pagenav\" title=\""._("Last page")."\" onclick=\"javascript: document.adminForm.limitstart".$this->suffix.".value=$end_page; document.adminForm.submit();return false;\">"._("Last")." &gt;&gt;</a>";
        } else {
            $html .= "\n<span class=\"pagenav\">"._("Next")." &gt;</span>";
            $html .= "\n<span class=\"pagenav\">"._("Last")." &gt;&gt;</span>";
        }
        return $html;
    }

    function getListFooter() {
        $html = '<div style="text-align: center; padding: 2px;">';
        $html .= $this->getPagesLinks();
        $html .= '</div>';
        $html .= '<div style="text-align: center; padding: 2px;">'._("Show").' #';
        $html .= $this->getLimitBox();
        $html .= $this->getPagesCounter();
        $html .= '</div>';
        return $html;
    }
/**
* @param int The row index
* @return int
*/
    function rowNumber( $i ) {
        return $i + 1 + $this->limitstart;
    }
}
?>