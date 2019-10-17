<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * @package   mod_pdfannotator
 * @copyright 2018 RWTH Aachen (see README.md)
 * @author    Friederike Schwager
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined('MOODLE_INTERNAL') || die();

/**
 * This class contains functions returning the data for the statistics-tab
 */
class pdfannotator_statistics {

    private $courseid;
    private $annotatorid;
    private $userid;
    private $isteacher;

    public function __construct($courseid, $annotatorid, $userid, $isteacher = false) {
        $this->annotatorid = $annotatorid;
        $this->courseid = $courseid;
        $this->userid = $userid;
        $this->isteacher = $isteacher;
    }

    /**
     * Returns the number of questions/answers in one PDF-Annotator by one/all users
     * @global type $DB
     * @param type $isquestion  '1' for questions, '0' for answers
     * @param type $user   false by default for comments by all users. True for comments by the user
     * @return type
     */
    public function get_comments_annotator($isquestion, $user = false) {
        global $DB;

        $conditions = array('pdfannotatorid' => $this->annotatorid, 'isquestion' => $isquestion, 'isdeleted' => '0');
        if ($user) {
            $conditions['userid'] = $this->userid;
        }

        return $DB->count_records('pdfannotator_comments', $conditions);
    }

    /**
     * Returns the number of questions/answers in all PDF-Annotators in one course by one/all users
     * @global type $DB
     * @param type $isquestion  '1' for questions, '0' for answers
     * @param type $user false by default for comments by all users. userid for comments by a specific user
     * @return type
     */
    public function get_comments_course($isquestion, $user = false) {
        global $DB;
        $sql = 'SELECT COUNT(*) FROM {pdfannotator_comments} c JOIN {pdfannotator} a ON '
                . 'a.course = ? AND a.id = c.pdfannotatorid WHERE c.isquestion = ? AND c.isdeleted = ?';
        if ($user) {
            $sql .= " AND c.userid = ?";
        }
        return $DB->count_records_sql($sql, array($this->courseid, $isquestion, '0', $this->userid));
    }

    /**
     * Returns the average number of questions/answers a user wrote in this pdf-annotator.
     * Only users that wrote at least one comment are included.
     * @global type $DB
     * @param type $isquestion '1' for questions, '0' for answers
     * @return type
     */
    public function get_comments_average_annotator($isquestion) {
        global $DB;
        $sql = "SELECT AVG(count) AS average FROM ("
                . "SELECT COUNT(*) AS count FROM {pdfannotator_comments} "
                . "WHERE pdfannotatorid = ? AND isquestion = ? AND isdeleted = ? "
                . "GROUP BY userid ) AS counts";

        return key($DB->get_records_sql($sql, array($this->annotatorid, $isquestion, '0')));
    }

    /**
     * Returns the average number of questions/answers a user wrote in this course.
     * Only users that wrote at least one comment are included.
     * @global type $DB
     * @param type $isquestion '1' for questions, '0' for answers
     * @return type
     */
    public function get_comments_average_course($isquestion) {
        global $DB;
        $sql = "SELECT AVG(count) AS average FROM ("
                . "SELECT COUNT(*) AS count "
                . "FROM {pdfannotator_comments} c, {pdfannotator} a "
                . "WHERE a.course = ? AND a.id = c.pdfannotatorid AND c.isquestion = ? AND c.isdeleted = ?"
                . "GROUP BY c.userid ) AS counts";

        return key($DB->get_records_sql($sql, array($this->courseid, $isquestion, '0')));
    }

    /**
     * Returns the number of reported comments in this annotator.
     * @global type $DB
     * @return type
     */
    public function get_reports_annotator() {
        global $DB;
        return $DB->count_records('pdfannotator_reports', array('pdfannotatorid' => $this->annotatorid));
    }

    /**
     * Returns the number of reported comments in this course.
     * @global type $DB
     * @return type
     */
    public function get_reports_course() {
        global $DB;
        return $DB->count_records('pdfannotator_reports', array('courseid' => $this->courseid));
    }

    /**
     * Returns the data for the tabl in the statistics-tab
     * @return array
     */
    public function get_tabledata() {
        $ret = [];

        $ret[] = array('row' => array(get_string('questions', 'pdfannotator'), $this->get_comments_annotator('1'), $this->get_comments_course('1')));
        $ret[] = array('row' => array(get_string('myquestions', 'pdfannotator'), $this->get_comments_annotator('1', true), $this->get_comments_course('1', true)));
        $ret[] = array('row' => array(get_string('average_questions', 'pdfannotator').'<a class="btn btn-link p-a-0" role="button" data-container="body" data-toggle="popover" data-placement="right" data-content="'.get_string('average_help', 'pdfannotator').'" data-html="true" tabindex="0" data-trigger="focus"><li class="icon fa fa-question-circle text-info fa-fw" aria-hidden="true" title="'.get_string('entity_helptitle', 'pdfannotator').' '.get_string('average', 'pdfannotator').'"></li></a>'
                    , round($this->get_comments_average_annotator('1'), 2), round($this->get_comments_average_course('1'), 2)));
        $ret[] = array('row' => array(get_string('answers', 'pdfannotator'), $this->get_comments_annotator('0'), $this->get_comments_course('0')));
        $ret[] = array('row' => array(get_string('myanswers', 'pdfannotator'), $this->get_comments_annotator('0', true), $this->get_comments_course('0', true)));
        $ret[] = array('row' => array(get_string('average_answers', 'pdfannotator').'<a class="btn btn-link p-a-0" role="button" data-container="body" data-toggle="popover" data-placement="right" data-content="'.get_string('average_help', 'pdfannotator').'" data-html="true" tabindex="0" data-trigger="focus"><li class="icon fa fa-question-circle text-info fa-fw" aria-hidden="true" title="'.get_string('entity_helptitle', 'pdfannotator').' '.get_string('average', 'pdfannotator').'"></li></a>'
                    , round($this->get_comments_average_annotator('0'), 2), round($this->get_comments_average_course('0'), 2)));
        if ($this->isteacher) {
            $ret[] = array('row' => array(get_string('reports', 'pdfannotator'), $this->get_reports_annotator(), $this->get_reports_course()));
        }

        return $ret;
    }

    /**
     * Returns the data for the chart in the statistics-tab.
     * @global type $DB
     * @param type $pdfannotators
     * @return type
     */
    public function get_chartdata() {

        $pdfannotators = pdfannotator_instance::get_pdfannotator_instances($this->courseid);

        $names = [];
        $otheranswers = [];
        $myanswers = [];
        $otherquestions = [];
        $myquestions = [];
        foreach ($pdfannotators as $pdfannotator) {
            $countquestions = self::count_comments_annotator($pdfannotator->get_id(), '1');
            $countmyquestions = self::count_comments_annotator($pdfannotator->get_id(), '1', $this->userid);
            $countanswers = self::count_comments_annotator($pdfannotator->get_id(), '0');
            $countmyanswers = self::count_comments_annotator($pdfannotator->get_id(), '0', $this->userid);

            $otherquestions[] = $countquestions - $countmyquestions;
            $myquestions[] = $countmyquestions;
            $otheranswers[] = $countanswers - $countmyanswers;
            $myanswers[] = $countmyanswers;
            $names[] = $pdfannotator->get_name();
        }
        $ret = array($names, $otherquestions, $myquestions, $otheranswers, $myanswers);
        return $ret;
    }

    /**
     * Returns the number of questions/answers in one PDF-Annotator by one/all users
     * @global type $DB
     * @param type $annotatorid
     * @param type $isquestion '1' for questions, '0' for answers
     * @param type $userid false by default for comments by all users. Userid for comments by a specific user
     * @return type
     */
    public static function count_comments_annotator($annotatorid, $isquestion, $userid = false) {
        global $DB;

        $conditions = array('pdfannotatorid' => $annotatorid, 'isquestion' => $isquestion, 'isdeleted' => '0');
        if ($userid) {
            $conditions['userid'] = $userid;
        }

        return $DB->count_records('pdfannotator_comments', $conditions);
    }

}
