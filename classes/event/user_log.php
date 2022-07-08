<?php


namespace mod_sloodle\event;


defined('MOODLE_INTERNAL') || die();


class user_log extends \core\event\base 
{
    public static function get_name()        // イベント名
    {
        return 'user_log';
    }


    public function get_url()
    {
        $params = array();
        $pgname = '';
        if (isset($this->other['params'])) $params = $this->other['params'];
        if (isset($this->other['pgname'])) $pgname = $this->other['pgname'];

        return new \moodle_url('/mod/sloodle/'.$pgname, $params);
    }


    public function get_description()
    {
        $info = '';
        if (isset($this->other['info'])) $info = $this->other['info'];

        return $info;
    }


    protected function init()
    {
        $this->data['crud'] = 'c';                      // イベントの種類　c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;    // 教育レベル LEVEL_TEACHING, LEVEL_PARTICIPATING or LEVEL_OTHER 
    }
}
