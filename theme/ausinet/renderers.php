<?php 

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/rendererbase.php');
require_once($CFG->dirroot.'/question/type/essay/renderer.php');

class theme_ausinet_qtype_essay_renderer extends qtype_essay_renderer{

    public function files_read_only(question_attempt $qa, question_display_options $options) {
        $files = $qa->get_last_qt_files('attachments', $options->context->id);
        $output = array();
        
        $extensions=array('Image (JPEG)','Image (PNG)','Image (BMP)','Image (GIF)','Image (PICT)','Image (SVG+XML)','Image (TIFF)','base64');
        foreach ($files as $file) {
            $temp = '<div>';
            if(get_mimetype_description($file)=='PDF document'){
                $temp .= "<iframe src='".$qa->get_response_file_url($file)."' width=\"100%\" style=\"height:500px\"></iframe>";
            }
            if(in_array(get_mimetype_description($file), $extensions)){
                $temp .= html_writer::link($qa->get_response_file_url($file), '<img class= card-img dashboard-card-img src="'.$qa->get_response_file_url($file).'"/>', array('target'=>'_blank'));
            }
            $temp .= html_writer::tag('p', html_writer::link($qa->get_response_file_url($file),
            $this->output->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon')) . ' ' . s($file->get_filename()), array('target'=>'_blank'))).'</div>';
            $output[] = $temp;
        }
        return implode($output);
    }
}
