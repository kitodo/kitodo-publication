$(document).ready(function() {
    loadMdEditor();
    loadMdPreview();
});

var loadMdEditor = function () {
    $("div[id^=markdown_area_]").each(function () {
        var editorSettings = {
            height : "500px",
            path   : "typo3conf/ext/dpf/Resources/Public/JavaScript/editormd/lib/",
            toolbarIcons : function() {
                // Using "||" set icons align right.
                return ["undo", "redo", "|", "bold", "del", "italic", "quote", "|", "h1", "h2", "h3", "h4", "h5", "h6", "|", "list-ul", "list-ol", "hr", "code", "preformatted-text", "code-block", "|", "help", "info", "||", "watch", "preview", "fullscreen"]
            },
        };

        if ($(this).find('textarea').attr('readonly') == 'readonly') {
            editorSettings.readOnly = true;
        }

        var editor = editormd($(this).attr('id'), editorSettings);
    });
}

var loadMdPreview = function () {
    $("div[id^=markdown_preview_]").each(function () {
        var editor = editormd($(this).attr('id'), {
            height: "500px",
            readOnly: true,
            path: "typo3conf/ext/dpf/Resources/Public/JavaScript/editormd/lib/",
            toolbarIcons: function () {
                // Using "||" set icons align right.
                return []
            },
        });
    });
}