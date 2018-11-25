<?php

/**
 * hMailServer Signature Plugin for Roundcube
 *
 * @version 1.2
 * @author Andreas Tunberg <andreas@tunberg.com>
 *
 * Copyright (C) 2018, Andreas Tunberg
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://www.gnu.org/licenses/.
 */

define('HMS_ERROR', 1);
define('HMS_CONNECT_ERROR', 2);
define('HMS_SUCCESS', 0);

/**
 * Change hMailServer signature plugin
 *
 * Plugin that adds functionality to change hMailServer signature messages.
 * It provides common functionality and user interface and supports
 * several backends to finally update the signature.
 *
 * For installation and configuration instructions please read the README file.
 *
 * @author Andreas Tunberg
 */
 
class hms_signature extends rcube_plugin
{
    public $task    = "settings";
    public $noframe = true;
    public $noajax  = true;
    private $rc;
    private $driver;

    function init()
    {
        $this->add_texts('localization/');
        $this->include_stylesheet($this->local_skin_path() . '/hms_signature.css');

        $this->add_hook('settings_actions', array($this, 'settings_actions'));

        $this->register_action('plugin.signature', array($this, 'signature'));
        $this->register_action('plugin.signature-save', array($this, 'signature_save'));
    }

    function settings_actions($args)
    {
        $args['actions'][] = array(
            'action' => 'plugin.signature',
            'class'  => 'signature',
            'label'  => 'signature',
            'title'  => 'changesignature',
            'domain' => 'hms_signature'
        );

        return $args;
    }

    function signature_init()
    {
        $this->rc = rcube::get_instance();
        $this->load_config();
        $this->rc->output->set_pagetitle($this->gettext('changesignature'));
    }

    function signature()
    {
        $this->signature_init();

        $this->register_handler('plugin.body', array($this, 'signature_form'));

        $this->rc->output->send('plugin');
    }

    function signature_save()
    {
        $this->signature_init();

        $dataToSave = array(
            'action'    => 'signature_save',
            'enabled'   => rcube_utils::get_input_value('_enabled', rcube_utils::INPUT_POST),
            'html'      => rcube_utils::get_input_value('_html', rcube_utils::INPUT_POST, true),
            'plaintext' => rcube_utils::get_input_value('_plaintext', rcube_utils::INPUT_POST),
        );

        if (!empty($dataToSave['html'])) {
            // replace uploaded images with data URIs
            $dataToSave['html'] = $this->rcmail_attach_images($dataToSave['html']);

            // XSS protection in HTML signature (#1489251)
            $dataToSave['html'] = $this->rcmail_wash_html($dataToSave['html']);
        }

        if (!($result = $this->_save($dataToSave))) {
            $this->rc->output->command('display_message', $this->gettext('successfullyupdated'), 'confirmation');
        }
        else {
            $this->rc->output->command('display_message', $result, 'error');
        }

        $this->register_handler('plugin.body', array($this, 'signature_form'));

        $this->rc->overwrite_action('plugin.signature');
        $this->rc->output->send('plugin');
    }

    function signature_form()
    {
        $currentData = $this->_load(array('action' => 'signature_load'));

        if (!is_array($currentData)) {
            if ($currentData == HMS_CONNECT_ERROR) {
                $error = $this->gettext('loadconnecterror');
            }
            else {
                $error = $this->gettext('loaderror');
            }

            $this->rc->output->command('display_message', $error, 'error');
            return;
        }

        // Correctly handle HTML entities in HTML editor (#1488483)
        $currentData['html'] = htmlspecialchars($currentData['html'], ENT_NOQUOTES, RCUBE_CHARSET);

        $table = new html_table(array('cols' => 2, 'class' => 'propform'));

        $field_id = 'enabled';
        $input_enabled = new html_checkbox(array (
                'name'  => '_enabled',
                'id'    => $field_id,
                'value' => 1
        ));

        $table->add('title', html::label($field_id, rcube::Q($this->gettext('enabled'))));
        $table->add(null, $input_enabled->show($currentData['enabled']));

        $field_id = 'plaintext';
        $input_plaintext = new html_textarea(array (
                'name' => '_plaintext',
                'rows' => '6',
                'cols' => '40',
                'id'   => $field_id
        ));

        $table->add('title', html::label($field_id, rcube::Q($this->gettext('plaintextsignature'))));
        $table->add(null, $input_plaintext->show($currentData['plaintext']));

        $field_id = 'html';
        $input_html = new html_textarea(array (
                'name' => '_html',
                'rows' => '6',
                'cols' => '40',
                'class' => 'mce_editor',
                'is_escaped' => true,
                'id'   => $field_id
        ));

        $table->add('title', html::label($field_id, rcube::Q($this->gettext('htmlsignature'))));
        $table->add(null, $input_html->show($currentData['html']));

        $submit_button = $this->rc->output->button(array(
                'command' => 'plugin.signature-save',
                'class'   => 'button mainaction submit',
                'label'   => 'save'
        ));

        $form = $this->rc->output->form_tag(array(
            'id'     => 'signature-form',
            'name'   => 'signature-form',
            'class'  => 'propform',
            'method' => 'post',
            'action' => './?_task=settings&_action=plugin.signature-save',
        ), $table->show());

        $out = html::div(array('id' => 'prefs-title', 'class' => 'boxtitle'), $this->gettext('changesignature'))
            . html::div(array('class' => 'hms box formcontainer scroller'),
                html::div(array('class' => 'boxcontent formcontent'), $form)
            . html::div(array('class' => 'footerleft formbuttons'), $submit_button));

        $this->rc->output->add_gui_object('signatureform', 'signature-form');

        $this->include_script('hms_signature.js');

        if($this->rc->config->get('hms_signature_htmleditor', false)) {
            // default font for HTML editor
            $font = rcmail::font_defs($this->rc->config->get('default_font'));
            if ($font && !is_array($font)) {
                $this->rc->output->set_env('default_font', $font);
            }

            // default font size for HTML editor
            if ($font_size = $this->rc->config->get('default_font_size')) {
                $this->rc->output->set_env('default_font_size', $font_size);
            }
            

            $this->rc->output->set_env('action', 'hms_signature');
            $this->rc->html_editor();

            // add image upload form
            $max_filesize   = $this->rc->upload_init($this->rc->config->get('hms_signature_image_size', 64) * 1024);
            $upload_form_id = 'signatureImageUpload';

            $out .= '<form id="' . $upload_form_id . '" style="display: none">'
                . html::div('hint', $this->rc->gettext(array('name' => 'maxuploadsize', 'vars' => array('size' => $max_filesize))))
                . '</form>';

            $this->rc->output->add_gui_object('uploadform', $upload_form_id);
        }

        return $out;
    }

    private function _load($data)
    {
        if (is_object($this->driver)) {
            $result = $this->driver->load($data);
        }
        elseif (!($result = $this->load_driver())){
            $result = $this->driver->load($data);
        }
        return $result;
    }

    private function _save($data, $response = false)
    {
        if (is_object($this->driver)) {
            $result = $this->driver->save($data);
        }
        elseif (!($result = $this->load_driver())){
            $result = $this->driver->save($data);
        }
        
        if ($response) return $result;

        switch ($result) {
            case HMS_SUCCESS:
                return;
            case HMS_CONNECT_ERROR:
                $reason = $this->gettext('updateconnecterror');
                break;
            case HMS_ERROR:
            default:
                $reason = $this->gettext('updateerror');
        }

        return $reason;
    }

    private function load_driver()
    {
        $config = rcmail::get_instance()->config;
        $driver = $config->get('hms_signature_driver', 'hmail');
        $class  = "rcube_{$driver}_signature";
        $file   = $this->home . "/drivers/$driver.php";

        if (!file_exists($file)) {
            rcube::raise_error(array(
                'code' => 600,
                'type' => 'php',
                'file' => __FILE__, 'line' => __LINE__,
                'message' => "hms_signature plugin: Unable to open driver file ($file)"
            ), true, false);
            return HMS_ERROR;
        }

        include_once $file;

        if (!class_exists($class, false) || !method_exists($class, 'save') || !method_exists($class, 'load')) {
            rcube::raise_error(array(
                'code' => 600,
                'type' => 'php',
                'file' => __FILE__, 'line' => __LINE__,
                'message' => "hms_signature plugin: Broken driver $driver"
            ), true, false);
            return $this->gettext('internalerror');
        }

        $this->driver = new $class;
    }

    /**
     * Attach uploaded images into signature as data URIs
     */
    function rcmail_attach_images($html)
    {
        $offset = 0;
        $regexp = '/\s(poster|src)\s*=\s*[\'"]*\S+upload-display\S+file=rcmfile(\w+)[\s\'"]*/';

        while (preg_match($regexp, $html, $matches, 0, $offset)) {
            $file_id  = $matches[2];
            $data_uri = ' ';

            if ($file_id && ($file = $_SESSION['plugin-signature']['files'][$file_id])) {
                $file = $this->rc->plugins->exec_hook('attachment_get', $file);

                $data_uri .= 'src="data:' . $file['mimetype'] . ';base64,';
                $data_uri .= base64_encode($file['data'] ?: file_get_contents($file['path']));
                $data_uri .= '" ';
            }

            $html    = str_replace($matches[0], $data_uri, $html);
            $offset += strlen($data_uri) - strlen($matches[0]) + 1;
        }

        return $html;
    }

    /**
     * Sanity checks/cleanups on HTML body of signature
     */
    function rcmail_wash_html($html)
    {
        // Add header with charset spec., washhtml cannot work without that
        $html = '<html><head>'
            . '<meta http-equiv="Content-Type" content="text/html; charset='.RCUBE_CHARSET.'" />'
            . '</head><body>' . $html . '</body></html>';

        // clean HTML with washhtml by Frederic Motte
        $wash_opts = array(
            'show_washed'   => false,
            'allow_remote'  => 1,
            'charset'       => RCUBE_CHARSET,
            'html_elements' => array('body', 'link'),
            'html_attribs'  => array('rel', 'type'),
        );

        // initialize HTML washer
        $washer = new rcube_washtml($wash_opts);

        // Remove non-UTF8 characters (#1487813)
        $html = rcube_charset::clean($html);

        $html = $washer->wash($html);

        // remove unwanted comments and tags (produced by washtml)
        $html = preg_replace(array('/<!--[^>]+-->/', '/<\/?body>/'), '', $html);

        return $html;
    }
}
