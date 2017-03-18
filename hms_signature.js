/**
 * hms_signature plugin script
 *
 * @licstart  The following is the entire license notice for the
 * JavaScript code in this file.
 *
 * Copyright (c) 2017, Andreas Tunberg <andreas@tunberg.com>
 *
 * The JavaScript code in this page is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 *
 * @licend  The above is the entire license notice
 * for the JavaScript code in this file.
 */
 
window.rcmail && rcmail.addEventListener('init', function(evt) {

    rcmail.register_command('plugin.signature-save', function() {
        rcmail.set_busy(true, 'loading');
        rcmail.gui_objects.signatureform.submit();
    },true);
    if (rcmail.env.editor_config) {
        rcmail.env.editor_config.extra_plugins = ['hr '];
        rcmail.env.editor_config.extra_buttons = ['hr '];
        rcmail.env.editor_config.disabled_plugins = ['searchreplace'];
        rcmail.env.editor_config.disabled_buttons = ['searchreplace'];
        rcmail.env.editor_config.spellcheck = 0;
        rcube_text_editor(rcmail.env.editor_config, 'html');
    }
});